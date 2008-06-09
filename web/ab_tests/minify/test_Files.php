<?php

require '../../config.php';

require 'Minify.php';

// give an explicit path to avoid having to load Solar/Dir.php
Minify::useServerCache($minifyCachePath);

Minify::serve('Files', array(
    'files' => array(
        dirname(__FILE__) . '/before.js'
    )
    ,'setExpires' => $_SERVER['REQUEST_TIME'] + 31536000 // 1 yr
));
