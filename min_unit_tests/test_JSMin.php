<?php
require_once '_inc.php';

require_once 'JSMin.php';

function test_JSMin()
{
    global $thisDir;
   
    $src = file_get_contents($thisDir . '/_test_files/js/before.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/before.min.js');
    $minOutput = JSMin::minify($src);
    $passed = assertTrue($minExpected == $minOutput, 'JSMin : Overall');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
    }

    $src = file_get_contents($thisDir . '/_test_files/js/issue144.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/issue144.min.js');
    $minOutput = JSMin::minify($src);
    $passed = assertTrue($minExpected == $minOutput, 'JSMin : Handle "+ ++a" syntax (Issue 144)');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
    }

    if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
        $src = file_get_contents($thisDir . '/_test_files/js/issue132.js');
        $minExpected = file_get_contents($thisDir . '/_test_files/js/issue132.min.js');
        $minOutput = JSMin::minify($src);
        $passed = assertTrue($minExpected == $minOutput, 'JSMin : mbstring.func_overload shouldn\'t cause failure (Issue 132)');
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
            echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
            echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
        }
    }

    $src = file_get_contents($thisDir . '/_test_files/js/issue74.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/issue74.min.js');
    $minOutput = JSMin::minify($src);
    $passed = assertTrue($minExpected == $minOutput, 'JSMin : Quotes in RegExp literals (Issue 74)');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";

        // only test exceptions on this page
        test_JSMin_exception('"Hello'
                            ,'Unterminated String'
                            ,'JSMin_UnterminatedStringException'
                            ,"JSMin: Unterminated String at byte 6: \"Hello");
        test_JSMin_exception("return /regexp\n}"
                            ,'Unterminated RegExp'
                            ,'JSMin_UnterminatedRegExpException'
                            ,"JSMin: Unterminated RegExp at byte 15: /regexp\n");
        test_JSMin_exception("/* Comment "
                            ,'Unterminated Comment'
                            ,'JSMin_UnterminatedCommentException'
                            ,"JSMin: Unterminated comment at byte 11: /* Comment ");
    }
}

function test_JSMin_exception($js, $label, $expClass, $expMessage) {
    $eClass = $eMsg = '';
    try {
        JSMin::minify($js);
    } catch (Exception $e) {
        $eClass = get_class($e);
        $eMsg = $e->getMessage();
    }
    $passed = assertTrue($eClass === $expClass && $eMsg === $expMessage, 
        'JSMin : throw on ' . $label);
    if (! $passed && __FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n  ---" , $e, "\n\n";
    }
}

test_JSMin();
