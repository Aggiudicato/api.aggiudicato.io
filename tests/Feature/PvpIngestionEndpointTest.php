<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyWsSecurity;
use App\Models\Insertion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PvpIngestionEndpointTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtureXml;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyWsSecurity::class);

        Storage::fake('local');

        config([
            'pvp.client_cert_path' => storage_path('certs/pvp-client.pem'),
            'pvp.client_key_path'  => storage_path('certs/pvp-client-key.pem'),
        ]);

        $this->fixtureXml = file_get_contents(
            base_path('tests/Fixtures/soap_inserzione_esp_vendita.xml')
        );
    }

    public function test_post_persists_insertion_and_related_entities(): void
    {
        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $this->fixtureXml,
        );

        $response->assertOk();

        $insertion = Insertion::where('pvp_id', '45508')->firstOrFail();

        $this->assertSame('d93e128b-b30d-11e7-9729-005056b14a1a', $insertion->message_id);
        $this->assertSame('giudiziaria', $insertion->type);
        $this->assertSame('2017-10-17', $insertion->publication_date->toDateString());
        $this->assertSame('received', $insertion->status);

        $procedure = $insertion->procedure;
        $this->assertNotNull($procedure);
        $this->assertSame('51155', $procedure->pvp_id);
        $this->assertSame('Tribunale di MONZA', $procedure->court);
        $this->assertSame('2', $procedure->number);
        $this->assertSame('2017', $procedure->year);

        $subjects = $procedure->subjects;
        $this->assertCount(1, $subjects);
        $this->assertSame('Delegato alla vendita', $subjects->first()->type);
        $this->assertSame('mail@mail.it', $subjects->first()->email);

        $lot = $insertion->lot;
        $this->assertNotNull($lot);
        $this->assertEquals(43902, $lot->pvp_id);
        $this->assertSame('IMMOBILI', $lot->genre);
        $this->assertSame('IMMOBILE RESIDENZIALE', $lot->category);

        $assets = $lot->assets;
        $this->assertCount(1, $assets);
        $this->assertSame('52453', $assets->first()->pvp_id);
        $this->assertSame('Carate Brianza', $assets->first()->location_city);
        $this->assertSame('Lombardia', $assets->first()->location_region);

        $saleData = $insertion->saleData;
        $this->assertNotNull($saleData);
        $this->assertSame('SENZA INCANTO', $saleData->sale_type);
        $this->assertSame('SINCRONA TELEMATICA', $saleData->sale_method);
        $this->assertSame('100,00', $saleData->minimum_bid);
        $this->assertSame('10,00', $saleData->minimum_raise);

        $this->assertCount(2, $insertion->sites);
    }

    public function test_post_stores_raw_xml_file_on_disk(): void
    {
        $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $this->fixtureXml,
        );

        Storage::disk('local')->assertExists('aste_ricevute/asta_45508.xml');

        $stored = Storage::disk('local')->get('aste_ricevute/asta_45508.xml');
        $this->assertSame($this->fixtureXml, $stored);
    }

    public function test_post_returns_signed_soap_success_response(): void
    {
        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $this->fixtureXml,
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        $body = $response->getContent();

        $this->assertStringContainsString('<xsd2:codice>1</xsd2:codice>', $body);
        $this->assertStringContainsString('<xsd2:descrizione>Ricevuto</xsd2:descrizione>', $body);
        $this->assertStringContainsString('messageId="d93e128b-b30d-11e7-9729-005056b14a1a"', $body);

        // WS-Security response signing
        $this->assertStringContainsString('wsse:Security', $body);
        $this->assertStringContainsString('ds:Signature', $body);
        $this->assertStringContainsString('ds:SignatureValue', $body);
        $this->assertStringContainsString('wsse:BinarySecurityToken', $body);
    }

    public function test_post_with_empty_body_returns_error_response(): void
    {
        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            '',
        );

        $response->assertOk();
        $this->assertStringContainsString('<xsd2:codice>0</xsd2:codice>', $response->getContent());
        $this->assertStringContainsString('No content received', $response->getContent());

        $this->assertDatabaseCount('insertions', 0);
    }

    public function test_post_with_malformed_xml_returns_error_and_no_persistence(): void
    {
        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            '<not-xml<<<',
        );

        $response->assertOk();
        $this->assertStringContainsString('<xsd2:codice>0</xsd2:codice>', $response->getContent());
        $this->assertStringContainsString('Processing error', $response->getContent());

        $this->assertDatabaseCount('insertions', 0);
    }

    public function test_post_with_doctype_payload_is_rejected(): void
    {
        $evilXml = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <foo>&xxe;</foo>
  </soap:Body>
</soap:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $evilXml,
        );

        $response->assertOk();
        $this->assertStringContainsString('<xsd2:codice>0</xsd2:codice>', $response->getContent());
        $this->assertStringContainsString('DOCTYPE not allowed', $response->getContent());

        $this->assertDatabaseCount('insertions', 0);
    }
}
