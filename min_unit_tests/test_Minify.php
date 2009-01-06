<?php

// currently these only test serve() when passed the 'quiet' options

require_once '_inc.php';
require_once 'Minify.php';

function test_Minify()
{
    global $thisDir;

    $minifyTestPath = dirname(__FILE__) . '/_test_files/minify';
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
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'ETag' => "\"{$lastModified}pub\"",
            'Cache-Control' => 'max-age=1800, public, must-revalidate',
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
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";
        }
    }

    assertTrue(
        ! class_exists('HTTP_Encoder', false)
        && ! class_exists('Minify_CSS', false)
        && ! class_exists('Minify_Cache', false)
        ,'Encoder.php, CSS.php, Cache.php not loaded'
    );

    // Test minifying JS and serving with Expires header

    $content = preg_replace('/\\r\\n?/', "\n", file_get_contents($minifyTestPath . '/minified.js'));
    $lastModified = max(
        filemtime($minifyTestPath . '/email.js')
        ,filemtime($minifyTestPath . '/QueryString.js')
    );
    $expected = array(
        'success' => true
        ,'statusCode' => 200
        // Minify_Javascript always converts to \n line endings
        ,'content' => $content
        ,'headers' => array (
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $tomorrow),
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'ETag' => "\"{$lastModified}pub\"",
            'Cache-Control' => 'max-age=86400, public, must-revalidate',
            'Content-Length' => strlen($content),
            'Content-Type' => 'application/x-javascript; charset=UTF-8',
        )
    );
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
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
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
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        if (! $passed) {
            echo "\n---Output  : " .var_export($output, 1). "\n";
            echo "---Expected: " .var_export($expected, 1). "\n\n";
        }    
    }

    // Test minifying CSS and responding with Etag/Last-Modified

    Minify::setCache();

    // don't allow conditional headers
    unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

    $pathToWebTest = str_replace(
        DIRECTORY_SEPARATOR
        ,'/'
        ,substr(dirname(__FILE__), strlen(realpath($_SERVER['DOCUMENT_ROOT'])))
    );
    $expectedContent = str_replace(
        '%PATH_TO_WEB_TEST%'
        ,$pathToWebTest
        ,file_get_contents($minifyTestPath . '/minified.css')
    );

    $expected = array(
        'success' => true
        ,'statusCode' => 200
        ,'content' => $expectedContent
        ,'headers' => array (
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'ETag' => "\"{$lastModified}pub\"",
            'Cache-Control' => 'max-age=0, public, must-revalidate',
            'Content-Length' => strlen($expectedContent),
            'Content-Type' => 'text/css; charset=UTF-8',
        )
    );
    $output = Minify::serve('Files', array(
        'files' => array(
            $thisDir . '/_test_files/css/styles.css'
            ,$thisDir . '/_test_files/css/subsilver.css'
        )
        ,'quiet' => true
        ,'lastModifiedTime' => $lastModified
        ,'encodeOutput' => false
        ,'maxAge' => false
    ));

    $passed = assertTrue($expected === $output, 'Minify : CSS and Etag/Last-Modified');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";
        }
    }
}

test_Minify();
