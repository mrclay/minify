<?php

// using same lib path and cache path specified in /min/config.php

require dirname(__FILE__) . '/../min/config.php';

require "$min_libPath/Minify/Loader.php";
Minify_Loader::register();

$minifyCachePath = isset($min_cachePath) 
    ? $min_cachePath 
    : '';
