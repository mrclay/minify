<?php

namespace Minify\Test;

use Exception;
use Minify_ClosureCompiler;
use Minify_NailgunClosureCompiler;

class MinifyNailgunClosureCompilerTest extends TestCase
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
        Minify_NailgunClosureCompiler::$ngJarFile = __DIR__ . DIRECTORY_SEPARATOR . 'nailgun.jar';
    }

    /**
     * Test minimisation with the minimum necessary settings
     */
    public function test1()
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
        $minOutput = Minify_NailgunClosureCompiler::minify($src);
        $this->assertSame($minExpected, $minOutput, 'minimum necessary settings');
    }

    protected function assertHasJar()
    {
        $this->assertNotEmpty(Minify_ClosureCompiler::$jarFile);
        $this->assertNotEmpty(Minify_NailgunClosureCompiler::$ngJarFile);
        try {
            $this->assertFileExists(Minify_ClosureCompiler::$jarFile, "Have closure compiler compiler.jar");
            $this->assertFileExists(Minify_NailgunClosureCompiler::$ngJarFile, "Have nailgun.jar");
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }
}
