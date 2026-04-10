<?php

namespace App\Services;

use App\Models\Insertion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound callback to the Ministry confirming a listing has been published.
 *
 * Implements the PortaleDelleVenditeSiti.accettazioneAvvenutaPubblicazione
 * operation per PVP_ST_Allegato_A_V1.4 §3.3. Once our site has published
 * an auction listing received from the Ministry, we must notify them
 * back with the published URL so they can verify the publication.
 *
 * Two-layer security model (both mandatory per §3.1):
 *   - Transport (mTLS): Guzzle presents our Actalis client certificate to
 *     the Ministry's SSL endpoint. The Ministry validates the cert chain.
 *   - Message (WS-Security): the SOAP body is X509-signed with the same
 *     private key so the Ministry can archive the signed message as
 *     proof of non-repudiation.
 *
 * Gotcha: the SOAP operation name is `accettazioneAvvenutaPubblicazione`
 * but the body root element is `reportPubblicazioneInserzione` in a
 * DIFFERENT namespace (ReportPubblicazioneInserzioneXMLSchema, not
 * PortaleDelleVenditeSiti). The SOAPAction header uses the operation name,
 * the body uses the report schema name. Don't conflate them.
 */
class PvpCallbackService
{
    private const NS_REPORT = 'http://www.giustizia.it/pvp/integration/portaleVenditaWS/siti/service/definitions/ReportPubblicazioneInserzioneXMLSchema';

    private const SOAP_ACTION = 'http://www.giustizia.it/pvp/integration/portaleVenditaWS/siti/service/definitions/PortaleDelleVenditeSiti/accettazioneAvvenutaPubblicazione';

    public function __construct(private WsSecurityService $wsSecurity) {}

    /**
     * Build, sign and POST the callback SOAP message to the Ministry.
     *
     * Throws RuntimeException on missing config or HTTP error so callers
     * (typically a queued job) can retry via Laravel's failure handling.
     * Logs before and after to enable audit trail reconstruction from
     * storage/logs during post-incident investigation.
     */
    public function notifyPublication(Insertion $insertion): void
    {
        $callbackUrl = config('pvp.callback_url');
        $certPath = config('pvp.client_cert_path');
        $keyPath = config('pvp.client_key_path');

        if (! $callbackUrl) {
            throw new \RuntimeException('PVP_CALLBACK_URL non configurato');
        }

        $listingUrl = url("/listings/{$insertion->id}");
        $publicationDate = $insertion->publication_date->format('Y-m-d');
        $messageId = $insertion->message_id ?? $insertion->pvp_id;

        $unsignedBody = $this->buildCallbackSoapBody(
            $insertion->pvp_id,
            $messageId,
            $listingUrl,
            $publicationDate,
        );

        $signedBody = $this->wsSecurity->signEnvelope($unsignedBody);

        Log::info('PVP Callback: sending publication notification', [
            'pvp_id' => $insertion->pvp_id,
            'callback_url' => $callbackUrl,
            'listing_url' => $listingUrl,
        ]);

        $httpRequest = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => self::SOAP_ACTION,
        ])->timeout(30);

        if ($certPath && $keyPath) {
            $httpRequest = $httpRequest->withOptions([
                'cert' => $certPath,
                'ssl_key' => $keyPath,
            ]);
        }

        $response = $httpRequest->send('POST', $callbackUrl, [
            'body' => $signedBody,
        ]);

        if (! $response->successful()) {
            Log::error('PVP Callback: response error', [
                'pvp_id' => $insertion->pvp_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Callback PVP fallito: HTTP {$response->status()}");
        }

        Log::info('PVP Callback: notification sent successfully', [
            'pvp_id' => $insertion->pvp_id,
        ]);
    }

    private function buildCallbackSoapBody(
        string $idEsperimentoVendita,
        string $messageId,
        string $listingUrl,
        string $publicationDate,
    ): string {
        $ns = self::NS_REPORT;
        $idAttr = htmlspecialchars($messageId, ENT_QUOTES | ENT_XML1);
        $idEsc = htmlspecialchars($idEsperimentoVendita, ENT_QUOTES | ENT_XML1);
        $urlEsc = htmlspecialchars($listingUrl, ENT_QUOTES | ENT_XML1);
        $dateEsc = htmlspecialchars($publicationDate, ENT_QUOTES | ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <reportPubblicazioneInserzione xmlns="{$ns}" messageId="{$idAttr}">
            <idEsperimentoVendita>{$idEsc}</idEsperimentoVendita>
            <dataPubblicazione>{$dateEsc}</dataPubblicazione>
            <urlInserzione>{$urlEsc}</urlInserzione>
        </reportPubblicazioneInserzione>
    </soap:Body>
</soap:Envelope>
XML;
    }
}
