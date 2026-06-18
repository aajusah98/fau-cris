<?php

namespace RRZE\Cris;

defined('ABSPATH') || exit;

class XML
{
    public static function element(string $xml = ''): \WP_Error|\SimpleXMLElement
    {
        $error = self::isXML($xml);
        if (is_wp_error($error)) {
            return $error;
        }
        try {
            return new \SimpleXMLElement($xml);
        } catch (\Throwable $e) {
            return new \WP_Error('cris-xml-parse', $e->getMessage());
        }
    }

    public static function isXML(string $xml = ''): bool|\WP_Error
    {
        if ($xml === '') {
            return new \WP_Error(
                'cris-xml-empty',
                __('Empty XML payload.', 'fau-cris')
            );
        }

        // libxml's error buffer is process-global. Clear it before parsing so
        // diagnostics from earlier calls on the same request don't poison this
        // one, and clear it again after so we don't leak our diagnostics to
        // later calls.
        $prevUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc = new \DOMDocument('1.0', 'utf-8');
        $loaded = $doc->loadXML($xml);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        $fatals = array_filter(
            $errors,
            fn($e) => $e->level >= LIBXML_ERR_FATAL
        );

        if ($loaded && empty($fatals)) {
            return true;
        }

        $first = $errors[0] ?? null;
        $message = $first
            ? trim($first->message) . ' at line ' . $first->line
            : 'Invalid XML payload';

        return new \WP_Error('cris-xml-error', $message);
    }
}
