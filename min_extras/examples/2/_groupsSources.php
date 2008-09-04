<?php

$base = realpath(dirname(__FILE__) . '/..');
$groupsSources = array(
    'js' => array(
        "{$base}/lib.js"
        ,"{$base}/test space.js"
    )
    ,'css' => array("{$base}/test.css")
);
unset($base);