<?php

namespace Minify\Test;

use Minify_Cache_APCu;

class MinifyCacheAPCuTest extends TestCase
{
    public function setUp()
    {
        if (!function_exists('apcu_store')) {
            $this->markTestSkipped("To test this component, install APCu extension");
        }
        ini_set('apc.enable_cli', 1);
    }

    public function test1()
    {
        $data = str_repeat(md5(time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_cache';

        $cache = new Minify_Cache_APCu();
        $this->assertTestCache($cache, $id, $data);
    }
}
