<?php

namespace Tests\Feature;

use App\Services\WsSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end test for the VerifyWsSecurity middleware on POST /pvp/service.
 *
 * Since we don't have the Ministry's private key, we use our own client
 * certificate as the "ministry" cert: we sign an outgoing request with its
 * private key and configure ministry_cert_path to point at the same cert,
 * so the fingerprint check and signature verification both succeed.
 */
class PvpInboundSignatureTest extends TestCase
{
    use RefreshDatabase;

    private string $unsignedFixture;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config([
            'pvp.client_cert_path' => storage_path('pvp-client.pem'),
            'pvp.client_key_path'  => storage_path('pvp-client-key.pem'),
        ]);

        $this->unsignedFixture = file_get_contents(
            base_path('tests/Fixtures/soap_inserzione_esp_vendita.xml')
        );
    }

    public function test_unsigned_request_is_rejected_when_ministry_cert_configured(): void
    {
        config(['pvp.ministry_cert_path' => storage_path('pvp-client.pem')]);

        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $this->unsignedFixture,
        );

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('<xsd2:codice>0</xsd2:codice>', $body);
        $this->assertStringContainsString('WS-Security validation failed', $body);

        $this->assertDatabaseCount('insertions', 0);
    }

    public function test_signed_request_with_matching_cert_is_accepted(): void
    {
        config(['pvp.ministry_cert_path' => storage_path('pvp-client.pem')]);

        $signedRequest = (new WsSecurityService())->signEnvelope($this->unsignedFixture);

        $this->assertStringContainsString('wsse:Security', $signedRequest);
        $this->assertStringContainsString('ds:Signature', $signedRequest);

        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $signedRequest,
        );

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('<xsd2:codice>1</xsd2:codice>', $body);
        $this->assertStringContainsString('<xsd2:descrizione>Ricevuto</xsd2:descrizione>', $body);

        $this->assertDatabaseHas('insertions', ['pvp_id' => '45508']);
    }

    public function test_signed_request_with_mismatched_fingerprint_is_rejected(): void
    {
        // Point ministry cert to the real model-office ministry cert.
        // Our request is signed with the client cert — fingerprints will differ.
        config([
            'pvp.ministry_cert_path' => storage_path('documenti/model-office-pvp.giustizia.it.cer'),
        ]);

        $signedRequest = (new WsSecurityService())->signEnvelope($this->unsignedFixture);

        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $signedRequest,
        );

        $response->assertOk();
        $this->assertStringContainsString('WS-Security validation failed', $response->getContent());
        $this->assertDatabaseCount('insertions', 0);
    }

    public function test_validation_is_skipped_when_ministry_cert_not_configured(): void
    {
        config(['pvp.ministry_cert_path' => null]);

        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $this->unsignedFixture,
        );

        $response->assertOk();
        $this->assertStringContainsString('<xsd2:codice>1</xsd2:codice>', $response->getContent());
        $this->assertDatabaseHas('insertions', ['pvp_id' => '45508']);
    }
}
