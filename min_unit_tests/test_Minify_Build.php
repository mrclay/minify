<?php
require_once '_inc.php';

function test_Minify_Build()
{
    global $thisDir;
    
    $file1 = $thisDir . '/_test_files/css/paths_prepend.css';
    $file2 = $thisDir . '/_test_files/css/styles.css';
    $maxTime = max(filemtime($file1), filemtime($file2));
    
    $b = new Minify_Build($file1);
    assertTrue($b->lastModified == filemtime($file1)
        ,'Minify_Build : single file path');
    
    $b = new Minify_Build(array($file1, $file2));
    assertTrue($maxTime == $b->lastModified
        ,'Minify_Build : multiple file paths');
    
    $b = new Minify_Build(array(
        $file1
        ,new Minify_Source(array('filepath' => $file2))
    ));
    
    assertTrue($maxTime == $b->lastModified
        ,'Minify_Build : file path and a Minify_Source');
    assertTrue($b->uri('/path') == "/path?{$maxTime}"
        ,'Minify_Build : uri() with no querystring');
    assertTrue($b->uri('/path?hello') == "/path?hello&amp;{$maxTime}"
        ,'Minify_Build : uri() with existing querystring');
}

test_Minify_Build();