<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyWsSecurity;
use App\Models\Insertion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Accreditation contract: the two XSLT files under public/xslt must
 * round-trip our XHTML listing pages back to the canonical schema the
 * Ministry sent us, otherwise the site monitor rejects the site.
 *
 * These tests don't touch the SOAP pipeline beyond ingesting the
 * fixture to populate the DB. They exercise the public listings pages
 * and the two XSLT files directly, as the Ministry's monitor would.
 */
class PvpAccreditationXsltTest extends TestCase
{
    use RefreshDatabase;

    private const PVP_NS = 'http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/InserzioneEsperimentoVenditaXMLSchema';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyWsSecurity::class);
        Storage::fake('local');

        config([
            'pvp.client_cert_path' => storage_path('certs/pvp-client.pem'),
            'pvp.client_key_path'  => storage_path('certs/pvp-client-key.pem'),
        ]);

        $fixtureXml = file_get_contents(
            base_path('tests/Fixtures/soap_inserzione_esp_vendita.xml')
        );

        $this->call(
            'POST',
            '/pvp/service',
            [], [], [],
            ['CONTENT_TYPE' => 'text/xml'],
            $fixtureXml,
        );

        // Search page only exposes published insertions; ingestion lands
        // them as 'received', so promote the fixture row here.
        Insertion::where('pvp_id', '45508')->update(['status' => 'published']);
    }

    public function test_xslt_ricerca_outputs_detail_url_for_matching_listing(): void
    {
        $insertion = Insertion::where('pvp_id', '45508')->firstOrFail();

        $html = $this->get('/listings?pvp_id=45508')
            ->assertOk()
            ->getContent();

        $output = $this->applyXslt($html, public_path('xslt/xslt_ricerca.xsl'), 'text');

        $this->assertSame(url('/listings/' . $insertion->id), trim($output));
    }

    public function test_xslt_ricerca_outputs_empty_when_no_match(): void
    {
        $html = $this->get('/listings?pvp_id=does-not-exist')
            ->assertOk()
            ->getContent();

        $output = $this->applyXslt($html, public_path('xslt/xslt_ricerca.xsl'), 'text');

        $this->assertSame('', trim($output));
    }

    public function test_xslt_dettaglio_produces_canonical_root_element(): void
    {
        $insertion = Insertion::where('pvp_id', '45508')->firstOrFail();

        $html = $this->get('/listings/' . $insertion->id)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xhtml+xml; charset=UTF-8')
            ->getContent();

        $dom = $this->applyXslt($html, public_path('xslt/xslt_dettaglio.xsl'), 'dom');

        $root = $dom->documentElement;

        $this->assertSame('inserzioneEspVendita', $root->localName);
        $this->assertSame(self::PVP_NS, $root->namespaceURI);
        $this->assertSame('45508', $root->getAttribute('idInserzioneEspVendita'));
        $this->assertSame('d93e128b-b30d-11e7-9729-005056b14a1a', $root->getAttribute('messageId'));
    }

    public function test_xslt_dettaglio_preserves_canonical_element_order_and_values(): void
    {
        $insertion = Insertion::where('pvp_id', '45508')->firstOrFail();

        $html = $this->get('/listings/' . $insertion->id)->getContent();

        $dom = $this->applyXslt($html, public_path('xslt/xslt_dettaglio.xsl'), 'dom');

        $expectedOrder = ['tipologiaInserzione', 'datiProcedura', 'lotto', 'datiVendita'];
        $actualOrder = [];

        foreach ($dom->documentElement->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && in_array($child->localName, $expectedOrder, true)) {
                $actualOrder[] = $child->localName;
            }
        }

        $expectedPresent = array_values(array_intersect($expectedOrder, $actualOrder));
        $this->assertSame($expectedPresent, $actualOrder);

        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:tipologiaInserzione', 'giudiziaria');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:datiProcedura/pvp:tipoProcedura', 'giudiziaria');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:datiProcedura/pvp:proceduraGiudiziaria/pvp:tribunale', 'Tribunale di MONZA');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:datiProcedura/pvp:proceduraGiudiziaria/pvp:numeroProcedura', '2');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:datiProcedura/pvp:proceduraGiudiziaria/pvp:annoProcedura', '2017');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:lotto/pvp:genere', 'IMMOBILI');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:lotto/pvp:categoria', 'IMMOBILE RESIDENZIALE');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:lotto/pvp:beni/pvp:ubicazione/pvp:citta', 'Carate Brianza');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:datiVendita/pvp:tipologiaVendita', 'SENZA INCANTO');
        $this->assertCanonical($dom, '/pvp:inserzioneEspVendita/pvp:datiVendita/pvp:modalitaVendita', 'SINCRONA TELEMATICA');
    }

    public function test_xslt_dettaglio_carries_element_attributes(): void
    {
        $insertion = Insertion::where('pvp_id', '45508')->firstOrFail();

        $html = $this->get('/listings/' . $insertion->id)->getContent();

        $dom = $this->applyXslt($html, public_path('xslt/xslt_dettaglio.xsl'), 'dom');

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('pvp', self::PVP_NS);

        $procedure = $xpath->query('/pvp:inserzioneEspVendita/pvp:datiProcedura')->item(0);
        $this->assertSame('51155', $procedure->getAttribute('idProcedura'));

        $lot = $xpath->query('/pvp:inserzioneEspVendita/pvp:lotto')->item(0);
        $this->assertSame('43902', $lot->getAttribute('idLotto'));

        $asset = $xpath->query('/pvp:inserzioneEspVendita/pvp:lotto/pvp:beni')->item(0);
        $this->assertSame('52453', $asset->getAttribute('idBene'));
    }

    private function applyXslt(string $xmlInput, string $xsltPath, string $mode): \DOMDocument|string
    {
        $source = new \DOMDocument();
        $source->resolveExternals = false;
        $source->substituteEntities = false;
        $loaded = @$source->loadXML($xmlInput, LIBXML_NONET | LIBXML_NOENT);
        $this->assertTrue($loaded, 'Failed to load XHTML source for XSLT input');

        $xsl = new \DOMDocument();
        $xsl->load($xsltPath, LIBXML_NONET);

        $processor = new \XSLTProcessor();
        $processor->importStylesheet($xsl);

        if ($mode === 'dom') {
            $result = $processor->transformToDoc($source);
            $this->assertNotFalse($result, 'XSLT transformation failed');
            return $result;
        }

        $result = $processor->transformToXml($source);
        // transformToXml returns null for an empty text-method result;
        // normalize to '' so callers can compare without special casing.
        return $result ?? '';
    }

    private function assertCanonical(\DOMDocument $dom, string $xpathExpr, string $expectedValue): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('pvp', self::PVP_NS);

        $node = $xpath->query($xpathExpr)->item(0);

        $this->assertNotNull($node, "Missing canonical element at {$xpathExpr}");
        $this->assertSame($expectedValue, trim($node->textContent));
    }
}
