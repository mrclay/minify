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
        echo "!---: Minify_JS_ClosureCompiler : Too many recent calls to Closure Compiler API to test.\n";
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

    // Test maximum byte size check (default)
    $fn = "(function() {})();";
    $src = str_repeat($fn, ceil(Minify_JS_ClosureCompiler::DEFAULT_MAX_BYTES / strlen($fn)));
    $exc = null;
    try {
        $minOutput = Minify_JS_ClosureCompiler::minify($src);
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    assertTrue(
        $exc->getMessage() === 'POST content larger than ' . Minify_JS_ClosureCompiler::DEFAULT_MAX_BYTES . ' bytes'
        , 'Minify_JS_ClosureCompiler : Message must tell how big maximum byte size is');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }

    // Test maximum byte size check (no limit)
    $src = "(function(){})();";
    try {
        $minOutput = Minify_JS_ClosureCompiler::minify($src, array(
            Minify_JS_ClosureCompiler::OPTION_MAX_BYTES => 0
        ));
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $src === $minOutput
        , 'Minify_JS_ClosureCompiler : With no limit set,  it should compile properly');

    // Test maximum byte size check (custom)
    $src = "(function() {})();";
    $allowedBytes = 5;
    $exc = null;
    try {
        $minOutput = Minify_JS_ClosureCompiler::minify($src, array(
            Minify_JS_ClosureCompiler::OPTION_MAX_BYTES => $allowedBytes
        ));
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    assertTrue(
        $exc->getMessage() === 'POST content larger than ' . $allowedBytes . ' bytes'
        , 'Minify_JS_ClosureCompiler : Message must tell how big maximum byte size is');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }

    // Test additional options passed to HTTP request
    $ecmascript5 = "[1,].length;";
    $exc = null;
    try {
        $minOutput = Minify_JS_ClosureCompiler::minify($ecmascript5);
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }

    $minExpected = '1;';
    $minOutput = Minify_JS_ClosureCompiler::minify($ecmascript5, array(
        Minify_JS_ClosureCompiler::OPTION_ADDITIONAL_OPTIONS => array(
            'language' => 'ECMASCRIPT5'
            )
    ));
    $passed = assertTrue(
        $minOutput === $minExpected
        , 'Minify_JS_ClosureCompiler : Language option should make it compile');
}

test_Minify_JS_ClosureCompiler();
