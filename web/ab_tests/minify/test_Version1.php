<?php

require '../../config.php';

define('MINIFY_BASE_DIR', realpath(
    dirname(__FILE__) . '/../minify'
));
define('MINIFY_CACHE_DIR', $minifyCachePath);

require 'Minify.php';

Minify::serve('Version1');
