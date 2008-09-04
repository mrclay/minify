<?php

require dirname(__FILE__) . '/../config.php';

set_include_path(dirname(__FILE__) . '/../lib' . PATH_SEPARATOR . get_include_path());

require 'Minify.php';

if (0 === stripos(PHP_OS, 'win')) {
    Minify::setDocRoot(); // we may be on IIS
}
Minify::setCache(isset($min_cachePath) ? $min_cachePath : null);
Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;

Minify::serve('Page', array(
    'file' => dirname(__FILE__) . '/_index.html'
    ,'minifyAll' => true
));
