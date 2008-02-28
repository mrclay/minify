<?php

/**
 * Set $minifyCachePath to a PHP-writeable path to enable server-side caching
 * in all examples.  
 */
$minifyCachePath = '';

// get lib in include path
ini_set('include_path', 
    dirname(__FILE__) . '/../lib'
    . PATH_SEPARATOR . ini_get('include_path')
);

?>