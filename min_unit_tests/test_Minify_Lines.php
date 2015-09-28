<?php

require_once '_inc.php';

function test_Lines()
{
    global $thisDir;
    
    $exp = file_get_contents("{$thisDir}/_test_files/minify/lines_output.js");

    $env = new Minify_Env();
    $sourceFactory = new Minify_Source_Factory($env, array(), new Minify_Cache_Null());
    $controller = new Minify_Controller_Files($env, $sourceFactory);
    $minify = new Minify(new Minify_Cache_Null());

    $ret = $minify->serve($controller, array(
        'debug' => true
        ,'quiet' => true
        ,'encodeOutput' => false
        ,'files' => array(
            "{$thisDir}/_test_files/js/before.js"
        )
    ));
    
    $passed = assertTrue($exp === $ret['content'], 'Minify_Lines');
        
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($ret['content']). " bytes\n\n{$ret['content']}\n\n";
        if (!$passed) {
            echo "---Expected: " .countBytes($exp). " bytes\n\n{$exp}\n\n\n";
        }
    }
}

test_Lines();