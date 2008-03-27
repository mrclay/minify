<?php

$base = realpath(dirname(__FILE__) . '/../1');
$groupsSources = array(
    'js' => array(
        "{$base}/jquery-1.2.3.js"
        ,"{$base}/test space.js"
    )
    ,'css' => array("{$base}/test.css")
);
unset($base);