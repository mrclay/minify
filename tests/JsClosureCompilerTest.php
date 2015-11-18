<?php

class JsClosureCompilerTest extends PHPUnit_Framework_TestCase {
	public function test1() {
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

		// Too many recent calls to Closure Compiler API to test.\n";
		$this->assertNotContains('Error(22): Too many compiles', $minOutput);

		$this->assertSame($minExpected, $minOutput, 'Minify_JS_ClosureCompiler : Overall');
	}

	public function test2() {
		$src = "function blah({ return 'blah';} ";
		try {
			Minify_JS_ClosureCompiler::minify($src);
			$this->fail('Expected exception not thrown');
		} catch (Exception $e) {
			$this->assertInstanceOf('Minify_JS_ClosureCompiler_Exception', $e, 'Throws Minify_JS_ClosureCompiler_Exception');
		}
	}

	// Test maximum byte size check (default)
	public function test3() {
		$fn = "(function() {})();";
		$src = str_repeat($fn, ceil(Minify_JS_ClosureCompiler::DEFAULT_MAX_BYTES / strlen($fn)));
		try {
			Minify_JS_ClosureCompiler::minify($src);
			$this->fail('Expected exception not thrown');
		} catch (Exception $e) {
			$this->assertInstanceOf('Minify_JS_ClosureCompiler_Exception', $e, 'Throws Minify_JS_ClosureCompiler_Exception');

			$expected = 'POST content larger than ' . Minify_JS_ClosureCompiler::DEFAULT_MAX_BYTES . ' bytes';
			$this->assertEquals($expected, $e->getMessage(), 'Message must tell how big maximum byte size is');
		}
	}

	// Test maximum byte size check (no limit)
	public function test4() {
		$src = "(function(){})();";
		$minOutput = Minify_JS_ClosureCompiler::minify($src, array(
			Minify_JS_ClosureCompiler::OPTION_MAX_BYTES => 0
		));

		$this->assertSame($src, $minOutput, 'With no limit set,  it should compile properly');
	}

	// Test maximum byte size check (custom)
	public function test5() {
		$src = "(function() {})();";
		$allowedBytes = 5;
		try {
			Minify_JS_ClosureCompiler::minify($src, array(
				Minify_JS_ClosureCompiler::OPTION_MAX_BYTES => $allowedBytes
			));
		} catch (Exception $e) {
			$this->assertInstanceOf('Minify_JS_ClosureCompiler_Exception', $e, 'Throws Minify_JS_ClosureCompiler_Exception');

			$expected = 'POST content larger than ' . $allowedBytes . ' bytes';
			$this->assertEquals($expected, $e->getMessage(), 'Message must tell how big maximum byte size is');
		}
	}

	// Test additional options passed to HTTP request
	public function test6() {
		$ecmascript5 = "[1,].length;";
		$exc = null;
		try {
			Minify_JS_ClosureCompiler::minify($ecmascript5);
			$this->fail('Expected exception not thrown');

		} catch (Exception $e) {
			$this->assertInstanceOf('Minify_JS_ClosureCompiler_Exception', $e, 'Throws Minify_JS_ClosureCompiler_Exception');
		}
	}

	public function test7() {
		$ecmascript5 = "[1,].length;";

		$minExpected = '1;';
		$minOutput = Minify_JS_ClosureCompiler::minify($ecmascript5, array(
			Minify_JS_ClosureCompiler::OPTION_ADDITIONAL_OPTIONS => array(
				'language' => 'ECMASCRIPT5'
			)
		));
		$this->assertSame($minExpected, $minOutput, 'Language option should make it compile');
	}
}
