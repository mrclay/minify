<?php
require_once '_inc.php';

require_once 'Minify.php';

function test_Lines()
{
    global $thisDir;
    
    Minify::serve('Files', array(
        'debug' => true
        ,'files' => array(
            "{$thisDir}/_test_files/minify/email.js"
            ,"{$thisDir}/_test_files/minify/QueryString.js"
            ,"{$thisDir}/_test_files/js/before.js"
        )
    ));
    
    /*
    // build test file list
    $d = dir($cssPath);
    while (false !== ($entry = $d->read())) {
        if (preg_match('/^([\w\\-]+)\.css$/', $entry, $m)) {
            $list[] = $m[1];
        }
    }
    $d->close();
    
    foreach ($list as $item) {
    
        $options = ($item === 'paths') 
            ? array('prependRelativePath' => '../')
            : array();
        
        $src = file_get_contents($cssPath . "/{$item}.css");
        $minExpected = file_get_contents($cssPath . "/{$item}.min.css");
        $minOutput = Minify_CSS::minify($src, $options);
        $passed = assertTrue($minExpected === $minOutput, 'Minify_CSS : ' . $item);
        
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}\n\n";
            if (!$passed) {
                echo "---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}\n\n";
                echo "---Source: " .strlen($src). " bytes\n\n{$src}\n\n\n";    
            }
        }
    } */   
}

test_Lines();
