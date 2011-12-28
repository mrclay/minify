<?php

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../min/lib'));
require 'HTTP/ConditionalGet.php';

// generate content first (not ideal)
// emulate regularly updating document
$every = 20;
$lastModified = round(time()/$every)*$every - $every;
$title = 'Last-Modified is unknown : use hash of content for ETag';
$explain = '
<p>When Last-Modified is unknown, you can still use ETags, but you need a short
string that is unique for that content. In the worst case, you have to generate
all the content first, <em>then</em> instantiate HTTP_ConditionalGet, setting
the array key <code>contentHash</code> to the output of a hash function of the
content. Since we have the full content, we might as well also use
<code>setContentLength(strlen($content))</code> in the case where we need to
send it.</p>
<p>This script emulates a document that changes every ' .$every. ' seconds.
<br>This is version: ' . date('r', $lastModified) . '</p>
';
require '_include.php';
$content = get_content(array(
    'title' => $title
    ,'explain' => $explain
));

$cg = new HTTP_ConditionalGet(array(
    'contentHash' => substr(md5($content), 7)
));
if ($cg->cacheIsValid) {
    $cg->sendHeaders();
    // we're done
    exit();
}
$cg->setContentLength(strlen($content));
$cg->sendHeaders();

send_slowly($content);

