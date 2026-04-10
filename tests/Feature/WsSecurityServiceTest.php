<?php

namespace Tests\Feature;

use App\Services\WsSecurityService;
use Tests\TestCase;

class WsSecurityServiceTest extends TestCase
{
    private WsSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pvp.client_cert_path' => storage_path('pvp-client.pem'),
            'pvp.client_key_path'  => storage_path('pvp-client-key.pem'),
        ]);

        $this->service = new WsSecurityService();
    }

    public function test_pem_files_exist_and_are_valid(): void
    {
        $certPath = config('pvp.client_cert_path');
        $keyPath = config('pvp.client_key_path');

        $this->assertFileExists($certPath);
        $this->assertFileExists($keyPath);

        $cert = openssl_x509_read(file_get_contents($certPath));
        $this->assertNotFalse($cert, 'Certificate must be readable');

        $parsed = openssl_x509_parse($cert);
        $this->assertSame('PROPTICA S.R.L.', $parsed['subject']['O']);
        $this->assertSame('api.aggiudicato.io', $parsed['subject']['CN']);

        $privateKey = openssl_pkey_get_private(file_get_contents($keyPath));
        $this->assertNotFalse($privateKey, 'Private key must be readable');

        $certDetails = openssl_pkey_get_details(openssl_pkey_get_public($cert));
        $keyDetails = openssl_pkey_get_details($privateKey);

        $this->assertSame(
            bin2hex($certDetails['rsa']['n']),
            bin2hex($keyDetails['rsa']['n']),
            'Certificate and private key must share the same RSA modulus'
        );
    }

    public function test_sign_response_adds_wsse_security_header(): void
    {
        $unsigned = $this->buildSoapEnvelope('<tns:receiveResponse>ok</tns:receiveResponse>');

        $signed = $this->service->signEnvelope($unsigned);

        $this->assertStringContainsString('wsse:Security', $signed);
        $this->assertStringContainsString('wsse:BinarySecurityToken', $signed);
        $this->assertStringContainsString('ds:Signature', $signed);
        $this->assertStringContainsString('ds:SignedInfo', $signed);
        $this->assertStringContainsString('ds:SignatureValue', $signed);
        $this->assertStringContainsString('ds:DigestValue', $signed);
        $this->assertStringContainsString('wsu:Id="Body"', $signed);
    }

    public function test_sign_response_produces_verifiable_signature(): void
    {
        $unsigned = $this->buildSoapEnvelope('<tns:payload>hello</tns:payload>');

        $signed = $this->service->signEnvelope($unsigned);

        $doc = new \DOMDocument();
        $doc->loadXML($signed);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

        $signedInfoNode = $xpath->query('//ds:SignedInfo')->item(0);
        $signatureValueNode = $xpath->query('//ds:SignatureValue')->item(0);
        $tokenNode = $xpath->query('//wsse:BinarySecurityToken')->item(0);

        $this->assertNotNull($signedInfoNode, 'SignedInfo element must be present');
        $this->assertNotNull($signatureValueNode, 'SignatureValue element must be present');
        $this->assertNotNull($tokenNode, 'BinarySecurityToken element must be present');

        $canonicalSignedInfo = $signedInfoNode->C14N(true);
        $signatureBytes = base64_decode($signatureValueNode->textContent);

        $certPem = "-----BEGIN CERTIFICATE-----\n"
            . trim($tokenNode->textContent)
            . "\n-----END CERTIFICATE-----";
        $publicKey = openssl_pkey_get_public($certPem);
        $this->assertNotFalse($publicKey, 'Public key must be extractable from BinarySecurityToken');

        $verifyResult = openssl_verify(
            $canonicalSignedInfo,
            $signatureBytes,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        $this->assertSame(1, $verifyResult, 'Signature must verify cryptographically against the embedded certificate');
    }

    public function test_sign_response_body_digest_matches_actual_body(): void
    {
        $unsigned = $this->buildSoapEnvelope('<tns:result>42</tns:result>');
        $signed = $this->service->signEnvelope($unsigned);

        $doc = new \DOMDocument();
        $doc->loadXML($signed);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $body = $xpath->query('//soap:Body')->item(0);
        $digestNode = $xpath->query('//ds:DigestValue')->item(0);

        $this->assertNotNull($body);
        $this->assertNotNull($digestNode);

        $expectedDigest = base64_encode(hash('sha256', $body->C14N(true), true));

        $this->assertSame($expectedDigest, $digestNode->textContent, 'DigestValue must match SHA256 of canonicalized Body');
    }

    public function test_sign_response_returns_unsigned_when_paths_missing(): void
    {
        config([
            'pvp.client_cert_path' => '/nonexistent/cert.pem',
            'pvp.client_key_path'  => '/nonexistent/key.pem',
        ]);

        $unsigned = $this->buildSoapEnvelope('<tns:ping/>');
        $result = $this->service->signEnvelope($unsigned);

        $this->assertSame($unsigned, $result, 'When cert/key paths are invalid, response must be returned unmodified');
    }

    private function buildSoapEnvelope(string $bodyContent): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://www.giustizia.it/pvp/integration/sitoPubblicitaWS/service/definitions/Siti/">
    <soap:Body>
        {$bodyContent}
    </soap:Body>
</soap:Envelope>
XML;
    }
}
