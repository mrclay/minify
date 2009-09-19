<?php
/**
 * File generator for mincache directories
 * 
 * DO NOT EDIT!
 * 
 * @package Minify
 */

function send404() {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}
 
define('MINIFY_MIN_DIR', realpath(dirname(__FILE__) . '/../min'));
define('MINCACHE_DIR'  , dirname(__FILE__));

// work on valid-looking URIs only
if (! isset($_GET['req']) 
    || ! preg_match('@^([fg])/([^/]+)\.m(css|js)$@', $_GET['req'], $m)) {
    send404();
}

$spec = array(
    'mode'   => $m[1]
    ,'group' => $m[2]
    ,'ext'   => $m[3]
    ,'plainFilename'    => "{$m[2]}.{$m[3]}"
    ,'deflatedFilename' => "{$m[2]}.{$m[3]}.zd"
    ,'typeMap'      => MINCACHE_DIR . '/' . $m[0]
    ,'plainFile'    => MINCACHE_DIR . "/{$m[1]}/{$m[2]}.{$m[3]}"
    ,'deflatedFile' => MINCACHE_DIR . "/{$m[1]}/{$m[2]}.{$m[3]}.zd"
    ,'ctype' => ($m[3] === 'js' ? 'application/x-javascript' : 'text/css')
);

// load configs
require MINIFY_MIN_DIR . '/config.php';
require MINCACHE_DIR   . '/config.php';

// setup include path
set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());
function min_autoload($name) {
    require str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
}
spl_autoload_register('min_autoload');

if ($min_documentRoot) {
    $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
} elseif (0 === stripos(PHP_OS, 'win')) {
    Minify::setDocRoot(); // IIS may need help
}

$min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;

$sources = array();

// URIs in "/f"
if ($spec['mode'] === 'f') {
    $files = explode(',', $spec['group']);
    foreach ($files as $file) {
        $file = realpath($mincache_servePath . '/' . $file . '.' . $spec['ext']);
        if ($file !== false
            && is_file($file)
            && dirname($file) === realpath($mincache_servePath)) {
            // file OK
            $sources[] = $file;
        } else {
            send404();
        }
    }
    $output = Minify::serve('Files', array(
        'files' => $sources
        ,'quiet' => true
        ,'encodeMethod' => ''
        ,'lastModifiedTime' => 0
    ));
    if (! $output['success']) {
        send404();
    }
}

$typeMap = "URI: {$spec['deflatedFilename']}\n"
         . "Content-Type: {$spec['ctype']}; qs=0.9\n"
         . "Content-Encoding: deflate\n"
         . "\n"
         . "URI: {$spec['plainFilename']}\n"
         . "Content-Type: {$spec['ctype']}; qs=0.6\n";

error_reporting(0);
if (false === file_put_contents($spec['plainFile'], $output['content'])
    || false === file_put_contents($spec['deflatedFile'], gzdeflate($output['content']))
    || false === file_put_contents($spec['typeMap'], $typeMap)) {
    echo "/* File writing failed. Your mincache directories /f and /g must be writable by PHP. */\n";
    exit();
}

unset($output['headers']['Last-Modified'], 
      $output['headers']['ETag']);
foreach ($output['headers'] as $name => $value) {
    header("{$name}: {$value}");
}

echo $output['content'];
