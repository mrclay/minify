<?php
/**
 * Front controller for default Minify implementation
 * 
 * DO NOT EDIT! Configure this utility via config.php and groupsConfig.php
 * 
 * @package Minify
 */

define('MINIFY_MIN_DIR', dirname(__FILE__));

// set config path defaults
$min_configPaths = array(
    'base'   => MINIFY_MIN_DIR . '/config.php',
    'test'   => MINIFY_MIN_DIR . '/config-test.php',
    'groups' => MINIFY_MIN_DIR . '/groupsConfig.php',
);

// check for custom config paths
if (!empty($min_customConfigPaths) && is_array($min_customConfigPaths)) {
    $min_configPaths = array_merge($min_configPaths, $min_customConfigPaths);
}

// load config
require $min_configPaths['base'];

if (isset($_GET['test'])) {
    include $min_configPaths['test'];
}

require "$min_libPath/Minify/Loader.php";
Minify_Loader::register();

$server = $_SERVER;
if ($min_documentRoot) {
    $server['DOCUMENT_ROOT'] = $min_documentRoot;
}

$env = new Minify_Env(array(
    'server' => $server,

    // move these...
    'allowDebug' => $min_allowDebugFlag,
    'uploaderHoursBehind' => $min_uploaderHoursBehind,
));

if (!isset($min_cachePath)) {
    $cache = new Minify_Cache_File('', $min_cacheFileLocking);
} elseif (is_object($min_cachePath)) {
    // let type hinting catch type error
    $cache = $min_cachePath;
} else {
    $cache = new Minify_Cache_File($min_cachePath, $min_cacheFileLocking);
}

$server = new Minify($cache);

$min_serveOptions['minifierOptions']['text/css']['docRoot'] = $env->getDocRoot();
$min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
// auto-add targets to allowDirs
foreach ($min_symlinks as $uri => $target) {
    $min_serveOptions['minApp']['allowDirs'][] = $target;
}

if ($min_allowDebugFlag) {
    $min_serveOptions['debug'] = Minify_DebugDetector::shouldDebugRequest($env);
}

if ($min_errorLogger) {
    if (true === $min_errorLogger) {
        $min_errorLogger = FirePHP::getInstance(true);
    }
    Minify_Logger::setLogger($min_errorLogger);
}

// check for URI versioning
if (null !== $env->get('v') || preg_match('/&\\d/', $env->server('QUERY_STRING'))) {
    $min_serveOptions['maxAge'] = 31536000;
}

// need groups config?
if (null !== $env->get('g')) {
    // well need groups config
    $min_serveOptions['minApp']['groups'] = (require $min_configPaths['groups']);
}

if ($env->get('f') || null !== $env->get('g')) {
    // serving!
    if (! isset($min_serveController)) {

        $sourceFactoryOptions = array();
        if (isset($min_serveOptions['minApp']['noMinPattern'])) {
            $sourceFactoryOptions['noMinPattern'] = $min_serveOptions['minApp']['noMinPattern'];
        }
        $sourceFactory = new Minify_Source_Factory($env, $sourceFactoryOptions);

        $min_serveController = new Minify_Controller_MinApp($env, $sourceFactory);
    }
    $server->serve($min_serveController, $min_serveOptions);
    exit;
}

// not serving
if ($min_enableBuilder) {
    header('Location: builder/');
    exit;
}

header('Location: /');
exit;
