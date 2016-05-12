<?php
/**
 * Reports server info useful in configuring the options $min_documentRoot, $min_symlinks,
 * and $min_serveOptions['minApp']['allowDirs'].
 *
 * Change to true to expose this info.
 */
$enabled = true;

///////////////////////

if (!$enabled) {
    die('Set $enabled to true to see server info.');
}

function assertTrue($test, $message) {
    if (!$test) {
        echo "Warning: $message\n";
    }
    return (bool)$test;
}

header('Content-Type: text/plain');

$file = __FILE__;
echo <<<EOD
__FILE__        : $file
SCRIPT_FILENAME : {$_SERVER['SCRIPT_FILENAME']}
DOCUMENT_ROOT   : {$_SERVER['DOCUMENT_ROOT']}
SCRIPT_NAME     : {$_SERVER['SCRIPT_NAME']}
REQUEST_URI     : {$_SERVER['REQUEST_URI']}


EOD;

$noSlash = assertTrue(
    0 === preg_match('@[\\\\/]$@', $_SERVER['DOCUMENT_ROOT']),
    'DOCUMENT_ROOT ends in trailing slash'
);

$isRealPath = assertTrue(
    false !== realpath($_SERVER['DOCUMENT_ROOT']),
    'DOCUMENT_ROOT fails realpath()'
);

$containsThisFile = assertTrue(
    0 === strpos(realpath(__FILE__), realpath($_SERVER['DOCUMENT_ROOT'])),
    'DOCUMENT_ROOT contains this test file'
);

if (! $noSlash || ! $isRealPath || ! $containsThisFile) {
    echo "If you cannot modify DOCUMENT_ROOT, consider setting \$min_documentRoot in config.php\n";
}

assertTrue(
    empty($_SERVER['SUBDOMAIN_DOCUMENT_ROOT']),
    "\$_SERVER['SUBDOMAIN_DOCUMENT_ROOT'] is set. You may want to set \$min_documentRoot to this in config.php"
);

assertTrue(
    realpath(__FILE__) === realpath($_SERVER['DOCUMENT_ROOT'] . '/min/server-info.php'),
    "/min/ is not directly inside DOCUMENT_ROOT."
);

// TODO: rework this
/*
function _test_environment_getHello($url)
{
    $fp = fopen($url, 'r', false, stream_context_create(array(
        'http' => array(
            'method' => "GET",
            'timeout' => '10',
            'header' => "Accept-Encoding: deflate, gzip\r\n",
        )
    )));
    $meta = stream_get_meta_data($fp);
    $encoding = '';
    $length = 0;
    foreach ($meta['wrapper_data'] as $i => $header) {
        if (preg_match('@^Content-Length:\\s*(\\d+)$@i', $header, $m)) {
            $length = $m[1];
        } elseif (preg_match('@^Content-Encoding:\\s*(\\S+)$@i', $header, $m)) {
            if ($m[1] !== 'identity') {
                $encoding = $m[1];
            }
        }
    }
    $streamContents = stream_get_contents($fp);
    fclose($fp);

    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        if ($length != 6) {
            echo "\nReturned content should be 6 bytes and not HTTP encoded.\n"
                . "Headers returned by: {$url}\n\n";
            var_export($meta['wrapper_data']);
            echo "\n\n";
        }
    }

    return array(
        'length' => $length
    ,'encoding' => $encoding
    ,'bytes' => $streamContents
    );
}

$thisUrl = 'http://'
    . $_SERVER['HTTP_HOST'] // avoid redirects when SERVER_NAME doesn't match
    . ('80' === $_SERVER['SERVER_PORT'] ? '' : ":{$_SERVER['SERVER_PORT']}")
    . dirname($_SERVER['REQUEST_URI'])
    . '/test_environment.php';

$oc = @file_get_contents($thisUrl . '?getOutputCompression=1');

if (false === $oc || ! preg_match('/^[01]$/', $oc)) {
    echo "!---: environment : Local HTTP request failed. Testing cannot continue.\n";
    return;
}
if ('1' === $oc) {
    echo "!---: environment : zlib.output_compression is enabled in php.ini"
        . " or .htaccess.\n";
}

$testJs = _test_environment_getHello($thisUrl . '?hello=js');
$passed = assertTrue(
    $testJs['length'] == 6
    ,'environment : PHP/server should not auto-encode application/x-javascript output'
);

$testCss = _test_environment_getHello($thisUrl . '?hello=css');
$passed = $passed && assertTrue(
        $testCss['length'] == 6
        ,'environment : PHP/server should not auto-encode text/css output'
    );

$testHtml = _test_environment_getHello($thisUrl . '?hello=html');
$passed = $passed && assertTrue(
        $testHtml['length'] == 6
        ,'environment : PHP/server should not auto-encode text/html output'
    );

if (! $passed) {
    $testFake = _test_environment_getHello($thisUrl . '?hello=faketype');
    if ($testFake['length'] == 6) {
        echo "      environment : Server does not auto-encode arbitrary types. This\n"
            . "                    may indicate that the auto-encoding is caused by Apache's\n"
            . "                    AddOutputFilterByType.";
    }
}
*/
