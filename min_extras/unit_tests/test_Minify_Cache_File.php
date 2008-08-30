<?php
require_once '_inc.php';

require_once 'Minify/Cache/File.php';

function test_Minify_Cache_File()
{
    $data = str_repeat(md5('testing'), 160);
    $id = 'Minify_test_cache';
    $prefix = 'Minify_Cache_File : ';
    
    $cache = new Minify_Cache_File();
    
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

test_Minify_Cache_File();