<?php

// allows putting /static anywhere as long as you put a boostrap.php in it
if (is_file(__DIR__ . '/bootstrap.php')) {
    $bootstrap_file = __DIR__ . '/bootstrap.php';
} else {
    $bootstrap_file = __DIR__ . '/../bootstrap.php';
}

$send_400 = function($content = 'Bad URL') {
    http_response_code(400);
    die($content);
};

$send_301 = function($url) {
    http_response_code(301);
    header("Cache-Control: max-age=31536000");
    header("Location: $url");
    exit;
};

$app = (require $bootstrap_file);
/* @var \Minify\App $app */

if (!$app->config->enableStatic) {
    die('Minify static serving is not enabled. Set $min_enableStatic = true; in config.php');
}

require __DIR__ . '/lib.php';

if (!is_writable(__DIR__)) {
    http_response_code(500);
    die('Directory is not writable.');
}

// parse request
// SCRIPT_NAME = /path/to/minify/static/gen.php
// REQUEST_URI = /path/to/minify/static/1467084520/b=path/to/minify&f=quick-test.js

// "/path/to/minify/static"
$root_uri = dirname($_SERVER['SCRIPT_NAME']);

// "/1467084520/b=path/to/minify&f=quick-test.js"
$uri = substr($_SERVER['REQUEST_URI'], strlen($root_uri));

if (!preg_match('~^/(\d+)/(.*)$~', $uri, $m)) {
    http_response_code(404);
    die('File not found');
}

// "1467084520"
$requested_cache_dir = $m[1];

// "b=path/to/minify&f=quick-test.js"
$query = $m[2];

// we basically want canonical querystrings because we make a file for each one.
// manual parsing is the only way to do this. The MinApp controller will validate
// these parameters anyway.
$get_params = array();
foreach (explode('&', $query) as $piece) {
    if (false === strpos($piece, '=')) {
        $send_400();
    }

    list($key, $value) = explode('=', $piece, 2);
    if (!in_array($key, array('f', 'g', 'b', 'z'))) {
        $send_400();
    }

    if (isset($get_params[$key])) {
        // already used
        $send_400();
    }

    if ($key === 'z' && !preg_match('~^\.(css|js)$~', $value, $m)) {
        $send_400();
    }

    $get_params[$key] = urldecode($value);
}

$cache_time = Minify\StaticService\get_cache_time();
if (!$cache_time) {
    http_response_code(500);
    die('Directory is not writable.');
}

$app->env = new Minify_Env(array(
    'get' => $get_params,
));
$ctrl = $app->controller;
$options = $app->serveOptions;
$sources = $ctrl->createConfiguration($options)->getSources();
if (!$sources) {
    http_response_code(404);
    die('File not found');
}
if ($sources[0]->getId() === 'id::missingFile') {
    $send_400("Bad URL: missing file");
}

// we need URL to end in appropriate extension
$type = $sources[0]->getContentType();
$ext = ($type === Minify::TYPE_JS) ? '.js' : '.css';
if (substr($query, - strlen($ext)) !== $ext) {
    $send_301("$root_uri/$cache_time/{$query}&z=$ext");
}

// fix the cache dir in the URL
if ($cache_time !== $requested_cache_dir) {
    $send_301("$root_uri/$cache_time/$query");
}

$content = $app->minify->combine($sources);

// save and send file
$file = __DIR__ . "/$cache_time/$query";
if (!is_dir(dirname($file))) {
    mkdir(dirname($file), 0777, true);
}

file_put_contents($file, $content);

header("Content-Type: $type;charset=utf-8");
header("Cache-Control: max-age=31536000");
echo $content;
