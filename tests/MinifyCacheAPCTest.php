<?php

namespace Minify\Test;

use Minify_Cache_APC;

class MinifyCacheAPCTest extends TestCase
{
    public function setUp()
    {
        if (!function_exists('apc_store')) {
            // FIXME: is APCu extension ok too?
            $this->markTestSkipped("To test this component, install APC extension");
        }
    }

    public function test1()
    {
        $data = str_repeat(md5(time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_cache';

        $cache = new Minify_Cache_APC();
        $this->assertTestCache($cache, $id, $data);
    }
}
