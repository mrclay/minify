<?php

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

    private function assertTestCache(Minify_Cache_File $cache, $id, $data)
    {
        $this->assertTrue($cache->store($id, $data), "$id store");
        $this->assertEquals($cache->getSize($id), $this->countBytes($data), "$id getSize");
        $this->assertTrue($cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), "$id isValid");

        ob_start();
        $cache->display($id);
        $displayed = ob_get_contents();
        ob_end_clean();

        $this->assertSame($data, $displayed, "$id display");
        $this->assertEquals($data, $cache->fetch($id), "$id fetch");
    }
}
