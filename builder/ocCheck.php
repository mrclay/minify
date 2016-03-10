<?php
/**
 * AJAX checks for zlib.output_compression
 * 
 * @package Minify
 */

$app = (require __DIR__ . '/../bootstrap.php');
/* @var \Minify\App $app */

$_oc = ini_get('zlib.output_compression');
 
// allow access only if builder is enabled
if (!$app->config->enableBuilder) {
    header('Location: /');
    exit;
}

if ($app->env->get('hello')) {
    // echo 'World!'
    
    // try to prevent double encoding (may not have an effect)
    ini_set('zlib.output_compression', '0');
    
    HTTP_Encoder::$encodeToIe6  = true; // just in case
    $he = new HTTP_Encoder(array(
        'content' => str_repeat('0123456789', 500),
        'method' => 'deflate',
    ));
    $he->encode();
    $he->sendAll();

} else {
    // echo status "0" or "1"
    header('Content-Type: text/plain');
    echo (int)$_oc;
}
