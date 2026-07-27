<?php

namespace RRZE\Cris;

defined('ABSPATH') || exit;

class Cache
{
    private static int|float $ttl = 6 * HOUR_IN_SECONDS;

    /**
     * Option that stores the list of transient keys this plugin has created.
     * It lets flush() invalidate every CRIS transient through the transient
     * API, so persistent object caches are cleared too (not just wp_options).
     */
    private const INDEX_OPTION = '_fau_cris_cache_keys';

    public static function set(string $url, string $ical): void
    {
        $cacheOption = self::buildOption($url);
        set_transient($cacheOption, $ical, self::$ttl);
        self::rememberKey($cacheOption);
    }

    public static function get(string $url)
    {
        return get_transient(self::buildOption($url));
    }

    public static function delete(string $url): bool
    {
        $cacheOption = self::buildOption($url);
        self::forgetKey($cacheOption);
        return delete_transient($cacheOption);
    }

    /**
     * Remove every CRIS transient tracked in the key index, going through the
     * transient API so external object caches are invalidated as well. Returns
     * the number of tracked keys that were removed.
     */
    public static function flush(): int
    {
        $keys = self::index();
        foreach ($keys as $cacheOption) {
            delete_transient($cacheOption);
        }
        delete_option(self::INDEX_OPTION);
        return count($keys);
    }

    /**
     * Build the transient key for a request URL. The "flag" query parameter
     * (e.g. ?flag=seednow) is stripped so a forced/server-reseed request maps
     * to the same local key as the normal request and never creates a
     * duplicate transient. The scheme is ignored (http/https collapse to one
     * key), matching the previous behaviour for normal URLs.
     */
    private static function buildOption(string $url): string
    {
        $parts = wp_parse_url($url);
        if (is_array($parts) && !empty($parts['host'])) {
            $query = '';
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $q);
                unset($q['flag']);
                if (!empty($q)) {
                    ksort($q);
                    $query = '?' . http_build_query($q);
                }
            }
            $key = '://' . $parts['host'] . ($parts['path'] ?? '') . $query;
        } else {
            $key = $url;
        }
        return 'cris_' . md5($key);
    }

    private static function index(): array
    {
        $index = get_option(self::INDEX_OPTION, []);
        return is_array($index) ? $index : [];
    }

    private static function rememberKey(string $cacheOption): void
    {
        $index = self::index();
        if (!in_array($cacheOption, $index, true)) {
            $index[] = $cacheOption;
            update_option(self::INDEX_OPTION, $index, false);
        }
    }

    private static function forgetKey(string $cacheOption): void
    {
        $index = self::index();
        $pos = array_search($cacheOption, $index, true);
        if ($pos !== false) {
            unset($index[$pos]);
            update_option(self::INDEX_OPTION, array_values($index), false);
        }
    }
}
