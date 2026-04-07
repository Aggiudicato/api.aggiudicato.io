<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WsSecurityService
{
    /**
     * Validate the WS-Security signature in the incoming SOAP header.
     */
    public function validateIncomingSignature(string $xmlContent): bool
    {
        $ministryCertPath = config('pvp.ministry_cert_path');

        if (! $ministryCertPath || ! file_exists($ministryCertPath)) {
            Log::warning('WS-Security: ministry certificate not configured, skipping validation');

            return true;
        }

        try {
            $doc = new \DOMDocument();
            $doc->loadXML($xmlContent);

            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
            $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

            $tokenCert = $this->extractBinarySecurityToken($xpath);
            if (! $tokenCert) {
                return false;
            }

            if (! $this->verifyCertificateFingerprint($tokenCert, $ministryCertPath)) {
                return false;
            }

            $publicKey = openssl_pkey_get_public($tokenCert);

            return $this->verifyXmlSignature($xpath, $publicKey);
        } catch (\Exception $e) {
            Log::error('WS-Security: validation exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sign a SOAP response with WS-Security header.
     */
    public function signResponse(string $xmlResponse): string
    {
        $certPath = config('pvp.client_cert_path');
        $keyPath = config('pvp.client_key_path');

        if (! $certPath || ! $keyPath || ! file_exists($certPath) || ! file_exists($keyPath)) {
            Log::warning('WS-Security: client certificate not configured, response unsigned');

            return $xmlResponse;
        }

        try {
            $doc = new \DOMDocument();
            $doc->loadXML($xmlResponse);

            $certContent = file_get_contents($certPath);
            $keyContent = file_get_contents($keyPath);

            $cert = openssl_x509_read($certContent);
            $privateKey = openssl_pkey_get_private($keyContent);

            if (! $cert || ! $privateKey) {
                Log::error('WS-Security: cannot read client certificate/key');

                return $xmlResponse;
            }

            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

            $envelope = $xpath->query('//soap:Envelope')->item(0);
            $body = $xpath->query('//soap:Body')->item(0);

            if (! $envelope || ! $body) {
                Log::error('WS-Security: SOAP structure not found in response');

                return $xmlResponse;
            }

            $body->setAttributeNS(
                'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd',
                'wsu:Id',
                'Body'
            );

            $certBase64 = $this->extractCertificateBase64($cert);
            $bodyDigest = $this->computeBodyDigest($body);
            $signedInfoXml = $this->buildSignedInfoXml($bodyDigest);
            $signatureValue = $this->signData($signedInfoXml, $privateKey);
            $tokenId = 'SecurityToken-' . bin2hex(random_bytes(8));
            $securityHeaderXml = $this->buildSecurityHeaderXml($certBase64, $signedInfoXml, $signatureValue, $tokenId);

            $headerDoc = new \DOMDocument();
            $headerDoc->loadXML('<soap:Header xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">' . $securityHeaderXml . '</soap:Header>');

            $importedHeader = $doc->importNode($headerDoc->documentElement, true);
            $envelope->insertBefore($importedHeader, $body);

            return $doc->saveXML();
        } catch (\Exception $e) {
            Log::error('WS-Security: error signing response', [
                'error' => $e->getMessage(),
            ]);

            return $xmlResponse;
        }
    }

    // --- validateIncomingSignature helpers ---

    private function extractBinarySecurityToken(\DOMXPath $xpath): ?string
    {
        $tokenNodes = $xpath->query('//wsse:BinarySecurityToken');
        if ($tokenNodes->length === 0) {
            Log::warning('WS-Security: BinarySecurityToken not found in message');

            return null;
        }

        $tokenValue = $tokenNodes->item(0)->textContent;
        $certPem = "-----BEGIN CERTIFICATE-----\n" . $tokenValue . "\n-----END CERTIFICATE-----";

        $cert = openssl_x509_read($certPem);
        if (! $cert) {
            Log::error('WS-Security: invalid certificate in token');

            return null;
        }

        return $certPem;
    }

    private function verifyCertificateFingerprint(string $tokenCert, string $ministryCertPath): bool
    {
        $ministryCert = file_get_contents($ministryCertPath);
        $ministryCertParsed = openssl_x509_read($ministryCert);

        if (! $ministryCertParsed) {
            Log::error('WS-Security: ministry certificate unreadable');

            return false;
        }

        $tokenFingerprint = openssl_x509_fingerprint(openssl_x509_read($tokenCert), 'sha256');
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

    private function verifyXmlSignature(\DOMXPath $xpath, $publicKey): bool
    {
        $signatureNodes = $xpath->query('//ds:Signature');
        if ($signatureNodes->length === 0) {
            Log::warning('WS-Security: Signature element not found');

            return false;
        }

        $signatureNode = $signatureNodes->item(0);
        $signedInfoNodes = $xpath->query('ds:SignedInfo', $signatureNode);
        $signatureValueNodes = $xpath->query('ds:SignatureValue', $signatureNode);

        if ($signedInfoNodes->length === 0 || $signatureValueNodes->length === 0) {
            Log::warning('WS-Security: SignedInfo or SignatureValue missing');

            return false;
        }

        $canonicalizedSignedInfo = $signedInfoNodes->item(0)->C14N(true);
        $signatureValue = base64_decode($signatureValueNodes->item(0)->textContent);

        $result = openssl_verify($canonicalizedSignedInfo, $signatureValue, $publicKey, OPENSSL_ALGO_SHA256);

        if ($result === 1) {
            Log::info('WS-Security: signature valid');

            return true;
        }

        if ($result === 0) {
            Log::warning('WS-Security: signature invalid');
        } else {
            Log::error('WS-Security: signature verification error', [
                'openssl_error' => openssl_error_string(),
            ]);
        }

        return false;
    }

    // --- signResponse helpers ---

    private function extractCertificateBase64($cert): string
    {
        openssl_x509_export($cert, $certPem);

        return str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"],
            '',
            $certPem
        );
    }

    private function computeBodyDigest(\DOMElement $body): string
    {
        $canonicalBody = $body->C14N(true);

        return base64_encode(hash('sha256', $canonicalBody, true));
    }

    private function buildSignedInfoXml(string $bodyDigest): string
    {
        return '<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">'
            . '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>'
            . '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>'
            . '<ds:Reference URI="#Body">'
            . '<ds:Transforms>'
            . '<ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>'
            . '</ds:Transforms>'
            . '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'
            . '<ds:DigestValue>' . $bodyDigest . '</ds:DigestValue>'
            . '</ds:Reference>'
            . '</ds:SignedInfo>';
    }

    private function signData(string $signedInfoXml, $privateKey): string
    {
        $signedInfoDoc = new \DOMDocument();
        $signedInfoDoc->loadXML($signedInfoXml);
        $canonicalSignedInfo = $signedInfoDoc->documentElement->C14N(true);

        openssl_sign($canonicalSignedInfo, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    private function buildSecurityHeaderXml(string $certBase64, string $signedInfoXml, string $signatureValue, string $tokenId): string
    {
        $wsseNs = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        return '<wsse:Security xmlns:wsse="' . $wsseNs . '" '
            . 'xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" '
            . 'soap:mustUnderstand="1">'
            . '<wsse:BinarySecurityToken '
            . 'EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" '
            . 'ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" '
            . 'wsu:Id="' . $tokenId . '">'
            . $certBase64
            . '</wsse:BinarySecurityToken>'
            . '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">'
            . $signedInfoXml
            . '<ds:SignatureValue>' . $signatureValue . '</ds:SignatureValue>'
            . '<ds:KeyInfo>'
            . '<wsse:SecurityTokenReference>'
            . '<wsse:Reference URI="#' . $tokenId . '" '
            . 'ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3"/>'
            . '</wsse:SecurityTokenReference>'
            . '</ds:KeyInfo>'
            . '</ds:Signature>'
            . '</wsse:Security>';
    }
}
