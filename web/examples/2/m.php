<?php

require '../../config.php';
require '_groupsSources.php';
require 'Minify.php';

if ($minifyCachePath) {
    Minify::useServerCache($minifyCachePath);
}

Minify::serve('Groups', array(
    'groups' => $groupsSources
    ,'setExpires' => time() + 86400 * 365
));
