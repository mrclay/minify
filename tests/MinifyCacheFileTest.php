<?php

namespace Minify\Test;

use Minify_Cache_File;

class MinifyCacheFileTest extends TestCase
{
    public function test1()
    {
        $data = str_repeat(md5(time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_cache_noLock';
        $cache = new Minify_Cache_File();

        $this->assertTestCache($cache, $id, $data);
    }

    /**
     * test with locks
     */
    public function test2()
    {
        $data = str_repeat(md5(time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_cache_withLock';
        $cache = new Minify_Cache_File('', true);

        $this->assertTestCache($cache, $id, $data);
    }
}
