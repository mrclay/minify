<?php
require_once '_inc.php';

function test_Minify_Cache_ZendPlatform()
{
    $prefix = 'Minify_Cache_ZendPlatform : ';
    if (! function_exists('output_cache_put')) {
        return;
    }
    $data = str_repeat(md5(time()) . 'í', 100); // 3400 bytes in UTF-8
    $id = 'Minify_test_cache';

    $cache = new Minify_Cache_ZendPlatform();

    assertTrue(true === $cache->store($id, $data), $prefix . 'store');

    assertTrue(countBytes($data) === $cache->getSize($id), $prefix . 'getSize');

    assertTrue(true === $cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), $prefix . 'isValid');

    ob_start();
    $cache->display($id);
    $displayed = ob_get_contents();
    ob_end_clean();

    assertTrue($data === $displayed, $prefix . 'display');

    assertTrue($data === $cache->fetch($id), $prefix . 'fetch');
}

test_Minify_Cache_ZendPlatform();
