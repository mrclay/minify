<?php

class MinifyClosureCompilerTest extends TestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();
        Minify_ClosureCompiler::$isDebug = true;

        // To test more functionality, download a compiler.jar from
        // https://github.com/google/closure-compiler#getting-started,
        // put it under tests dir as 'compiler.jar'

        // set minimum necessary settings
        Minify_ClosureCompiler::$jarFile = __DIR__ . DIRECTORY_SEPARATOR . 'compiler.jar';
        Minify_ClosureCompiler::$tempDir = sys_get_temp_dir();
    }

    /*
     * Test minimisation w/o setting the necessary settings
     */
    public function test1()
    {
        // clear params
        Minify_ClosureCompiler::$jarFile = null;
        Minify_ClosureCompiler::$tempDir = null;
        try {
            Minify_ClosureCompiler::minify('');
            $this->fail();
        } catch (Exception $e) {
            $this->assertInstanceOf('Minify_ClosureCompiler_Exception', $e);
        }
        // redo init to make other tests pass
        self::setupBeforeClass();
    }

    /**
     * Test minimisation with the minimum necessary settings
     */
    public function test2()
    {
        $this->assertHasJar();
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
     * Test minimisation with advanced compilation level
     */
    public function test3()
    {
        $this->assertHasJar();
        $src = "function unused() {};";
        $minExpected = '';
        $minOutput = Minify_ClosureCompiler::minify($src, array(
            Minify_ClosureCompiler::OPTION_COMPILATION_LEVEL => 'ADVANCED_OPTIMIZATIONS'
        ));
        $this->assertSame($minExpected, $minOutput, 'advanced optimizations');
    }

    protected function assertHasJar()
    {
        $this->assertNotEmpty(Minify_ClosureCompiler::$jarFile);
        try {
            $this->assertFileExists(Minify_ClosureCompiler::$jarFile, "Have closure compiler compiler.jar");
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }
}