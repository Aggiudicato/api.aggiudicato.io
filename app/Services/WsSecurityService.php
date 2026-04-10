<?php

namespace App\Services;

use App\Support\SafeXml;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * WS-Security (OASIS WSS 1.0) signer and verifier for PVP ministry integration.
 *
 * Implements the application-layer security model mandated by
 * PVP_ST_Allegato_A_V1.4 §3.1.2: every SOAP message exchanged with the
 * Ministry must be signed with X.509 (BinarySecurityToken + XMLDSig) to
 * provide non-repudiation on top of the mTLS transport layer.
 *
 * Signing uses SHA256/RSA with exclusive XML canonicalization (exc-c14n).
 * The ministry specification shows SHA1 in its legacy examples, but SHA256
 * is the current accepted algorithm and what all modern PVP integrations use.
 *
 * The cryptographic math (canonicalization, digest, sign, verify) is delegated
 * to robrichards/xmlseclibs — a maintained OASIS-conformant library — rather
 * than built manually. The WSS envelope structure (Security header, BST,
 * SecurityTokenReference) is still assembled locally because it is PVP-specific
 * and not standardized across xmlseclibs helpers.
 */
class WsSecurityService
{
    private const WSSE_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

    private const WSU_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';

    private const SOAP_NS = 'http://schemas.xmlsoap.org/soap/envelope/';

    private const DS_NS = 'http://www.w3.org/2000/09/xmldsig#';

    private const X509_VALUE_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3';

    private const BASE64_ENCODING_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

    /**
     * Verify the WS-Security signature on an incoming SOAP envelope.
     *
     * Three-step validation: (1) extract the ministry-provided X509 cert
     * from the BinarySecurityToken, (2) pin it against the ministry cert
     * we have on disk by SHA256 fingerprint comparison, (3) verify the
     * XMLDSig signature (reference digest + signature value) using xmlseclibs.
     *
     * Fingerprint pinning stays in application code because xmlseclibs has no
     * concept of trusted certificates — it only verifies signature math.
     *
     * When the ministry certificate path is not configured, validation is
     * deliberately skipped and returns true. This is NOT a security bug —
     * it's intentional so local development and CI can run without needing
     * production certificates. In production the env var must be set.
     */
    public function validateIncomingSignature(string $xmlContent): bool
    {
        $ministryCertPath = config('pvp.ministry_cert_path');

        if (! $ministryCertPath || ! file_exists($ministryCertPath)) {
            // Fail-closed in production: the dev-mode bypass exists only so
            // local and CI environments can run without the ministry cert.
            // Allowing it in production would silently disable signature
            // pinning and let forged messages through.
            if (app()->environment('production')) {
                throw new \RuntimeException('WS-Security: ministry certificate required in production but not configured');
            }

            Log::warning('WS-Security: ministry certificate not configured, skipping validation');

            return true;
        }

        try {
            $doc = SafeXml::loadDom($xmlContent);

            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('wsse', self::WSSE_NS);

            $token = $this->extractBinarySecurityToken($xpath);
            if (! $token) {
                return false;
            }

            if (! $this->verifyCertificateFingerprint($token['cert'], $ministryCertPath)) {
                return false;
            }

            $dsig = new XMLSecurityDSig();
            $signatureNode = $dsig->locateSignature($doc);
            if (! $signatureNode) {
                Log::warning('WS-Security: Signature element not found');

                return false;
            }

            $dsig->canonicalizeSignedInfo();

            if (! $dsig->validateReference()) {
                Log::warning('WS-Security: reference digest validation failed');

                return false;
            }

            $publicKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
            $publicKey->loadKey($token['pem'], false, true);

            if ($dsig->verify($publicKey) !== 1) {
                Log::warning('WS-Security: signature invalid');

                return false;
            }

            Log::info('WS-Security: signature valid');

            return true;
        } catch (\Exception $e) {
            Log::error('WS-Security: validation exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sign a SOAP envelope and return it with a wsse:Security header.
     *
     * Used for both inbound responses we return to the Ministry and outbound
     * callback requests we originate — same WSS construction in either case.
     * Marks the Body with `wsu:Id`, delegates digest and signature computation
     * to xmlseclibs (SHA256/RSA, exc-c14n), then wraps the result in a
     * `wsse:Security` header with a `BinarySecurityToken` carrying the X.509
     * client certificate, referenced from `KeyInfo` via
     * `SecurityTokenReference` as required by the PVP profile.
     *
     * If the client cert or key path is missing, the XML is returned
     * unmodified. This enables local dev without certificates but means
     * production MUST verify the config is present at boot.
     *
     * Gotcha: DOMDocument normalises default namespaces on parse. If the
     * input uses `xmlns="..."` on the body content, the output may re-emit
     * it with a `default:` prefix on the ancestor. Downstream consumers
     * must use namespace-aware XPath, never string matching.
     */
    public function signEnvelope(string $xmlEnvelope): string
    {
        $certPath = config('pvp.client_cert_path');
        $keyPath = config('pvp.client_key_path');

        if (! $certPath || ! $keyPath || ! file_exists($certPath) || ! file_exists($keyPath)) {
            // Fail-closed in production: unsigned outbound messages would be
            // rejected by the ministry and unsigned responses would fail
            // non-repudiation archival. The silent-return path exists only
            // for local dev and CI where certs are not provisioned.
            if (app()->environment('production')) {
                throw new \RuntimeException('WS-Security: client certificate required in production but not configured');
            }

            Log::warning('WS-Security: client certificate not configured, envelope returned unsigned');

            return $xmlEnvelope;
        }

        try {
            $doc = SafeXml::loadDom($xmlEnvelope);

            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('soap', self::SOAP_NS);

            $envelope = $xpath->query('//soap:Envelope')->item(0);
            $body = $xpath->query('//soap:Body')->item(0);

            if (! $envelope instanceof \DOMElement || ! $body instanceof \DOMElement) {
                Log::error('WS-Security: SOAP structure not found in response');

                return $xmlEnvelope;
            }

            $body->setAttributeNS(self::WSU_NS, 'wsu:Id', 'Body');

            $header = $this->ensureSoapHeader($doc, $envelope, $body);
            $security = $this->createSecurityElement($doc);
            $header->appendChild($security);

            $tokenId = 'SecurityToken-' . bin2hex(random_bytes(8));
            $security->appendChild($this->createBinarySecurityToken($doc, $certPath, $tokenId));

            $dsig = new XMLSecurityDSig();
            $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
            $dsig->addReference(
                $body,
                XMLSecurityDSig::SHA256,
                ['http://www.w3.org/2001/10/xml-exc-c14n#'],
                ['force_uri' => true],
            );

            $keyContent = @file_get_contents($keyPath);
            if ($keyContent === false) {
                Log::error('WS-Security: cannot read client private key file', ['path' => $keyPath]);

                return $xmlEnvelope;
            }

            $privateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
            $privateKey->loadKey($keyContent, false);
            $dsig->sign($privateKey);

            $dsig->appendSignature($security);

            // xmlseclibs can emit ds:X509Data via add509Cert() but not the
            // wsse:SecurityTokenReference form the PVP profile requires, so we
            // graft the KeyInfo after the library has finished signing.
            $signatureElement = $security->getElementsByTagNameNS(self::DS_NS, 'Signature')->item(0);
            $signatureElement->appendChild($this->createKeyInfo($doc, $tokenId));

            return $doc->saveXML();
        } catch (\Exception $e) {
            Log::error('WS-Security: error signing response', [
                'error' => $e->getMessage(),
            ]);

            return $xmlEnvelope;
        }
    }

    /**
     * Parse the BinarySecurityToken and return both the PEM string and the
     * parsed OpenSSL resource.
     *
     * Why return both: the PEM string is needed to feed xmlseclibs' `loadKey`
     * (which expects textual PEM input), while downstream fingerprint checks
     * and cert operations work on the parsed resource. Parsing once here
     * avoids re-parsing the same bytes in every caller.
     */
    private function extractBinarySecurityToken(\DOMXPath $xpath): ?array
    {
        $tokenNodes = $xpath->query('//wsse:BinarySecurityToken');
        if ($tokenNodes->length === 0) {
            Log::warning('WS-Security: BinarySecurityToken not found in message');

            return null;
        }

        $tokenValue = trim($tokenNodes->item(0)->textContent);
        $certPem = "-----BEGIN CERTIFICATE-----\n" . $tokenValue . "\n-----END CERTIFICATE-----";

        $parsed = openssl_x509_read($certPem);
        if (! $parsed) {
            Log::error('WS-Security: invalid certificate in token');

            return null;
        }

        return ['pem' => $certPem, 'cert' => $parsed];
    }

    private function verifyCertificateFingerprint(\OpenSSLCertificate $tokenCert, string $ministryCertPath): bool
    {
        $ministryCertContent = @file_get_contents($ministryCertPath);
        if ($ministryCertContent === false) {
            Log::error('WS-Security: cannot read ministry certificate file', ['path' => $ministryCertPath]);

            return false;
        }

        $ministryCertParsed = openssl_x509_read($ministryCertContent);
        if (! $ministryCertParsed) {
            Log::error('WS-Security: ministry certificate unreadable');

            return false;
        }

        $tokenFingerprint = openssl_x509_fingerprint($tokenCert, 'sha256');
        $ministryFingerprint = openssl_x509_fingerprint($ministryCertParsed, 'sha256');

        if ($tokenFingerprint !== $ministryFingerprint) {
            Log::warning('WS-Security: certificate fingerprint mismatch', [
                'token_fingerprint' => $tokenFingerprint,
                'ministry_fingerprint' => $ministryFingerprint,
            ]);

            return false;
        }

        return true;
    }

    private function ensureSoapHeader(\DOMDocument $doc, \DOMElement $envelope, \DOMElement $body): \DOMElement
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('soap', self::SOAP_NS);
        $found = $xpath->query('soap:Header', $envelope);

        if ($found->length > 0) {
            return $found->item(0);
        }

        $header = $doc->createElementNS(self::SOAP_NS, 'soap:Header');
        $envelope->insertBefore($header, $body);

        return $header;
    }

    private function createSecurityElement(\DOMDocument $doc): \DOMElement
    {
        $security = $doc->createElementNS(self::WSSE_NS, 'wsse:Security');
        $security->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wsu', self::WSU_NS);
        $security->setAttributeNS(self::SOAP_NS, 'soap:mustUnderstand', '1');

        return $security;
    }

    private function createBinarySecurityToken(\DOMDocument $doc, string $certPath, string $tokenId): \DOMElement
    {
        $certContent = @file_get_contents($certPath);
        if ($certContent === false) {
            throw new \RuntimeException('Cannot read client certificate file: ' . $certPath);
        }

        $cert = openssl_x509_read($certContent);
        if (! $cert) {
            throw new \RuntimeException('Invalid X.509 certificate at: ' . $certPath);
        }

        $token = $doc->createElementNS(self::WSSE_NS, 'wsse:BinarySecurityToken', $this->extractCertificateBase64($cert));
        $token->setAttribute('EncodingType', self::BASE64_ENCODING_TYPE);
        $token->setAttribute('ValueType', self::X509_VALUE_TYPE);
        $token->setAttributeNS(self::WSU_NS, 'wsu:Id', $tokenId);

        return $token;
    }

    private function createKeyInfo(\DOMDocument $doc, string $tokenId): \DOMElement
    {
        $keyInfo = $doc->createElementNS(self::DS_NS, 'ds:KeyInfo');

        $stRef = $doc->createElementNS(self::WSSE_NS, 'wsse:SecurityTokenReference');
        $reference = $doc->createElementNS(self::WSSE_NS, 'wsse:Reference');
        $reference->setAttribute('URI', '#' . $tokenId);
        $reference->setAttribute('ValueType', self::X509_VALUE_TYPE);

        $stRef->appendChild($reference);
        $keyInfo->appendChild($stRef);

        return $keyInfo;
    }

    private function extractCertificateBase64(\OpenSSLCertificate $cert): string
    {
        if (! openssl_x509_export($cert, $certPem)) {
            throw new \RuntimeException('Failed to export X.509 certificate to PEM');
        }

        return str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"],
            '',
            $certPem
        );
    }
}
