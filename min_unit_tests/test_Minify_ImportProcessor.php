<?php

require_once '_inc.php';

require_once 'Minify/ImportProcessor.php';

function test_Minify_ImportProcessor()
{
    global $thisDir;
    
    $linDir = $thisDir . '/_test_files/importProcessor';
        
    $expected = file_get_contents($linDir . '/css/output.css');
    
    $actual = Minify_ImportProcessor::process($linDir . '/css/input.css');

    $passed = assertTrue($expected === $actual, 'ImportProcessor');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($actual). " bytes\n\n{$actual}\n\n";
        if (!$passed) {
            echo "---Expected: " .countBytes($expected). " bytes\n\n{$expected}\n\n\n";
        }
    }
    
    $expectedIncludes = array (
        realpath($linDir .  '/css/input.css')
        ,realpath($linDir . '/css/adjacent.css')
        ,realpath($linDir . '/../css/styles.css')
        ,realpath($linDir . '/css/1/tv.css')
        ,realpath($linDir . '/css/1/adjacent.css')
        ,realpath($linDir . '/lib/css/example.css')
    );
    
    $passed = assertTrue($expectedIncludes === Minify_ImportProcessor::$filesIncluded
        , 'ImportProcessor : included right files in right order');
}

test_Minify_ImportProcessor();