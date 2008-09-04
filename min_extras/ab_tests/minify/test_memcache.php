<?php

$mc = new Memcache;
if (! @$mc->connect('localhost', 11211)) {
    file_put_contents(
        dirname(__FILE__) . '/../memcached_stats.txt'
        ,"Fail\n"
        ,FILE_APPEND | LOCK_EX
    );
    die();
}

$stats = $mc->getStats();
file_put_contents(
    dirname(__FILE__) . '/../memcached_stats.txt'
    ,$stats['curr_connections'] . "\n"
    ,FILE_APPEND | LOCK_EX
);

