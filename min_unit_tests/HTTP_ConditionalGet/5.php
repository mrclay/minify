<?php

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../min/lib'));
require 'HTTP/ConditionalGet.php';

// far expires
$cg = new HTTP_ConditionalGet(array(
    'maxAge' => 20
    ,'lastModifiedTime' => filemtime(__FILE__)
));
$cg->sendHeaders();

// generate, send content
$title = 'Last-Modified + Expires';
$explain = '
<p>Here we set a static "lastModifiedTime" and "maxAge" to 20. The browser
will consider this document fresh for 20 seconds, then revalidate its cache. After
the 304 response, the cache will be good for another 20 seconds. Unless you force
a reload, there will only be 304 responses for this page after the initial download.
';

require '_include.php';
echo get_content(array(
    'title' => $title
    ,'explain' => $explain
));

