<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'on');

define('MINIFY_REWRITE_CSS_URLS', false);

require '../minify.php';
echo Minify::min(file_get_contents('test.html'), Minify::TYPE_HTML);
?>