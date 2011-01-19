<?php

require_once '_inc.php';
require_once 'Minify.php';

function test_Lines()
{
    global $thisDir;
    
    $exp = file_get_contents("{$thisDir}/_test_files/minify/lines_output.js");

    Minify::setCache(null); // no cache
    
    $ret = Minify::serve('Files', array(
        'debug' => true
        ,'quiet' => true
        ,'encodeOutput' => false
        ,'files' => array(
            "{$thisDir}/_test_files/minify/email.js"
            ,"{$thisDir}/_test_files/minify/lines_bugs.js"
            ,"{$thisDir}/_test_files/minify/QueryString.js"
            ,"{$thisDir}/_test_files/js/before.js"
        )
    ));
    
    $passed = assertTrue($exp === $ret['content'], 'Minify_Lines');
        
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .strlen($ret['content']). " bytes\n\n{$ret['content']}\n\n";
        if (!$passed) {
            echo "---Expected: " .strlen($exp). " bytes\n\n{$exp}\n\n\n";
        }
    }
}

test_Lines();