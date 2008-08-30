<?php

require '../../config.php';

require 'Minify.php';

// give an explicit path to avoid having to load Solar/Dir.php
Minify::setCache($minifyCachePath);

Minify::serve('Groups', array(
    'groups' => array(
        'test' => array(dirname(__FILE__) . '/before.js') 
    )
    ,'setExpires' => $_SERVER['REQUEST_TIME'] + 31536000 // 1 yr
));
