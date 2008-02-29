<?php

/**
 * This script will serve a single js/css file in this directory. Here we place
 * the front-end-controller logic in user code, then use the "Files" controller
 * to minify the file. Alternately, we could have created a custom controller
 * with the same logic and passed it to Minify::handleRequest().
 */

require '../../config.php';

/**
 * The Files controller only "knows" HTML, CSS, and JS files. Other files
 * would only be trim()ed and sent as plain/text.
 */
$serveExtensions = array('css', 'js');

// set HTTP Expires header if GET 'v' is sent
$setExpires = isset($_GET['v'])
    ? (time() + 86400 * 30)
    : null;

// serve
if (isset($_GET['f'])) {
    $filename = basename($_GET['f']); // remove any naughty bits
    $filenamePattern = '/[^\'"\\/\\\\]+\\.(?:' 
        .implode('|', $serveExtensions).   ')$/';
        
    if (preg_match($filenamePattern, $filename)
        && file_exists(dirname(__FILE__) . '/' . $filename)) {

        require 'Minify.php';
        
        if ($minifyCachePath) {
            Minify::useServerCache($minifyCachePath);
        }
        
        // The Files controller serves an array of files, but here we just
        // need one.
        Minify::serve('Files', array(
            dirname(__FILE__) . '/' . $filename
        ), array(
            'setExpires' => $setExpires
        ));
        exit();
    }
}

header("HTTP/1.0 404 Not Found");
echo "HTTP/1.0 404 Not Found";