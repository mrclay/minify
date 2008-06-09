<?php

ini_set('include_path', 
    dirname(__FILE__) . '/../../../lib'
    . PATH_SEPARATOR . ini_get('include_path')
);

define('MINIFY_BASE_DIR', realpath(
    dirname(__FILE__) . '/../minify'
));
define('MINIFY_CACHE_DIR', 'C:/xampp/tmp');

require 'Minify.php';

Minify::serve('Version1');
