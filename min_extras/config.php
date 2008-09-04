<?php

/**
 * Add the location of Minify's "lib" directory to the include_path. In
 * production this could be done via .htaccess or some other method.
 */
ini_set('include_path', 
    dirname(__FILE__) . '/../min/lib'
    . PATH_SEPARATOR . ini_get('include_path')
);

/**
 * Set $minifyCachePath to a PHP-writeable path to enable server-side caching
 * in all examples and tests.  
 */
$minifyCachePath = '';

