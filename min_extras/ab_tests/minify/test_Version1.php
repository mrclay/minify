<?php

require '../../config.php';

define('MINIFY_BASE_DIR', realpath(
    dirname(__FILE__) . '/../minify'
));
// set in /min/config.php
define('MINIFY_CACHE_DIR', $minifyCachePath);

Minify::serve('Version1');
