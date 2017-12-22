<?php

namespace Minify\Test;

use Exception;
use Minify_ClosureCompiler;

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
        $options = array(
            'compilation_level' => 'ADVANCED_OPTIMIZATIONS'
        );
        $minOutput = Minify_ClosureCompiler::minify($src, $options);
        $this->assertSame($minExpected, $minOutput, 'advanced optimizations');
    }

    /**
     * Test that closure compiler does not produce unneeded noise
     *
     * @see https://code.google.com/p/closure-compiler/issues/detail?id=513
     *
     * NOTE: this test does not actually cover it, result is manually verified.
     */
    public function test4()
    {
        $this->assertHasJar();

        $src = $this->getDataFile('bug-513.js');
        $minExpected = 'var a=4;';
        $minOutput = Minify_ClosureCompiler::minify($src);
        $this->assertSame($minExpected, $minOutput, 'advanced optimizations');
    }

    /**
     * Test that language_in parameter has effect.
     */
    public function testLanguageOptions()
    {
        $this->assertHasJar();

        $src = $this->getDataFile('js/jscomp.polyfill.js');
        $exp = $this->getDataFile('js/jscomp.polyfill.min.js');
        $options = array(
            'language_in' => 'ECMASCRIPT3',
        );

        $res = Minify_ClosureCompiler::minify($src, $options);
        $this->assertSame($exp, $res);

        $options = array(
            'language_in' => 'ECMASCRIPT6',
        );
        $exp = $this->getDataFile('js/jscomp.polyfilled.min.js');
        $res = Minify_ClosureCompiler::minify($src, $options);
        $this->assertSame($exp, $res);
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
