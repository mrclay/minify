<?php

class MinifyClosureCompilerTest extends TestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();
        Minify_ClosureCompiler::$isDebug = true;

        // To test more functionality, download a compiler.jar from
        // https://code.google.com/p/closure-compiler/wiki/BinaryDownloads,
        // put it under tests dir as 'compiler.jar'

        // set minimum necessary settings
        Minify_ClosureCompiler::$jarFile = __DIR__ . DIRECTORY_SEPARATOR . 'compiler.jar';
        Minify_ClosureCompiler::$tempDir = sys_get_temp_dir();
    }

    /*
     * Test minification w/o setting the necessary settings
     */
    public function test1()
    {
        Minify_ClosureCompiler::$jarFile = null;
        Minify_ClosureCompiler::$tempDir = null;
        try {
            Minify_ClosureCompiler::minify('');
            $this->fail();
        } catch (Exception $e) {
            $this->assertInstanceOf('Minify_ClosureCompiler_Exception', $e);
        }
    }

    /**
     * Test minification with the minimum necessary settings
     */
    public function test2()
    {
        $this->assertHasCompilerJar();
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
        $this->assertSame($minExpected, $minOutput, 'minimum necessary settings');
    }

    /**
     * Test minification with advanced compilation level
     */
    public function test3()
    {
        $this->assertHasCompilerJar();
        $src = "function unused() {};";
        $minExpected = '';
        $minOutput = Minify_ClosureCompiler::minify($src, array(
            Minify_ClosureCompiler::OPTION_COMPILATION_LEVEL => 'ADVANCED_OPTIMIZATIONS'
        ));
        $this->assertSame($minExpected, $minOutput, 'advanced optimizations');
    }

    protected function assertHasCompilerJar()
    {
        $this->assertFileExists(Minify_ClosureCompiler::$jarFile, "Have closure compiler compiler.jar");
    }
}