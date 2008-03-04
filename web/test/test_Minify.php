<?php

require_once '_inc.php';
/**
 * Note: All Minify classes are E_STRICT except for Cache_Lite_File.
 */
error_reporting(E_ALL);
require_once 'Minify.php';

function test_Minify()
{
    global $thisDir;
    
    $minifyTestPath = dirname(__FILE__) . '/_test_files/minify';
    $tomorrow = time() + 86400;
    $lastModified = time() - 86400;
    
    // Test minifying JS and serving with Expires header
    
    $expected = array(
    	'success' => true
        ,'statusCode' => 200
        // Minify_Javascript always converts to \n line endings
        ,'content' => preg_replace('/\\r\\n?/', "\n", file_get_contents($minifyTestPath . '/minified.js'))
        ,'headers' => array (
            'Cache-Control' => 'public',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $tomorrow),
            'Content-Type' => 'application/x-javascript',
        )
    );
    $output = Minify::serve('Files', array(
        'files' => array(
            $minifyTestPath . '/email.js'
            ,$minifyTestPath . '/QueryString.js'
        )
        ,'quiet' => true
        ,'setExpires' => $tomorrow
        ,'encodeOutput' => false
    ));
    $passed = assertTrue($expected === $output, 'Minify : JS and Expires');
    
    if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";    
        }    
    }
    
    // Test minifying CSS and responding with Etag/Last-Modified
    
    // don't allow conditional headers
    unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    
    $expected = array(
    	'success' => true
        ,'statusCode' => 200	
    	,'content' => file_get_contents($minifyTestPath . '/minified.css')
        ,'headers' => array (
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'ETag' => "\"{$lastModified}pub\"",
            'Cache-Control' => 'max-age=0, public, must-revalidate',
            'Content-Type' => 'text/css',
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
    )); 
    $passed = assertTrue($expected === $output, 'Minify : CSS and Etag/Last-Modified');
    if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";    
        }
    }
    
    // Test 304 response
    
    // simulate conditional headers
    $_SERVER['HTTP_IF_NONE_MATCH'] = "\"{$lastModified}pub\"";
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s \G\M\T', $lastModified);
    
    $expected = array (
    	'success' => true
        ,'statusCode' => 304    
        ,'content' => '',
        'headers' => array()
    );
    $output = Minify::serve('Files', array(
        'files' => array(
            $thisDir . '/_test_files/css/styles.css'
        )
        ,'quiet' => true
        ,'lastModifiedTime' => $lastModified
        ,'encodeOutput' => false
    ));
    $passed = assertTrue($expected === $output, 'Minify : 304 response');
    if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
        echo "\nOutput: " .var_export($output, 1). "\n\n";
        if (! $passed) {
            echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";    
        }
    }
}

test_Minify();
