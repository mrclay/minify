<?php

namespace Minify\Test;

use Minify_Cache_WinCache;

/**
 * @internal
 */
final class MinifyCacheWinCacheTest extends TestCase
{
    protected function setUp()
    {
        if (!\function_exists('wincache_ucache_info')) {
            static::markTestSkipped('To test this component, install WinCache extension');
        }
    }

    public function test1()
    {
        $data = \str_repeat(\md5(\time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_cache';

        $cache = new Minify_Cache_WinCache();
        $this->assertTestCache($cache, $id, $data);
    }
}
