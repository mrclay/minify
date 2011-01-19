<?php
require_once '_inc.php';

require_once 'Minify/HTML.php';
require_once 'Minify/CSS.php';
require_once 'JSMin.php';

function test_HTML()
{
    global $thisDir;
    
    $src = file_get_contents($thisDir . '/_test_files/html/before.html');
    $minExpected = file_get_contents($thisDir . '/_test_files/html/before.min.html');
    
    $time = microtime(true);
    $minOutput = Minify_HTML::minify($src, array(
        'cssMinifier' => array('Minify_CSS', 'minify')
        ,'jsMinifier' => array('JSMin', 'minify')
    ));
    $time = microtime(true) - $time;
    
    $passed = assertTrue($minExpected === $minOutput, 'Minify_HTML');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        if ($passed) {
            echo "\n---Source: ", strlen($src), " bytes\n"
               , "---Output: ", strlen($minOutput), " bytes (", round($time * 1000), " ms)\n\n{$minOutput}\n\n\n";
        } else {
            echo "\n---Output: ", strlen($minOutput), " bytes (", round($time * 1000), " ms)\n\n{$minOutput}\n\n"
               , "---Expected: ", strlen($minExpected), " bytes\n\n{$minExpected}\n\n"
               , "---Source: ", strlen($src), " bytes\n\n{$src}\n\n\n";
        }
    }
    
    $src = file_get_contents($thisDir . '/_test_files/html/before2.html');
    $minExpected = file_get_contents($thisDir . '/_test_files/html/before2.min.html');
    
    $time = microtime(true);
    $minOutput = Minify_HTML::minify($src, array(
        'cssMinifier' => array('Minify_CSS', 'minify')
        ,'jsMinifier' => array('JSMin', 'minify')
    ));
    $time = microtime(true) - $time;
    
    $passed = assertTrue($minExpected === $minOutput, 'Minify_HTML');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        if ($passed) {
            echo "\n---Source: ", strlen($src), " bytes\n"
               , "---Output: ", strlen($minOutput), " bytes (", round($time * 1000), " ms)\n\n{$minOutput}\n\n\n";
        } else {
            echo "\n---Output: ", strlen($minOutput), " bytes (", round($time * 1000), " ms)\n\n{$minOutput}\n\n"
               , "---Expected: ", strlen($minExpected), " bytes\n\n{$minExpected}\n\n"
               , "---Source: ", strlen($src), " bytes\n\n{$src}\n\n\n";
        }
    }
}

test_HTML();
