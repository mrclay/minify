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


    // Test setting an unsupported HTTP client
    $exc = null;
    $httpClient = 'unknownHttpClient';
    try {
        $minOutput = Minify_JS_ClosureCompiler::minify('', array(
          Minify_JS_ClosureCompiler::OPTION_HTTP_CLIENT => $httpClient
        ));
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    assertTrue(
        $exc->getMessage() === 'HTTP Client "' . $httpClient . '" is not supported'
        , 'Minify_JS_ClosureCompiler : Message must tell that the given HTTP client is unsupported');

    $input = "(function() { var x = 'x'; })();";
    $minExpected = '(function(){})();';

    // Test fallback HTTP clients

    class NonAny_Minify_JS_ClosureCompiler extends Minify_JS_ClosureCompiler {
        protected function allowFileGetContents()
        {
            return false;
        }

        protected function allowCurl()
        {
            return false;
        }
    }

    $nonAnyClosureCompiler = new NonAny_Minify_JS_ClosureCompiler();
    $exc = null;
    try {
        $minOutput = $nonAnyClosureCompiler->min($input);
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    assertTrue(
        $exc->getMessage() === 'Could not make HTTP request: allow_url_open is false and cURL not available'
        , 'Minify_JS_ClosureCompiler : Message must tell why it did not work');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }

    // Test specifying the HTTP client: file_get_contents

    class NonFopen_Minify_JS_ClosureCompiler extends Minify_JS_ClosureCompiler {
        protected function allowFileGetContents()
        {
            return false;
        }
    }

    $exc = null;
    try {
        $nonFopenClosureCompiler = new NonFopen_Minify_JS_ClosureCompiler(array(
          Minify_JS_ClosureCompiler::OPTION_HTTP_CLIENT => Minify_JS_ClosureCompiler::HTTP_CLIENT_FOPEN
        ));
        $minOutput = $nonFopenClosureCompiler->min($input);
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    assertTrue(
        $exc->getMessage() === 'Could not make HTTP request: allow_url_fopen is disabled'
        , 'Minify_JS_ClosureCompiler : Message must tell why it did not work');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }


    class NonCurl_Minify_JS_ClosureCompiler extends Minify_JS_ClosureCompiler {
        protected function allowCurl()
        {
            return false;
        }
    }

    $exc = null;
    try {
        $nonCurlClosureCompiler = new NonCurl_Minify_JS_ClosureCompiler(array(
          Minify_JS_ClosureCompiler::OPTION_HTTP_CLIENT => Minify_JS_ClosureCompiler::HTTP_CLIENT_CURL
        ));
        $minOutput = $nonCurlClosureCompiler->min($input);
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_JS_ClosureCompiler_Exception
        , 'Minify_JS_ClosureCompiler : Throws Minify_JS_ClosureCompiler_Exception');
    assertTrue(
        $exc->getMessage() === 'Could not make HTTP request: cURL is not available'
        , 'Minify_JS_ClosureCompiler : Message must tell why it did not work');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }
}

test_Minify_JS_ClosureCompiler();
