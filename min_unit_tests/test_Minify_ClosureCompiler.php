<?php
require_once '_inc.php';

function test_Minify_ClosureCompiler()
{
    global $thisDir;
    Minify_ClosureCompiler::$isDebug = true;


    // --- Test minification w/o setting the necessary settings ---
    try {
        Minify_ClosureCompiler::minify('');
    } catch (Exception $e) {
        $exc = $e;
    }
    $passed = assertTrue(
        $exc instanceof Minify_ClosureCompiler_Exception
        , 'Minify_ClosureCompiler : Throws Minify_ClosureCompiler_Exception');

    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Message: " . var_export($exc->getMessage(), 1) . "\n\n\n";
    }
    
    $compiler_jar_path = __DIR__ . DIRECTORY_SEPARATOR . 'compiler.jar';
    if (is_file($compiler_jar_path)) {

        // set minimum necessary settings
        Minify_ClosureCompiler::$jarFile = $compiler_jar_path;
        Minify_ClosureCompiler::$tempDir = sys_get_temp_dir();
        

        // --- Test minification with the minimum necessary settings ---
        
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
        $minOutput = Minify_ClosureCompiler::minify($src);
        $passed = assertTrue($minExpected == $minOutput, 'Minify_ClosureCompiler : minimum necessary settings');
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
            echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
            echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
        }


        // --- Test minification with advanced compilation level ---

        $src = "function unused() {};";
        $minExpected = '';
        $minOutput = Minify_ClosureCompiler::minify($src, array(
            Minify_ClosureCompiler::OPTION_COMPILATION_LEVEL => 'ADVANCED_OPTIMIZATIONS'
        ));
        $passed = assertTrue($minExpected == $minOutput, 'Minify_ClosureCompiler : advanced optimizations');
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
            echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
            echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
        }

    } else {
        echo "      Minify_ClosureCompiler : To test more functionality, download a compiler.jar from\n";
        echo "                               https://code.google.com/p/closure-compiler/wiki/BinaryDownloads,\n";
        echo "                               put it under '${compiler_jar_path}',\n";
        echo "                               and make it readable by your webserver\n";
    }
}

test_Minify_ClosureCompiler();
