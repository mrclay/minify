<?php

ini_set('include_path', 
    dirname(__FILE__) . '/../../../lib'
    . PATH_SEPARATOR . ini_get('include_path')
);

require 'Minify.php';

// give an explicit path to avoid having to load Solar/Dir.php
Minify::useServerCache('C:/WINDOWS/Temp');

Minify::serve('Files', array(
    'files' => array(
        dirname(__FILE__) . '/before.js'
    )
    ,'setExpires' => $_SERVER['REQUEST_TIME'] + 31536000 // 1 yr
));
