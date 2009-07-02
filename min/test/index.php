<?php

if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
    // called directly
    if (isset($_GET['getOutputCompression'])) {
        echo (int)ini_get('zlib.output_compression');
        exit();
    }
    if (isset($_GET['hello'])) {
        // try to disable (may not work)
        ini_set('zlib.output_compression', '0');
        echo 'World!';
        exit();
    }
}

$origDocRoot = $_SERVER['DOCUMENT_ROOT'];

require_once '_inc.php';

header('Content-Type: text/html; charset=utf-8');

?><!doctype html>
<title>Minify server environment/config tests</title>
<style>
.assert {margin:1em 0 0}
.assert + .assert {margin:0}
.pass strong {color:#090}
.fail strong, .warn strong {color:#c00}
dt {margin-top:.5em; font-weight:bold}
</style>
<h1>Minify server environment/config tests</h1>

<h2>Document Root</h2>
<dl>
    <dt>Original <code>$_SERVER['DOCUMENT_ROOT']</code> (set by PHP/env)</dt>
    <dd><code><?= e($origDocRoot) ?></code></dd>
    <dt>Current <code>$_SERVER['DOCUMENT_ROOT']</code> (altered by Minify/your config.php)</dt>
    <dd><code><?= e($_SERVER['DOCUMENT_ROOT']) ?></code></dd>
    <dt><code>realpath($_SERVER['DOCUMENT_ROOT'])</code></dt>
    <dd><code><?= e(realpath($_SERVER['DOCUMENT_ROOT'])) ?></code></dd>
    <dt><code>__FILE__</code></dt>
    <dd><code><?= e(__FILE__) ?></code></dd>
</dl>
<?php

if (isset($_SERVER['SUBDOMAIN_DOCUMENT_ROOT'])) {
    echo "<p class='assert note'><strong>!NOTE</strong>: <code>\$_SERVER['SUBDOMAIN_DOCUMENT_ROOT']</code>"
       . " is set to " . e($_SERVER['SUBDOMAIN_DOCUMENT_ROOT']) . ". You may need to set"
       . " <code>\$min_documentRoot</code> to this in config.php.</p>";
}

$passed = assertTrue(
    0 === strpos(__FILE__, realpath($_SERVER['DOCUMENT_ROOT']))
    ,'<code>__FILE__</code> is within realpath of docroot'
);
if ($passed) {
    $thisPath = str_replace(
        '\\'
        ,'/'
        ,substr(__FILE__, strlen(realpath($_SERVER['DOCUMENT_ROOT'])))
    );
} else {
    // try HTTP requests anyway
    $thisPath = '/min/test/index.php';
}

if ($thisPath !== '/min/test/index.php') {
    echo "<p class='assert note'><strong>!NOTE</strong>: /min/ is not directly inside DOCUMENT_ROOT.</p>";
}

?>

<h2>HTTP Encoding</h2>
<?php

$thisUrl = 'http://'
    . $_SERVER['HTTP_HOST'] // avoid redirects when SERVER_NAME doesn't match
    . ('80' === $_SERVER['SERVER_PORT'] ? '' : ":{$_SERVER['SERVER_PORT']}")
    . $thisPath;

$oc = @file_get_contents($thisUrl . '?getOutputCompression=1');

if (false === $oc || ! preg_match('/^[01]$/', $oc)) {
    echo "<p class='assert warn'><strong>!WARN</strong>: Local HTTP request failed. Testing cannot continue.</p>";
} else {
    if ('1' === $oc) {
        echo "<p class='assert note'><strong>!NOTE</strong>: zlib.output_compression"
           . " was enabled by default, but this is OK if the next test passes.</p>";
    }

    $fp = fopen($thisUrl . '?hello=1', 'r', false, stream_context_create(array(
        'http' => array(
            'method' => "GET",
            'header' => "Accept-Encoding: deflate, gzip\r\n"
        )
    )));

    $meta = stream_get_meta_data($fp);

    $passed = true;
    foreach ($meta['wrapper_data'] as $i => $header) {
        if ((preg_match('@^Content-Length: (\\d+)$@i', $header, $m) && $m[1] !== '6')
            || preg_match('@^Content-Encoding:@i', $header, $m)
        ) {
            $passed = false;
            break;
        }
    }
    if ($passed && stream_get_contents($fp) !== 'World!') {
        $passed = false;
    }
    assertTrue(
        $passed
        ,'PHP/server can serve non-encoded content'
    );
    fclose($fp);

    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        if (! $passed) {
            echo "<p>Returned content should be 6 bytes and not HTTP encoded.<br>"
               . "Headers returned by: <code>" . h($thisUrl . '?hello=1') . "</code></p>"
               . "<pre><code>" . h(var_export($meta['wrapper_data'], 1)) . "</code></pre>";
        }
    }
}

if (assertTrue(function_exists('gzencode'), 'gzencode() exists')) {
    // test encode/decode
    $data = str_repeat(md5('testing'), 160);
    $gzipped = @gzencode($data, 6);
    assertTrue(is_string($gzipped) && strlen($gzipped) < strlen($data), 'gzip works');
    assertTrue(_gzdecode($gzipped) === $data, 'gzdecode works');
}

?>

<h2>Cache</h2>
<dl>
    <dt><code>$min_cachePath</code> in config.php</dt>
    <dd><code><?= e($min_cachePath) ?></code></dd>
    <dt>Resulting cache object</dt>
    <dd><pre><code><?= e($cache) ?></code></pre></dd>
</dl>
<?php

if ($min_cachePath === '') {
    echo "<p class='assert'><strong>!NOTE</strong>: Consider setting <code>\$min_cachePath</code> for best performance.</p>";
}

$data = str_repeat(md5('testing'), 160);
$id = 'Minify_test_cache';
assertTrue(true === $cache->store($id, $data), 'Cache store');
assertTrue(strlen($data) === $cache->getSize($id), 'Cache getSize');
assertTrue(true === $cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), 'Cache isValid');
ob_start();
$cache->display($id);
$displayed = ob_get_contents();
ob_end_clean();
assertTrue($data === $displayed, 'Cache display');
assertTrue($data === $cache->fetch($id), 'Cache fetch');
