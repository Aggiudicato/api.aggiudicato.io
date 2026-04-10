<?php

namespace Tests\Unit\Support;

use App\Support\SafeXml;
use Tests\TestCase;

class SafeXmlTest extends TestCase
{
    public function test_loads_well_formed_xml(): void
    {
        $doc = SafeXml::loadDom('<?xml version="1.0"?><root><child>ok</child></root>');
        $this->assertSame('ok', $doc->getElementsByTagName('child')->item(0)->textContent);
    }

    public function test_loads_simple_xml(): void
    {
        $xml = SafeXml::loadSimpleXml('<?xml version="1.0"?><root><a>1</a></root>');
        $this->assertSame('1', (string) $xml->a);
    }

    public function test_rejects_doctype_billion_laughs(): void
    {
        $evil = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE lolz [
  <!ENTITY lol "lol">
  <!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">
]>
<lolz>&lol2;</lolz>
XML;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DOCTYPE not allowed');
        SafeXml::loadDom($evil);
    }

    public function test_rejects_external_entity(): void
    {
        $evil = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<foo>&xxe;</foo>
XML;

        $this->expectException(\RuntimeException::class);
        SafeXml::loadDom($evil);
    }

    public function test_throws_on_malformed_xml(): void
    {
        $this->expectException(\RuntimeException::class);
        SafeXml::loadDom('<not-xml<<');
    }

    public function test_simple_xml_rejects_doctype(): void
    {
        $evil = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<foo>&xxe;</foo>
XML;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DOCTYPE not allowed');
        SafeXml::loadSimpleXml($evil);
    }
}
