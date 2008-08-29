<?php
/**
 * The goal with this file is to benchmark serving the file doing the absolute
 * least operations possible. E.g. we know we'll have to check for the file, 
 * check its size and the mtimes of it and the src file.
 */

$src = realpath(dirname(__FILE__) . '/../minify/before.js');
$cached = realpath(dirname(__FILE__) . '/../type-map') . '/before.js.zd';

// clearstatcache() takes over 2ms on Athlon 64 X2 5600+! Avoid at all costs!
//clearstatcache();

filemtime($src);
file_exists($cached);
filemtime($cached);

header('Cache-Control: public, max-age=31536000');
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + (86400 * 365)));
header('Content-Type: application/x-javascript; charset=utf-8');
header('Content-Encoding: deflate');
header('Content-Length: ' . filesize($cached));
header('Vary: Accept-Encoding');

readfile($cached);