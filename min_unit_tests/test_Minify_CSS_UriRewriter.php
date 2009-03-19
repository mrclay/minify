<?php

require_once '_inc.php';

require_once 'Minify/CSS/UriRewriter.php';

function test_Minify_CSS_UriRewriter()
{
    global $thisDir;
    
    $in = file_get_contents($thisDir . '/_test_files/css_uriRewriter/in.css');
    $expected = file_get_contents($thisDir . '/_test_files/css_uriRewriter/exp.css');
    
    Minify_CSS_UriRewriter::$debugText = '';
    
    $actual = Minify_CSS_UriRewriter::rewrite(
        $in
        ,$thisDir . '/_test_files/css_uriRewriter' // currentDir
        ,$thisDir // use DOCUMENT_ROOT = '/full/path/to/min_unit_tests'
    );
    
    $passed = assertTrue($expected === $actual, 'Minify_CSS_UriRewriter');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Input:\n\n{$in}\n";
        echo "\n---Output: " .strlen($actual). " bytes\n\n{$actual}\n\n";
        if (!$passed) {
            echo "---Expected: " .strlen($expected). " bytes\n\n{$expected}\n\n\n";
        }
        
        // show debugging only when test run directly
        echo "--- Minify_CSS_UriRewriter::\$debugText\n\n"
            , Minify_CSS_UriRewriter::$debugText;
    }    
}

test_Minify_CSS_UriRewriter();