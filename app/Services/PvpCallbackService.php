<?php

namespace App\Services;

use App\Models\Insertion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PvpCallbackService
{
    /**
     * Notify the Ministry that the listing has been published.
     * Sends a SOAP request with the publication data.
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

        $soapBody = $this->buildCallbackSoapBody(
            $insertion->pvp_id,
            $messageId,
            $listingUrl,
            $publicationDate,
        );

        Log::info('PVP Callback: sending publication notification', [
            'pvp_id' => $insertion->pvp_id,
            'callback_url' => $callbackUrl,
            'listing_url' => $listingUrl,
        ]);

        $httpRequest = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => '',
        ])->timeout(30);

        // Configure client TLS certificate if available
        if ($certPath && $keyPath) {
            $httpRequest = $httpRequest->withOptions([
                'cert' => $certPath,
                'ssl_key' => $keyPath,
            ]);
        }

        $response = $httpRequest->send('POST', $callbackUrl, [
            'body' => $soapBody,
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
        string $idInserzione,
        string $messageId,
        string $listingUrl,
        string $publicationDate,
    ): string {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:pvp="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/Siti/">
            <soap:Body>
                <pvp:accettazioneAvvenutaPubblicazione>
                    <idInserzioneEspVendita>{$idInserzione}</idInserzioneEspVendita>
                    <messageId>{$messageId}</messageId>
                    <urlAnnuncio>{$listingUrl}</urlAnnuncio>
                    <dataPubblicazione>{$publicationDate}</dataPubblicazione>
                </pvp:accettazioneAvvenutaPubblicazione>
            </soap:Body>
        </soap:Envelope>
        XML;
    }
}
