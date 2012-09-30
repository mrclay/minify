<?php
require_once '_inc.php';

function test_JSMinPlus()
{
    global $thisDir;
    
    $src = file_get_contents($thisDir . '/_test_files/js/condcomm.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/condcomm.min_plus.js');
    
    $minOutput = JSMinPlus::minify($src);
    
    $passed = assertTrue($minExpected == $minOutput, 'JSMinPlus : Conditional Comments');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
    }
    
    return;
    
    
    $src = file_get_contents($thisDir . '/_test_files/js/before.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/before.min_plus.js');
    $minOutput = JSMinPlus::minify($src);
    
    $passed = assertTrue($minExpected == $minOutput, 'JSMinPlus : Overall');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
    }
    
    $src = file_get_contents($thisDir . '/_test_files/js/issue74.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/issue74.min_plus.js');
    $minOutput = JSMinPlus::minify($src);
    
    $passed = assertTrue($minExpected == $minOutput, 'JSMinPlus : Quotes in RegExp literals (Issue 74)');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
    }
}

test_JSMinPlus();
