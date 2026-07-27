<?php

namespace RRZE\Cris\Tests;

use RRZE\Cris\Cache;

/**
 * @covers \RRZE\Cris\Cache
 */
class CacheTest extends CrisTestCase
{
    private string $base = 'https://cris.fau.de/ws-cached/1.0/public/infoobject/123';

    /**
     * A forced (?flag=seednow) request must read the SAME transient as the
     * normal request — no duplicate "seednow" twin is created.
     */
    public function test_seednow_url_maps_to_same_key_as_normal_url(): void
    {
        Cache::set($this->base, 'DATA');

        $this->assertSame('DATA', Cache::get($this->base . '?flag=seednow'));
        $this->assertCount(1, $this->transients);
    }

    public function test_different_urls_use_different_keys(): void
    {
        Cache::set($this->base, 'A');

        $other = 'https://cris.fau.de/ws-cached/1.0/public/infoobject/999';
        $this->assertFalse(Cache::get($other));
    }

    /**
     * flush() must remove all plugin-tracked CRIS transients (data + timeout,
     * handled by delete_transient) without touching foreign transients.
     */
    public function test_flush_removes_only_tracked_cris_transients(): void
    {
        Cache::set($this->base, 'A');
        Cache::set('https://cris.fau.de/ws-cached/1.0/public/infoobject/456', 'B');
        $this->transients['some_other_plugin_transient'] = 'keep';

        $removed = Cache::flush();

        $this->assertSame(2, $removed);
        $this->assertArrayHasKey('some_other_plugin_transient', $this->transients);
        $this->assertCount(1, $this->transients);
    }
}
