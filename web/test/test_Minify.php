<?php

require '_inc.php';

/**
 * Note: All Minify classes are E_STRICT except for Cache_Lite_File.
 */
error_reporting(E_ALL);

require 'Minify.php';

// cache output files on filesystem
if ($minifyCachePath) {
    Minify::useServerCache($minifyCachePath);    
}

//Minify::$cacheUnencodedVersion = false;

// serve an array of files as one
Minify::serve('Files', array(
    $thisDir . '/minify/email.js'
    ,$thisDir . '/minify/QueryString.js'
));
