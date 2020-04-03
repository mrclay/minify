<?php

namespace Minify\Test;

use Minify_Cache_ZendPlatform;

class MinifyCacheZendPlatformTest extends TestCase
{
    public function setUp()
    {
        if (!function_exists('output_cache_put')) {
            // FIXME: be specific what to actually install
            $this->markTestSkipped("To test this component, install ZendPlatform");
        }
    }

    public function test1()
    {
        $data = str_repeat(md5(time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_cache';

        $cache = new Minify_Cache_ZendPlatform();
        $this->assertTestCache($cache, $id, $data);
    }
}
