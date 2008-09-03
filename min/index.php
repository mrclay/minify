<?php
/**
 * Front controller for default Minify implementation
 * 
 * DO NOT EDIT! Configure this utility via config.php and groupsConfig.php
 * 
 * @package Minify
 */

define('MINIFY_MIN_DIR', dirname(__FILE__));

// load config
require MINIFY_MIN_DIR . '/config.php';

// setup include path
if (!isset($min_libPath)) {
    // default lib path is inside this directory
    $min_libPath = MINIFY_MIN_DIR . '/lib';
}
set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());

// friendly error if lib wasn't in the include_path
if (! (include 'Minify.php')) {
    trigger_error(
    	'Minify: You must add Minify/lib to the include_path or set $min_libPath in config.php'
        ,E_USER_ERROR
    );
}

if (0 === stripos(PHP_OS, 'win')) {
    Minify::setDocRoot(); // we may be on IIS
}

Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;

if (isset($_GET['g'])) {
    
    Minify::setCache(isset($min_cachePath) ? $min_cachePath : null);
    // Groups expects the group key as PATH_INFO
    // we want to allow ?g=groupKey
    $_SERVER['PATH_INFO'] = '/' . $_GET['g'];
    $min_serveOptions['groups'] = (require MINIFY_MIN_DIR . '/groupsConfig.php');
    if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
        $min_serveOptions['maxAge'] = 31536000;
    }
    Minify::serve('Groups', $min_serveOptions);

} elseif (!$min_groupsOnly && isset($_GET['f'])) {

    /**
     * crude initial implementation hacked onto on Version1 controller
     * @todo encapsulate this in a new controller 
     */
    
    if (isset($min_cachePath)) {
        define('MINIFY_CACHE_DIR', $min_cachePath);
    }
    if (isset($min_allowDirs)) {
        foreach ((array)$min_allowDirs as $_allowDir) {
            $min_serveOptions['allowDirs'][] = realpath(
                $_SERVER['DOCUMENT_ROOT'] . substr($_allowDir, 1)
            );
        }
    }
    // Version1 already does validation. All we want is to prepend "b"
    // to each file if it looks right.
    $min_base = (isset($_GET['b']) && preg_match('@^[^/.]+(?:/[^/.]+)*$@', $_GET['b']))
        ? '/' . $_GET['b'] . '/'
        : '/';
    // Version1 expects ?files=/js/file1.js,/js/file2.js,/js/file3.js
    // we want to allow ?f=js/file1.js,js/file2.js,js/file3.js
    // or               ?b=js&f=file1.js,file2.js,file3.js
    $_GET['files'] = $min_base . str_replace(',', ',' . $min_base, $_GET['f']);
    
    Minify::serve('Version1', $min_serveOptions);

} elseif ($min_forwardToBuilder) {
 
    header('Location: builder/');
    exit();
    
} else {

    header("HTTP/1.0 404 Not Found");
    exit();

}
