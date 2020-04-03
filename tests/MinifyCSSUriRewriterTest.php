<?php

namespace Minify\Test;

use Minify_CSS_UriRewriter;

class MinifyCSSUriRewriterTest extends TestCase
{
    public function setUp()
    {
        Minify_CSS_UriRewriter::$debugText = '';
    }

    public function test1()
    {
        $in = file_get_contents(self::$test_files . '/css_uriRewriter/in.css');
        $expected = file_get_contents(self::$test_files . '/css_uriRewriter/exp.css');
        $actual = Minify_CSS_UriRewriter::rewrite(
            $in,
            self::$test_files . '/css_uriRewriter' // currentDir
            ,
            self::$document_root // use DOCUMENT_ROOT = '/full/path/to/min_unit_tests'
        );

        $this->assertEquals($expected, $actual, 'rewrite, debug: ' . Minify_CSS_UriRewriter::$debugText);
    }

    public function test2()
    {
        $in = file_get_contents(self::$test_files . '/css_uriRewriter/in.css');
        $expected = file_get_contents(self::$test_files . '/css_uriRewriter/exp_prepend.css');
        $actual = Minify_CSS_UriRewriter::prepend($in, 'http://cnd.com/A/B/');

        $this->assertEquals($expected, $actual, 'prepend1, debug: ' . Minify_CSS_UriRewriter::$debugText);
    }

    public function test3()
    {
        $in = file_get_contents(self::$test_files . '/css_uriRewriter/in.css');
        $expected = file_get_contents(self::$test_files . '/css_uriRewriter/exp_prepend2.css');
        $actual = Minify_CSS_UriRewriter::prepend($in, '//cnd.com/A/B/');

        $this->assertEquals($expected, $actual, 'prepend2, debug: ' . Minify_CSS_UriRewriter::$debugText);
    }

    public function test4()
    {
        $in = '../../../../assets/skins/sam/sprite.png';
        $exp = '/yui/assets/skins/sam/sprite.png';
        $actual = Minify_CSS_UriRewriter::rewriteRelative(
            $in,
            'sf_root_dir\web\yui\menu\assets\skins\sam',
            'sf_root_dir\web'
        );

        $this->assertEquals($exp, $actual, 'Issue 99, debug: ' . Minify_CSS_UriRewriter::$debugText);
    }
}
