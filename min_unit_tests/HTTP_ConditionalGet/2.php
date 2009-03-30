<?php

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../min/lib'));
require 'HTTP/ConditionalGet.php';

// emulate regularly updating document
$every = 20;
$lastModified = round(time()/$every)*$every - $every;

$cg = new HTTP_ConditionalGet(array(
    'lastModifiedTime' => $lastModified
));
if ($cg->cacheIsValid) {
    $cg->sendHeaders();
    // we're done
    exit();
}

// generate content
$title = 'Last-Modified is known : add Content-Length';
$explain = '
<p>Here, like <a href="./">the first example</a>, we know the Last-Modified time,
but we also want to set the Content-Length to increase cacheability and allow
HTTP persistent connections. Instead of sending headers immediately, we first
generate our content, then use <code>setContentLength(strlen($content))</code>
to add the header. Then finally call <code>sendHeaders()</code> and send the
content.</p>
<p><strong>Note:</strong> This is not required if your PHP config buffers all
output and your script doesn\'t do any incremental flushing of the output
buffer. PHP will generally set Content-Length for you if it can.</p>
<p>This script emulates a document that changes every ' .$every. ' seconds.
<br>This is version: ' . date('r', $lastModified) . '</p>
';

require '_include.php';
$content = get_content(array(
    'title' => $title
    ,'explain' => $explain
));

$cg->setContentLength(strlen($content));
$cg->sendHeaders();
send_slowly($content);

