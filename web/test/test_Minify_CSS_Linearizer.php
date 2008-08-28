<?php
require_once '_inc.php';

require_once 'Minify/CSS/Linearizer.php';

function test_Minify_CSS_Linearizer()
{
    global $thisDir;
    
    $expected = file_get_contents($thisDir . '/_test_files/cssLinearizer/output.css');
    
    $actual = Minify_CSS_Linearizer::linearize($thisDir . '/_test_files/cssLinearizer/input.css');
    
    $passed = assertTrue($expected === $actual, 'Minify_CSS_Linearizer');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .strlen($actual). " bytes\n\n{$actual}\n\n";
        if (!$passed) {
            echo "---Expected: " .strlen($expected). " bytes\n\n{$expected}\n\n\n";
        }
    }
}

test_Minify_CSS_Linearizer();