<?php
require_once '_inc.php';

require_once 'Minify/HTML.php';
require_once 'Minify/CSS.php';
require_once 'Minify/Javascript.php';

function test_HTML()
{
    global $thisDir;
    
    $src = file_get_contents($thisDir . '/_test_files/html/before.html');
    $minExpected = file_get_contents($thisDir . '/_test_files/html/before.min.html');
    
    $minOutput = Minify_HTML::minify($src, array(
        'cssMinifier' => array('Minify_CSS', 'minify')
        ,'jsMinifier' => array('Minify_Javascript', 'minify')
    ));
    
    $passed = assertTrue($minExpected === $minOutput, 'Minify_HTML');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .strlen($src). " bytes\n\n{$src}\n\n\n";
    }    
}

test_HTML();
