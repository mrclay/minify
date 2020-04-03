<?php

namespace Minify\Test;

use Minify;
use Minify_Cache_Null;
use Minify_Controller_Files;
use Minify_Env;
use Minify_Source_Factory;

class MinifyTest extends TestCase
{
    public function test_Minify()
    {
        $minifyTestPath = self::$test_files . '/minify';

        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

        $tomorrow = $_SERVER['REQUEST_TIME'] + 86400;
        $lastModified = $_SERVER['REQUEST_TIME'] - 86400;

        // Test 304 response

        // simulate conditional headers
        $_SERVER['HTTP_IF_NONE_MATCH'] = "\"{$lastModified}pub\"";
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s \G\M\T', $lastModified);

        $minify = new Minify(new Minify_Cache_Null());
        $env = new Minify_Env(array(
            'server' => $_SERVER,
        ));
        $sourceFactory = new Minify_Source_Factory($env, array(), new Minify_Cache_Null());
        $controller = new Minify_Controller_Files($env, $sourceFactory);

        $output = $minify->serve($controller, array(
            'files' => self::$test_files . '/css/styles.css', // controller casts to array
            'quiet' => true,
            'lastModifiedTime' => $lastModified,
            'encodeOutput' => false,
        ));

        $expected = array(
            'success' => true,
            'statusCode' => 304,
            'content' => '',
            'headers' => array(
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + 1800),
                'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
                'ETag' => "\"pub{$lastModified}\"",
                'Cache-Control' => 'max-age=1800',
                '_responseCode' => 'HTTP/1.0 304 Not Modified',
            ),
        );

        $this->assertEquals($expected, $output, '304 response');

        $this->markTestIncomplete('minifier classes aren\'t loaded for 304s');
//        $this->assertTrue(!class_exists('Minify_CSSmin', false),
//            'Minify : minifier classes aren\'t loaded for 304s');

        // Test JS and Expires

        $content = preg_replace('/\\r\\n?/', "\n", file_get_contents($minifyTestPath . '/minified.js'));
        $lastModified = max(
            filemtime($minifyTestPath . '/email.js'),
            filemtime($minifyTestPath . '/QueryString.js')
        );
        $expected = array(
            'success' => true,
            'statusCode' => 200,
            // JSMin always converts to \n line endings
            'content' => $content,
            'headers' => array(
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $tomorrow),
                'Vary' => 'Accept-Encoding',
                'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
                'ETag' => "\"pub{$lastModified}\"",
                'Cache-Control' => 'max-age=86400',
                'Content-Length' => $this->countBytes($content),
                'Content-Type' => 'application/x-javascript; charset=utf-8',
            )
        );

        unset($_SERVER['HTTP_IF_NONE_MATCH']);
        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

        $env = new Minify_Env(array(
            'server' => $_SERVER,
        ));
        $sourceFactory = new Minify_Source_Factory($env, array(), new Minify_Cache_Null());
        $controller = new Minify_Controller_Files($env, $sourceFactory);
        $output = $minify->serve($controller, array(
            'files' => array(
                $minifyTestPath . '/email.js',
                $minifyTestPath . '/QueryString.js',
            ),
            'quiet' => true,
            'maxAge' => 86400,
            'encodeOutput' => false,
        ));

        $this->assertEquals($expected, $output, 'JS and Expires');

        // test for Issue 73
        $expected = ";function h(){}";
        $output = $minify->serve($controller, array(
            'files' => array(
                $minifyTestPath . '/issue73_1.js',
                $minifyTestPath . '/issue73_2.js',
            ),
            'quiet' => true,
            'encodeOutput' => false,
        ));
        $output = $output['content'];

        $this->assertEquals($expected, $output, 'Issue 73');

        // test for Issue 89
        $expected = file_get_contents($minifyTestPath . '/issue89_out.min.css');
        $output = $minify->serve($controller, array(
            'files' => array(
                $minifyTestPath . '/issue89_1.css',
                $minifyTestPath . '/issue89_2.css',
            ),
            'quiet' => true,
            'encodeOutput' => false,
            'bubbleCssImports' => true,
        ));
        $output = $output['content'];

        $this->assertEquals($expected, $output, 'Issue 89 : bubbleCssImports');

        $output = $minify->serve($controller, array(
            'files' => array(
                $minifyTestPath . '/issue89_1.css',
                $minifyTestPath . '/issue89_2.css',
            ),
            'quiet' => true,
            'encodeOutput' => false,
        ));
        $output = $output['content'];

        $defaultOptions = $minify->getDefaultOptions();

        $this->assertEquals(0, strpos($output, $defaultOptions['importWarning']), 'Issue 89 : detect invalid imports');

        $output = $minify->serve($controller, array(
            'files' => array(
                $minifyTestPath . '/issue89_1.css',
            ),
            'quiet' => true,
            'encodeOutput' => false,
        ));
        $output = $output['content'];

        $this->assertFalse(
            strpos($output, $defaultOptions['importWarning']),
            'Issue 89 : don\'t warn about valid imports'
        );

        // Test Issue 132
        if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
            $output = $minify->serve($controller, array(
                'files' => array(__DIR__ . '/_test_files/js/issue132.js'),
                'quiet' => true,
                'encodeOutput' => false,
            ));

            $this->assertEquals(
                77,
                $output['headers']['Content-Length'],
                'Issue 132 : mbstring.func_overload shouldn\'t cause incorrect Content-Length'
            );
        }

        // Test minifying CSS and responding with Etag/Last-Modified

        // don't allow conditional headers
        unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

        $expectedContent = file_get_contents($minifyTestPath . '/minified.css');

        $expected = array(
            'success' => true,
            'statusCode' => 200,
            'content' => $expectedContent,
            'headers' => array(
                'Vary' => 'Accept-Encoding',
                'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
                'ETag' => "\"pub{$lastModified}\"",
                'Cache-Control' => 'max-age=0',
                'Content-Length' => $this->countBytes($expectedContent),
                'Content-Type' => 'text/css; charset=utf-8',
            )
        );

        $env = new Minify_Env(array(
            'server' => $_SERVER,
        ));
        $sourceFactory = new Minify_Source_Factory($env, array(), new Minify_Cache_Null());
        $controller = new Minify_Controller_Files($env, $sourceFactory);

        $output = $minify->serve($controller, array(
            'files' => array(
                self::$test_files . '/css/styles.css',
                self::$test_files . '/css/comments.css',
            ),
            'quiet' => true,
            'lastModifiedTime' => $lastModified,
            'encodeOutput' => false,
            'maxAge' => false,
        ));

        $this->assertEquals($expected, $output, 'CSS and Etag/Last-Modified');
    }
}
