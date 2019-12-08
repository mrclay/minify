<?php

// using same lib path and cache path specified in /min/config.php

require __DIR__ . '/../config.php';

$minifyCachePath = isset($min_cachePath)
    ? $min_cachePath
    : '';
