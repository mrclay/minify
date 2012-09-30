<?php
require_once '_inc.php';

function test_Minify_JS_ClosureCompiler()
{
    global $thisDir;
    
    $src = "
(function (window, undefined){
    function addOne(input) {
        return 1 + input;
    }
    window.addOne = addOne;
    window.undefined = undefined;
})(window);
    ";
    $minExpected = "(function(a,b){a.addOne=function(a){return 1+a};a.undefined=b})(window);";
    $minOutput = Minify_JS_ClosureCompiler::minify($src);
    if (false !== strpos($minOutput, 'Error(22): Too many compiles')) {
        echo "!NOTE: Too many recent calls to Closure Compiler API to test.\n";
        return;
    }


    $passed = assertTrue($minExpected == $minOutput, 'Minify_JS_ClosureCompiler : Overall');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
    }

    $src = "function blah({ return 'blah';} ";
    $exc = null;
    try {
        $minOutput = Minify_JS_ClosureCompiler::minify($src);
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }
}

test_Minify_JS_ClosureCompiler();
