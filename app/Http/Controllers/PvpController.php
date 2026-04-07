<?php

namespace App\Http\Controllers;

use App\Services\PvpIngestionService;
use App\Services\WsSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PvpController extends Controller
{
    public function __construct(
        private WsSecurityService $wsSecurity,
    ) {}

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
        $xmlContent = view($view, $data)->render();
        $signedXml = $this->wsSecurity->signResponse($xmlContent);

        return response($signedXml)
            ->header('Content-Type', 'text/xml');
    }
}
