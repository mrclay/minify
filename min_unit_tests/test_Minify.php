<?php

// currently these only test serve() when passed the 'quiet' options

require_once '_inc.php';
require_once 'Minify.php';

function test_Minify()
{
    global $thisDir;

    $minifyTestPath = dirname(__FILE__) . '/_test_files/minify';
    $thisFileActive = (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME']));
    $tomorrow = $_SERVER['REQUEST_TIME'] + 86400;
    $lastModified = $_SERVER['REQUEST_TIME'] - 86400;

    // Test 304 response

    // simulate conditional headers
    $_SERVER['HTTP_IF_NONE_MATCH'] = "\"{$lastModified}pub\"";
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s \G\M\T', $lastModified);

    $expected = array (
        'success' => true
        ,'statusCode' => 304
        ,'content' => '',
        'headers' => array(
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + 1800),
            'Vary' => 'Accept-Encoding',
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'ETag' => "\"pub{$lastModified}\"",
            'Cache-Control' => 'max-age=1800',
            '_responseCode' => 'HTTP/1.0 304 Not Modified',
        )
    );
    $output = Minify::serve('Files', array(
        'files' => $thisDir . '/_test_files/css/styles.css' // controller casts to array
        ,'quiet' => true
        ,'lastModifiedTime' => $lastModified
        ,'encodeOutput' => false
    ));
    $passed = assertTrue($expected === $output, 'Minify : 304 response');
    if ($thisFileActive) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";
        }
    }

    assertTrue(
        ! class_exists('Minify_CSS', false)
        && ! class_exists('Minify_Cache_File', false)
        ,'Minify : cache, and minifier classes aren\'t loaded for 304s'
    );

    // Test JS and Expires

    $content = preg_replace('/\\r\\n?/', "\n", file_get_contents($minifyTestPath . '/minified.js'));
    $lastModified = max(
        filemtime($minifyTestPath . '/email.js')
        ,filemtime($minifyTestPath . '/QueryString.js')
    );
    $expected = array(
        'success' => true
        ,'statusCode' => 200
        // JSMin always converts to \n line endings
        ,'content' => $content
        ,'headers' => array (
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $tomorrow),
            'Vary' => 'Accept-Encoding',
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'ETag' => "\"pub{$lastModified}\"",
            'Cache-Control' => 'max-age=86400',
            'Content-Length' => countBytes($content),
            'Content-Type' => 'application/x-javascript; charset=utf-8',
        )
    );
    unset($_SERVER['HTTP_IF_NONE_MATCH']);
    unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    $output = Minify::serve('Files', array(
        'files' => array(
            $minifyTestPath . '/email.js'
            ,$minifyTestPath . '/QueryString.js'
        )
        ,'quiet' => true
        ,'maxAge' => 86400
        ,'encodeOutput' => false
    ));
    
    $passed = assertTrue($expected === $output, 'Minify : JS and Expires');
    if ($thisFileActive) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";
        }    
    }
    
    // test for Issue 73
    Minify::setCache(null);
    
    $expected = ";function h(){}";
    $output = Minify::serve('Files', array(
        'files' => array(
            $minifyTestPath . '/issue73_1.js'
            ,$minifyTestPath . '/issue73_2.js'
        )
        ,'quiet' => true
        ,'encodeOutput' => false
    ));
    $output = $output['content'];
    
    $passed = assertTrue($expected === $output, 'Minify : Issue 73');
    if ($thisFileActive) {
        if (! $passed) {
            echo "\n---Output  : " .var_export($output, 1). "\n";
            echo "---Expected: " .var_export($expected, 1). "\n\n";
        }    
    }
    
    // test for Issue 89
    $expected = file_get_contents($minifyTestPath . '/issue89_out.min.css');
    $output = Minify::serve('Files', array(
        'files' => array(
            $minifyTestPath . '/issue89_1.css'
            ,$minifyTestPath . '/issue89_2.css'
        )
        ,'quiet' => true
        ,'encodeOutput' => false
        ,'bubbleCssImports' => true
    ));
    $output = $output['content'];
    $passed = assertTrue($expected === $output, 'Minify : Issue 89 : bubbleCssImports');
    if ($thisFileActive) {
        if (! $passed) {
            echo "\n---Output  : " .var_export($output, 1). "\n";
            echo "---Expected: " .var_export($expected, 1). "\n\n";
        }    
    }
    
    $output = Minify::serve('Files', array(
        'files' => array(
            $minifyTestPath . '/issue89_1.css'
            ,$minifyTestPath . '/issue89_2.css'
        )
        ,'quiet' => true
        ,'encodeOutput' => false
    ));
    $output = $output['content'];
    $passed = assertTrue(0 === strpos($output, Minify::$importWarning), 'Minify : Issue 89 : detect invalid imports');
    if ($thisFileActive) {
        if (! $passed) {
            echo "\n---Output  : " .var_export($output, 1). "\n";
            echo "---Expected: " .var_export($expected, 1). "\n\n";
        }    
    }
    
    $output = Minify::serve('Files', array(
        'files' => array(
            $minifyTestPath . '/issue89_1.css'
        )
        ,'quiet' => true
        ,'encodeOutput' => false
    ));
    $output = $output['content'];
    $passed = assertTrue(false === strpos($output, Minify::$importWarning), 'Minify : Issue 89 : don\'t warn about valid imports');
    if ($thisFileActive) {
        if (! $passed) {
            echo "\n---Output  : " .var_export($output, 1). "\n";
            echo "---Expected: " .var_export($expected, 1). "\n\n";
        }    
    }

    // Test Issue 132
    if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
        $output = Minify::serve('Files', array(
            'files' => array(dirname(__FILE__) . '/_test_files/js/issue132.js')
            ,'quiet' => true
            ,'encodeOutput' => false
        ));
        $passed = assertTrue($output['headers']['Content-Length'] == 77, 'Minify : Issue 132 : mbstring.func_overload shouldn\'t cause incorrect Content-Length');
    }

    // Test minifying CSS and responding with Etag/Last-Modified

    // don't allow conditional headers
    unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

    $expectedContent = file_get_contents($minifyTestPath . '/minified.css');

    $expected = array(
        'success' => true
        ,'statusCode' => 200
        ,'content' => $expectedContent
        ,'headers' => array (
            'Vary' => 'Accept-Encoding',
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'ETag' => "\"pub{$lastModified}\"",
            'Cache-Control' => 'max-age=0',
            'Content-Length' => countBytes($expectedContent),
            'Content-Type' => 'text/css; charset=utf-8',
        )
    );
    $output = Minify::serve('Files', array(
        'files' => array(
            $thisDir . '/_test_files/css/styles.css'
            ,$thisDir . '/_test_files/css/comments.css'
        )
        ,'quiet' => true
        ,'lastModifiedTime' => $lastModified
        ,'encodeOutput' => false
        ,'maxAge' => false
    ));

    $passed = assertTrue($expected === $output, 'Minify : CSS and Etag/Last-Modified');
    if ($thisFileActive) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";
        }
    }
}

test_Minify();
