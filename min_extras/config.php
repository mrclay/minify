<?php

// using same lib path and cache path specified in /min/config.php

require dirname(__FILE__) . '/../min/config.php';

$minifyCachePath = isset($min_cachePath) 
    ? $min_cachePath 
    : '';
