<?php

namespace Minify\Test;

use Memcache;
use Minify_Cache_Memcache;

/**
 * @internal
 */
final class MinifyCacheMemcacheTest extends TestCase
{
    /** @var Memcache */
    private $mc;

    public function test1()
    {
        $data = \str_repeat(\md5(\time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_memcache';
        $cache = new Minify_Cache_Memcache($this->mc);

        $this->assertTestCache($cache, $id, $data);
    }

    public function test2()
    {
        if (!\function_exists('gzencode')) {
            static::markTestSkipped('enable gzip extension to test this');
        }

        $data = \str_repeat(\md5(\time()) . 'í', 100); // 3400 bytes in UTF-8
        $id = 'Minify_test_memcache.gz';
        $cache = new Minify_Cache_Memcache($this->mc);

        $data = \gzencode($data);
        $this->assertTestCache($cache, $id, $data);
    }

    protected function setUp()
    {
        if (!\function_exists('memcache_set')) {
            static::markTestSkipped('To test this component, install memcache in PHP');
        }

        $this->mc = new Memcache();
        if (!$this->mc->connect('localhost', 11211)) {
            static::markTestSkipped('Memcache server not found on localhost:11211');
        }
    }
}
