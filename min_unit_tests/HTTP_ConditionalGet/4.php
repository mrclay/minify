<?php

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../min/lib'));
require 'HTTP/ConditionalGet.php';

// emulate regularly updating document
$every = 20;
$lastModified = round(time()/$every)*$every - $every;

require 'HTTP/Encoder.php';
list($enc,) = HTTP_Encoder::getAcceptedEncoding();

$cg = new HTTP_ConditionalGet(array(
    'lastModifiedTime' => $lastModified
    ,'encoding' => $enc
));
$cg->sendHeaders();
if ($cg->cacheIsValid) {
    // we're done
    exit();
}

// output encoded content

$title = 'ConditionalGet + Encoder';
$explain = '
<p>Using ConditionalGet and Encoder is straightforward. First impliment the
ConditionalGet, then if the cache is not valid, encode and send the content</p>
<p>This script emulates a document that changes every ' .$every. ' seconds.
<br>This is version: ' . date('r', $lastModified) . '</p>
';
require '_include.php';
$content = get_content(array(
    'title' => $title
    ,'explain' => $explain
));

$he = new HTTP_Encoder(array(
    'content' => get_content(array(
        'title' => $title
        ,'explain' => $explain
    ))
));
$he->encode();

// usually you would just $he->sendAll(), but here we want to emulate slow
// connection
$he->sendHeaders();
send_slowly($he->getContent());
