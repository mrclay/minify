<?php

require '../../config.php';

require 'Minify.php';
require 'Minify/Cache/Memcache.php';

$mc = new Memcache;
if (! $mc->connect('localhost', 11211)) {
    die();
}
Minify::setCache(new Minify_Cache_Memcache($mc));

Minify::serve('Files', array(
    'files' => array(
        dirname(__FILE__) . '/before.js'
    )
    ,'maxAge' => 31536000 // 1 yr
));
