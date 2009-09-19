<?php

require '../../config.php';


$mc = new Memcache;
if (! @$mc->connect('localhost', 11211)) {
    file_put_contents(
        dirname(__FILE__) . '/../memcached_stats.txt'
        ,"\nFailed connection.\n"
        ,FILE_APPEND
    );
    die();
}

if (0 == rand(0, 19)) {
    $stats = $mc->getStats();
    file_put_contents(
        dirname(__FILE__) . '/../memcached_stats.txt'
        ,$stats['curr_connections'] . "\n"
        ,FILE_APPEND
    );
}


Minify::setCache(new Minify_Cache_Memcache($mc));

Minify::serve('Files', array(
    'files' => array(
        dirname(__FILE__) . '/before.js'
    )
    ,'maxAge' => 31536000 // 1 yr
));
