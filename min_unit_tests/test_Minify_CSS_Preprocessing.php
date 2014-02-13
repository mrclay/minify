<?php

require_once '_inc.php';

function test_CSS_Preprocessing()
{
    global $thisDir;

    /*** NOOP test ***/

    $in = file_get_contents($thisDir . '/_test_files/css_preProcessor/simple.css');
    $expected = $in;
    $noop = function($css) {
                return $css;
            };
    $actual = Minify_CSS::minify(
        $in,
        array(
            'compress' => false,
            Minify_CSS::OPTION_PREPROCESSOR => $noop
            )
    );
    
    $passed = assertTrue($expected === $actual, 'Minify_CSS_Preprocessing : noop');

    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Input:\n\n{$in}\n";
        echo "\n---Output: " .countBytes($actual). " bytes\n\n{$actual}\n\n";
        if (!$passed) {
            echo "---Expected: " .countBytes($expected). " bytes\n\n{$expected}\n\n\n";
        }
    }


    /*** static preprocessor output test ***/

    $in = '.unused { color: green; }';
    
    $const_return = function($css) {
                return '.expected { color: red; }';
            };
    $expected = $const_return(null);
    $actual = Minify_CSS::minify(
        $in,
        array(
            'compress' => false,
            Minify_CSS::OPTION_PREPROCESSOR => $const_return
            )
    );
    
    $passed = assertTrue($expected === $actual, 'Minify_CSS_Preprocessing : const_return');

    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Input:\n\n{$in}\n";
        echo "\n---Output: " .countBytes($actual). " bytes\n\n{$actual}\n\n";
        if (!$passed) {
            echo "---Expected: " .countBytes($expected). " bytes\n\n{$expected}\n\n\n";
        }
    }

    /*** prepend import statements preprocessor test ***/

    $in = file_get_contents($thisDir . '/_test_files/css_preProcessor/simple.css');
    $expected = file_get_contents($thisDir . '/_test_files/css_preProcessor/exp_simple_prepend_import.css');
    $actual = Minify_CSS::minify(
        $in,
        array(
            'compress' => false,
            Minify_CSS::OPTION_PREPROCESSOR => array('Minify_CSS_Preprocessors', 'prependImportStatements')
            )
    );
    
    $passed = assertTrue($expected === $actual, 'Minify_CSS_Preprocessing : prependImportStatements');

    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Input:\n\n{$in}\n";
        echo "\n---Output: " .countBytes($actual). " bytes\n\n{$actual}\n\n";
        if (!$passed) {
            echo "---Expected: " .countBytes($expected). " bytes\n\n{$expected}\n\n\n";
        }
    }
}

test_CSS_Preprocessing();