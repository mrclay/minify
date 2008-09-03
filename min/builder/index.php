<?php

set_include_path(dirname(__FILE__) . '/../lib' . PATH_SEPARATOR . get_include_path());

require 'Minify.php';

Minify::setCache();

Minify::serve('Page', array(
    'file' => dirname(__FILE__) . '/_index.html'
    ,'minifyAll' => true
));
