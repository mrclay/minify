<?php

require_once '_inc.php';

require_once 'CSSmin.php';

function test_CSSmin()
{
    $files = glob(dirname(__FILE__) . '/_test_files/yuic/*.css');

    // @todo determine why these crash. memroy exhaustion?
    $skip = array(
        'dataurl-base64-doublequotes.css',
        'dataurl-base64-noquotes.css',
        'dataurl-base64-singlequotes.css',
    );

    foreach ($files as $file) {
        if (in_array(basename($file), $skip)) {
            echo "INFO: CSSmin: skipping " . basename($file) . "\n";
            continue;
        }

        $cssmin = new CSSmin();

        $src = file_get_contents($file);
        $minExpected = file_get_contents($file . '.min');

//        echo "$file\n\n";
//        ob_flush();
//        flush();
        $minOutput = $cssmin->run($src, 100);
        $passed = assertTrue($minExpected == $minOutput, 'CSSmin : ' . basename($file));
        if (! $passed && __FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
            echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
            echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
        }

    }
}

test_CSSmin();
