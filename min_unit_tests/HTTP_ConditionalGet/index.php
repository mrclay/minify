<?php

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../min/lib'));
require 'HTTP/ConditionalGet.php';

// emulate regularly updating document
$every = 20;
$lastModified = round(time()/$every)*$every - $every;

$cg = new HTTP_ConditionalGet(array(
    'lastModifiedTime' => $lastModified
));
$cg->sendHeaders();
if ($cg->cacheIsValid) {
    // we're done
    exit();
}

$title = 'Last-Modified is known : simple usage';
$explain = '
<p>If your content has not changed since a certain timestamp, set this via the
the <code>lastModifiedTime</code> array key when instantiating HTTP_ConditionalGet.
You can immediately call the method <code>sendHeaders()</code> to set the
Last-Modified, ETag, and Cache-Control headers. The, if <code>cacheIsValid</code>
property is false, you echo the content.</p>
<p>This script emulates a document that changes every ' .$every. ' seconds.
<br>This is version: ' . date('r', $lastModified) . '</p>
';

require '_include.php';

echo send_slowly(get_content(array(
    'title' => $title
    ,'explain' => $explain
)));

