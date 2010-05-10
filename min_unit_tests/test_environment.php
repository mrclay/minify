<?php

//phpinfo(); exit();

if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
    // called directly
    if (isset($_GET['getOutputCompression'])) {
        echo (int)ini_get('zlib.output_compression');
        exit();
    }
    if (isset($_GET['hello'])) {
        // try to disable (may not work)
        ini_set('zlib.output_compression', '0');
        $type = ($_GET['hello'] == 'js')
            ? 'application/x-javascript'
            : "text/{$_GET['hello']}";
        header("Content-Type: {$type}");
        echo 'World!';
        exit();
    }
}

require_once '_inc.php';

function test_environment()
{
    global $thisDir;

    // check DOCROOT
    $noSlash = assertTrue(
        0 === preg_match('@[\\\\/]$@', $_SERVER['DOCUMENT_ROOT'])
        ,'environment : DOCUMENT_ROOT should not end in trailing slash'
    );
    $isRealPath = assertTrue(false !== realpath($_SERVER['DOCUMENT_ROOT'])
        ,'environment : DOCUMENT_ROOT should pass realpath()'
    );
    $containsThisFile = assertTrue(
        0 === strpos(realpath(__FILE__), realpath($_SERVER['DOCUMENT_ROOT']))
        ,'environment : DOCUMENT_ROOT should contain this test file'
    );
    if (! $noSlash || ! $isRealPath || ! $containsThisFile) {
        echo "\nDOCUMENT_ROOT is set to: '{$_SERVER['DOCUMENT_ROOT']}'. If you "
           . "cannot modify this, consider setting \$min_documentRoot in config.php\n\n";
    }
    if (isset($_SERVER['SUBDOMAIN_DOCUMENT_ROOT'])) {
        echo "\n!NOTE: environment : \$_SERVER['SUBDOMAIN_DOCUMENT_ROOT'] is set. "
           . "You may need to set \$min_documentRoot to this in config.php\n";
    }
    if (realpath(__FILE__) !== realpath($_SERVER['DOCUMENT_ROOT'] . '/min_unit_tests/test_environment.php')) {
        echo "!NOTE: environment : /min_unit_tests/ is not directly inside DOCUMENT_ROOT\n";
    }

    $thisUrl = 'http://'
        . $_SERVER['HTTP_HOST'] // avoid redirects when SERVER_NAME doesn't match
        . ('80' === $_SERVER['SERVER_PORT'] ? '' : ":{$_SERVER['SERVER_PORT']}")
        . dirname($_SERVER['REQUEST_URI']) 
        . '/test_environment.php';
    
    $oc = @file_get_contents($thisUrl . '?getOutputCompression=1');
    
    if (false === $oc || ! preg_match('/^[01]$/', $oc)) {
        echo "!WARN: environment : Local HTTP request failed. Testing cannot continue.\n";
        return;
    }
    if ('1' === $oc) {
        echo "!WARN: environment : zlib.output_compression is enabled in php.ini"
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
            echo "!NOTE: environment : Server does not auto-encode arbitrary types. This\n"
               . "       may indicate that the auto-encoding is caused by Apache's \n"
               . "       AddOutputFilterByType.";
        }
    }
}

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

test_environment();
