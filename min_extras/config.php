<?php

// using same lib path and cache path specified in /min/config.php

require dirname(__FILE__) . '/../min/config.php';

set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());

$minifyCachePath = isset($min_cachePath) 
    ? $min_cachePath 
    : '';

function min_autoload($name) {
    require str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
}
spl_autoload_register('min_autoload');
