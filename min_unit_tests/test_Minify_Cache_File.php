<?php
require_once '_inc.php';

require_once 'Minify/Cache/File.php';

function test_Minify_Cache_File()
{
    global $minifyCachePath;
    
    $data = str_repeat(md5(time()), 160);
    $id = 'Minify_test_cache_noLock';
    $prefix = 'Minify_Cache_File : ';
    
    $cache = new Minify_Cache_File($minifyCachePath);
    
    echo "NOTE: Minify_Cache_File : path is set to: '" . $cache->getPath() . "'.\n";
    
    assertTrue(true === $cache->store($id, $data), $prefix . 'store');
    
    assertTrue(strlen($data) === $cache->getSize($id), $prefix . 'getSize');
    
    assertTrue(true === $cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), $prefix . 'isValid');
    
    ob_start();
    $cache->display($id);
    $displayed = ob_get_contents();
    ob_end_clean();
    
    assertTrue($data === $displayed, $prefix . 'display');
    
    assertTrue($data === $cache->fetch($id), $prefix . 'fetch');
    
    // test with locks
    
    $id = 'Minify_test_cache_withLock';
    $cache = new Minify_Cache_File($minifyCachePath, true);
    
    assertTrue(true === $cache->store($id, $data), $prefix . 'store w/ lock');
    
    assertTrue(strlen($data) === $cache->getSize($id), $prefix . 'getSize');
    
    assertTrue(true === $cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), $prefix . 'isValid');
    
    ob_start();
    $cache->display($id);
    $displayed = ob_get_contents();
    ob_end_clean();
    
    assertTrue($data === $displayed, $prefix . 'display w/ lock');
    
    assertTrue($data === $cache->fetch($id), $prefix . 'fetch w/ lock');
}

test_Minify_Cache_File();