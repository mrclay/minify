<?php

/**
 * Note: All Minify class are E_STRICT except for Cache_Lite_File.
 */
error_reporting(E_ALL);

ini_set('display_errors', 1);

// setup
$cachePath = $_SERVER['DOCUMENT_ROOT'] . '/_cache/private';
ini_set('include_path', 
    '.' 
    . PATH_SEPARATOR . '../lib' 
    . PATH_SEPARATOR . ini_get('include_path')
);

require 'Minify.php';

// cache output files on filesystem
Minify::useServerCache($cachePath);

//Minify::$cacheUnencodedVersion = false;

// serve an array of files as one
Minify::serve('Files', array(
    dirname(__FILE__) . '/minify/email.js'
    ,dirname(__FILE__) . '/minify/QueryString.js'
));
