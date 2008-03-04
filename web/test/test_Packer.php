<?php
require_once '_inc.php';

require_once 'Minify/Packer.php';

function test_Packer()
{
    global $thisDir;
    
    $src = file_get_contents($thisDir . '/_test_files/packer/before.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/packer/before.min.js');
    $minOutput = Minify_Packer::minify($src);
    
    $passed = assertTrue($minExpected === $minOutput, 'Minify_Packer');
    
    if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
        echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .strlen($src). " bytes\n\n{$src}\n\n\n";
    }    
}

test_Packer();