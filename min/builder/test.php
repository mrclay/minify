<?php
exit;
/* currently unused.

// capture PHP's default setting (may get overridden in config
$_oc = ini_get('zlib.output_compression');

// allow access only if builder is enabled
require dirname(__FILE__) . '/../config.php';
if (! $min_enableBuilder) {
    exit;
}

if (isset($_GET['oc'])) {
    header('Content-Type: text/plain');
    echo (int)$_oc;

} elseif (isset($_GET['text']) && in_array($_GET['text'], array('js', 'css', 'fake'))) {
    ini_set('zlib.output_compression', '0');
    $type = ($_GET['text'] == 'js')
        ? 'application/x-javascript'
        : "text/{$_GET['text']}";
    header("Content-Type: {$type}");
    echo 'Hello';

} elseif (isset($_GET['docroot'])) {
    if (false === realpath($_SERVER['DOCUMENT_ROOT'])) {
        echo "<p class=topWarning><strong>realpath(DOCUMENT_ROOT) failed.</strong> You may need "
           . "to set \$min_documentRoot manually (hopefully realpath() is not "
           . "broken in your environment).</p>";
    }
    if (0 !== strpos(realpath(__FILE__), realpath($_SERVER['DOCUMENT_ROOT']))) {
        echo "<p class=topWarning><strong>DOCUMENT_ROOT doesn't contain this file.</strong> You may "
           . " need to set \$min_documentRoot manually</p>";
    }
    if (isset($_SERVER['SUBDOMAIN_DOCUMENT_ROOT'])) {
        echo "<p class=topNote><strong>\$_SERVER['SUBDOMAIN_DOCUMENT_ROOT'] is set.</strong> "
           . "You may need to set \$min_documentRoot to this in config.php</p>";
    }
    
}

//*/