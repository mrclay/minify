<?php

require_once '_inc.php';

function test_CSSmin()
{
    $files = glob(dirname(__FILE__) . '/_test_files/yuic/*.css');

    // some tests may exhaust memory/stack due to string size/PCRE
    $skip = array(
        //'dataurl-base64-doublequotes.css',
        //'dataurl-base64-noquotes.css',
        //'dataurl-base64-singlequotes.css',
    );

    $cssmin = new CSSmin();

    foreach ($files as $file) {
        if (! empty($skip) && in_array(basename($file), $skip)) {
            echo "INFO: CSSmin: skipping " . basename($file) . "\n";
            continue;
        }

        $src = file_get_contents($file);
        $minExpected = trim(file_get_contents($file . '.min'));
        $minOutput = trim($cssmin->run($src));
        
        $passed = assertTrue($minExpected == $minOutput, 'CSSmin : ' . basename($file));
        if (! $passed && __FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
            echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
            echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
        }
    }
}

test_CSSmin();
