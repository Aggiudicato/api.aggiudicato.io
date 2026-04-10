<?php

namespace App\Http\Controllers;

use App\Services\PvpIngestionService;
use App\Services\WsSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Entry point for inbound Ministry PVP SOAP calls.
 *
 * Wired to POST /pvp/service behind the VerifyWsSecurity middleware. The
 * request flow is:
 *
 *   1. Nginx terminates TLS and verifies the Ministry client certificate
 *      (mTLS, see deploy/nginx.conf.example).
 *   2. VerifyWsSecurity middleware checks the WS-Security signature on
 *      the SOAP envelope using the pinned ministry public certificate.
 *   3. This controller's receive() method reads the raw XML body and
 *      hands it to PvpIngestionService for parsing + DB persistence.
 *   4. The response is built from a Blade view, then signed by
 *      WsSecurityService (outbound WS-Security) and returned to the
 *      Ministry with Content-Type text/xml.
 *
 * Errors at any stage are caught and converted to SOAP fault envelopes
 * (codice=0) because the Ministry's client expects SOAP-shaped responses
 * even on failure — returning HTTP 500 would cause it to retry forever.
 *
 * The ServiceController (GET /pvp/service) serves the WSDL and is not
 * gated by mTLS so the Ministry (and us) can discover the schema.
 */
class PvpController extends Controller
{
    public function __construct(
        private WsSecurityService $wsSecurity,
    ) {}

    /**
     * Ingest an inbound inserzioneEspVendita SOAP envelope and respond.
     *
     * Returns HTTP 200 with a signed SOAP envelope in every case (success
     * or error) so the Ministry's retry logic can distinguish "processed"
     * from "transport failure" by inspecting the `codice` field.
     */
    public function receive(Request $request, PvpIngestionService $ingestion)
    {
        $xmlContent = $request->getContent();

        if (empty($xmlContent)) {
            Log::warning('PVP: empty request received');

            return $this->soapResponse('service.response_error', [
                'codice' => '0',
                'descrizione' => 'No content received',
            ]);
        }

        try {
            $insertion = $ingestion->ingest($xmlContent);

            return $this->soapResponse('service.response_success', [
                'codice' => '1',
                'descrizione' => 'Ricevuto',
                'messageId' => $insertion->message_id,
            ]);
        } catch (\Exception $e) {
            Log::error('PVP: processing error', [
                'error' => $e->getMessage(),
            ]);

            return $this->soapResponse('service.response_error', [
                'codice' => '0',
                'descrizione' => 'Processing error: ' . $e->getMessage(),
            ]);
        }
    }

    private function soapResponse(string $view, array $data)
    {
        if (isset($data['messageId'])) {
            $data['messageIdAttr'] = ' messageId="' . htmlspecialchars($data['messageId'], ENT_QUOTES | ENT_XML1) . '"';
        } else {
            $data['messageIdAttr'] = '';
        }

        $xmlContent = view($view, $data)->render();
        $signedXml = $this->wsSecurity->signEnvelope($xmlContent);

        return response($signedXml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
