<?php
/**
 * Front controller for default Minify implementation
 * 
 * DO NOT EDIT! Configure this utility via config.php and groupsConfig.php
 * 
 * @package Minify
 */

define('MINIFY_MIN_DIR', dirname(__FILE__));

// set config directory defaults
$min_configDirs = array(
    'config.php'       => MINIFY_MIN_DIR,
    'config-test.php'  => MINIFY_MIN_DIR,
    'groupsConfig.php' => MINIFY_MIN_DIR
);

// check for custom config directory
if (defined('MINIFY_CUSTOM_CONFIG_DIR')) {
    // check for each config in the custom directory
    foreach ($min_configDirs as $file => $dir) {
        $path = MINIFY_CUSTOM_CONFIG_DIR . '/' . $file;
        if (!file_exists($path)) {
            continue;   
        }
        if (!is_readable($path)) {
            continue;   
        }
        // reassign the directory for this config to custom
        $min_configDirs[$file] = MINIFY_CUSTOM_CONFIG_DIR;
    }
    unset($file, $dir, $path);
}

// load config
require $min_configDirs['config.php'] . '/config.php';

if (isset($_GET['test'])) {
    include $min_configDirs['config-test.php'] . '/config-test.php';
}

require "$min_libPath/Minify/Loader.php";
Minify_Loader::register();

Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;
Minify::setCache(
    isset($min_cachePath) ? $min_cachePath : ''
    ,$min_cacheFileLocking
);

if ($min_documentRoot) {
    $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
    Minify::$isDocRootSet = true;
}

$min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
// auto-add targets to allowDirs
foreach ($min_symlinks as $uri => $target) {
    $min_serveOptions['minApp']['allowDirs'][] = $target;
}

if ($min_allowDebugFlag) {
    $min_serveOptions['debug'] = Minify_DebugDetector::shouldDebugRequest($_COOKIE, $_GET, $_SERVER['REQUEST_URI']);
}

if ($min_errorLogger) {
    if (true === $min_errorLogger) {
        $min_errorLogger = FirePHP::getInstance(true);
    }
    Minify_Logger::setLogger($min_errorLogger);
}

// check for URI versioning
if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
    $min_serveOptions['maxAge'] = 31536000;
}
if (isset($_GET['g'])) {
    // well need groups config
    $min_serveOptions['minApp']['groups'] = (require $min_configDirs['groupsConfig.php'] . '/groupsConfig.php');
}
if (isset($_GET['f']) || isset($_GET['g'])) {
    // serve!   

    if (! isset($min_serveController)) {
        $min_serveController = new Minify_Controller_MinApp();
    }
    Minify::serve($min_serveController, $min_serveOptions);
        
} elseif ($min_enableBuilder) {
    header('Location: builder/');
    exit();
} else {
    header("Location: /");
    exit();
}
