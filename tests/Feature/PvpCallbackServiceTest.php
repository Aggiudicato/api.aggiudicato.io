<?php

namespace Tests\Feature;

use App\Models\Insertion;
use App\Services\PvpCallbackService;
use App\Services\WsSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PvpCallbackServiceTest extends TestCase
{
    use RefreshDatabase;

    private PvpCallbackService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pvp.callback_url'     => 'https://model-office-pvp.giustizia.it/pvp/callback',
            'pvp.client_cert_path' => storage_path('pvp-client.pem'),
            'pvp.client_key_path'  => storage_path('pvp-client-key.pem'),
        ]);

        $this->service = new PvpCallbackService(new WsSecurityService());
    }

    private function makeInsertion(array $overrides = []): Insertion
    {
        return Insertion::create(array_merge([
            'pvp_id' => '45508',
            'message_id' => 'd93e128b-b30d-11e7-9729-005056b14a1a',
            'type' => 'giudiziaria',
            'publication_date' => '2017-10-17',
            'status' => 'received',
        ], $overrides));
    }

    public function test_callback_sends_signed_soap_envelope_with_correct_schema(): void
    {
        Http::fake([
            'model-office-pvp.giustizia.it/*' => Http::response('<ok/>', 200),
        ]);

        $insertion = $this->makeInsertion();

        $this->service->notifyPublication($insertion);

        Http::assertSent(function (Request $request) use ($insertion) {
            $this->assertSame(
                'https://model-office-pvp.giustizia.it/pvp/callback',
                $request->url(),
            );
            $this->assertSame('POST', $request->method());
            $this->assertSame('text/xml; charset=utf-8', $request->header('Content-Type')[0]);
            $this->assertSame(
                'http://www.giustizia.it/pvp/integration/portaleVenditaWS/siti/service/definitions/PortaleDelleVenditeSiti/accettazioneAvvenutaPubblicazione',
                $request->header('SOAPAction')[0],
            );

            $body = $request->body();

            // Parse namespace-aware (DOMDocument normalises default ns to prefixed form during signing).
            $doc = new \DOMDocument();
            $this->assertTrue($doc->loadXML($body));

            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('report', 'http://www.giustizia.it/pvp/integration/portaleVenditaWS/siti/service/definitions/ReportPubblicazioneInserzioneXMLSchema');
            $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
            $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

            // Correct operation schema per PVP_ST_Allegato_A section 3.3
            $reportNodes = $xpath->query('//soap:Body/report:reportPubblicazioneInserzione');
            $this->assertSame(1, $reportNodes->length);
            $report = $reportNodes->item(0);

            $this->assertSame($insertion->message_id, $report->getAttribute('messageId'));

            $this->assertSame(
                $insertion->pvp_id,
                $xpath->query('report:idEsperimentoVendita', $report)->item(0)->textContent,
            );
            $this->assertSame(
                '2017-10-17',
                $xpath->query('report:dataPubblicazione', $report)->item(0)->textContent,
            );
            $this->assertStringContainsString(
                '/listings/',
                $xpath->query('report:urlInserzione', $report)->item(0)->textContent,
            );

            // WS-Security signature present (required for non-repudiation)
            $this->assertSame(1, $xpath->query('//wsse:Security')->length);
            $this->assertSame(1, $xpath->query('//wsse:BinarySecurityToken')->length);
            $this->assertSame(1, $xpath->query('//ds:Signature')->length);
            $this->assertSame(1, $xpath->query('//ds:SignatureValue')->length);
            $this->assertSame(1, $xpath->query('//ds:DigestValue')->length);

            return true;
        });
    }

    public function test_callback_uses_pvp_id_as_message_id_when_missing(): void
    {
        Http::fake(['*' => Http::response('<ok/>', 200)]);

        $insertion = $this->makeInsertion(['message_id' => null]);

        $this->service->notifyPublication($insertion);

        Http::assertSent(function (Request $request) use ($insertion) {
            $doc = new \DOMDocument();
            $doc->loadXML($request->body());
            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('report', 'http://www.giustizia.it/pvp/integration/portaleVenditaWS/siti/service/definitions/ReportPubblicazioneInserzioneXMLSchema');

            $report = $xpath->query('//report:reportPubblicazioneInserzione')->item(0);
            $this->assertSame($insertion->pvp_id, $report->getAttribute('messageId'));

            return true;
        });
    }

    public function test_callback_signature_verifies_against_client_public_key(): void
    {
        Http::fake(['*' => Http::response('<ok/>', 200)]);

        $this->service->notifyPublication($this->makeInsertion());

        Http::assertSent(function (Request $request) {
            $doc = new \DOMDocument();
            $this->assertTrue($doc->loadXML($request->body()));

            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

            $signedInfoNodes = $xpath->query('//ds:SignedInfo');
            $signatureValueNodes = $xpath->query('//ds:SignatureValue');
            $this->assertSame(1, $signedInfoNodes->length);
            $this->assertSame(1, $signatureValueNodes->length);

            $signedInfo = $signedInfoNodes->item(0)->C14N(true, false);
            $signature = base64_decode(trim($signatureValueNodes->item(0)->textContent));

            $publicKey = openssl_pkey_get_public(
                file_get_contents(storage_path('pvp-client.pem'))
            );
            $result = openssl_verify($signedInfo, $signature, $publicKey, OPENSSL_ALGO_SHA256);

            $this->assertSame(1, $result, 'Callback SOAP signature must verify against client public key');

            return true;
        });
    }

    public function test_callback_throws_when_callback_url_missing(): void
    {
        config(['pvp.callback_url' => null]);

        $insertion = $this->makeInsertion();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PVP_CALLBACK_URL non configurato');

        $this->service->notifyPublication($insertion);
    }

    public function test_callback_throws_on_http_error_response(): void
    {
        Http::fake(['*' => Http::response('<error/>', 500)]);

        $insertion = $this->makeInsertion();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Callback PVP fallito: HTTP 500');

        $this->service->notifyPublication($insertion);
    }
}
