<?php

require '../../config.php';

// set in /min/config.php
Minify::setCache($minifyCachePath);

Minify::serve('Groups', array(
    'groups' => array(
        'test' => array(dirname(__FILE__) . '/before.js') 
    )
    ,'maxAge' => 31536000 // 1 yr
));
