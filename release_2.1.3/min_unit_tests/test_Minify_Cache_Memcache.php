<?php
require_once '_inc.php';

require_once 'Minify/Cache/Memcache.php';

function test_Minify_Cache_Memcache()
{
    $prefix = 'Minify_Cache_Memcache : ';
    if (! function_exists('memcache_set')) {
        return;
    }
    $mc = new Memcache;
    if (! @$mc->connect('localhost', 11211)) {
        return;
    }
    
    $data = str_repeat(md5('testing'), 160);
    $id = 'Minify_test_cache';
    
    $cache = new Minify_Cache_Memcache($mc);
    
    assertTrue(true === $cache->store($id, $data), $prefix . 'store');
    
    assertTrue(strlen($data) === $cache->getSize($id), $prefix . 'getSize');
    
    assertTrue(true === $cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), $prefix . 'isValid');
    
    ob_start();
    $cache->display($id);
    $displayed = ob_get_contents();
    ob_end_clean();
    
    assertTrue($data === $displayed, $prefix . 'display');
    
    assertTrue($data === $cache->fetch($id), $prefix . 'fetch');
}

test_Minify_Cache_Memcache();