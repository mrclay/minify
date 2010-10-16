<?php
require_once '_inc.php';

require_once 'Minify/Cache/Memcache.php';

function test_Minify_Cache_Memcache()
{
    $prefix = 'Minify_Cache_Memcache : ';
    $thisFileActive = (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME']));

    if (! function_exists('memcache_set')) {
        if ($thisFileActive) {
            echo "NOTE: {$prefix}PHP lacks memcache support\n";
        }
        return;
    }
    $mc = new Memcache;
    if (! @$mc->connect('localhost', 11211)) {
        if ($thisFileActive) {
            echo "NOTE: {$prefix}Could not connect to localhost:11211\n";
        }
        return;
    }
    
    $data = str_repeat(md5(time()) . 'í', 100); // 3400 bytes in UTF-8
    $id = 'Minify_test_memcache';
    $cache = new Minify_Cache_Memcache($mc);

    assertTrue(true === $cache->store($id, $data), $prefix . 'store');

    assertTrue(countBytes($data) === $cache->getSize($id), $prefix . 'getSize');

    assertTrue(true === $cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), $prefix . 'isValid');

    ob_start();
    $cache->display($id);
    $displayed = ob_get_contents();
    ob_end_clean();

    assertTrue($data === $displayed, $prefix . 'display');

    assertTrue($data === $cache->fetch($id), $prefix . 'fetch');

    if (function_exists('gzencode')) {
        $data = gzencode($data);
        $id .= ".gz";
        $cache->store($id, $data);
        assertTrue($data === $cache->fetch($id), $prefix . 'store/fetch gzencoded string');
    }
}

test_Minify_Cache_Memcache();