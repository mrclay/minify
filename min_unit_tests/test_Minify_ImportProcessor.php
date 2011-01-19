<?php

require_once '_inc.php';

require_once 'Minify/ImportProcessor.php';

function test_Minify_ImportProcessor()
{
    global $thisDir;
    
    $linDir = $thisDir . '/_test_files/importProcessor';
    
    $testFilesUri = substr(
        realpath($thisDir . '/_test_files')
        ,strlen(realpath($_SERVER['DOCUMENT_ROOT']))
    );
    $testFilesUri = str_replace('\\', '/', $testFilesUri);
        
    $expected = str_replace(
        '%TEST_FILES_URI%'
        ,$testFilesUri
        ,file_get_contents($linDir . '/output.css')
    );
    
    $actual = Minify_ImportProcessor::process($linDir . '/input.css');

    $passed = assertTrue($expected === $actual, 'ImportProcessor');
    
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .strlen($actual). " bytes\n\n{$actual}\n\n";
        if (!$passed) {
            echo "---Expected: " .strlen($expected). " bytes\n\n{$expected}\n\n\n";
        }
    }
    
    $expectedIncludes = array (
        realpath($linDir .  '/input.css')
        ,realpath($linDir . '/adjacent.css')
        ,realpath($linDir . '/../css/styles.css')
        ,realpath($linDir . '/1/tv.css')
        ,realpath($linDir . '/1/adjacent.css')
    );
    
    $passed = assertTrue($expectedIncludes === Minify_ImportProcessor::$filesIncluded
        , 'ImportProcessor : included right files in right order');
}

test_Minify_ImportProcessor();