<?php

namespace App\Support;

/**
 * Safe XML loading helpers that refuse DOCTYPE declarations.
 *
 * Centralises XXE hardening so every XML ingress in the app goes through
 * one audited code path. Rejecting DOCTYPE entirely is stronger than
 * disabling entity loading: it prevents billion-laughs, external entity
 * injection, and parameter entity tricks in a single check.
 */
final class SafeXml
{
    public static function loadDom(string $xml): \DOMDocument
    {
        self::rejectDoctype($xml);

        return self::withLibxmlErrors(function () use ($xml) {
            $doc = new \DOMDocument();
            $loaded = $doc->loadXML($xml, LIBXML_NONET);

            if (! $loaded) {
                throw new \RuntimeException('Invalid XML: '.self::formatErrors());
            }

            return $doc;
        });
    }

    public static function loadSimpleXml(string $xml): \SimpleXMLElement
    {
        self::rejectDoctype($xml);

        return self::withLibxmlErrors(function () use ($xml) {
            $simple = simplexml_load_string($xml, options: LIBXML_NONET);

            if ($simple === false) {
                throw new \RuntimeException('Invalid XML: '.self::formatErrors());
            }

            return $simple;
        });
    }

    private static function rejectDoctype(string $xml): void
    {
        // Intentionally crude: a false positive on "<!DOCTYPE" inside CDATA is
        // accepted as fail-closed. A smarter tokenizer would risk a bypass.
        if (preg_match('/<!DOCTYPE/i', $xml)) {
            throw new \RuntimeException('DOCTYPE not allowed in XML payload');
        }
    }

    private static function withLibxmlErrors(callable $fn)
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            return $fn();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function formatErrors(): string
    {
        return collect(libxml_get_errors())
            ->map(fn ($e) => trim($e->message))
            ->implode('; ') ?: 'unknown parse error';
    }
}
