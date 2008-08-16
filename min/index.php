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
if (!isset($minifyLibPath)) {
    // default lib path is inside this directory
    $minifyLibPath = MINIFY_MIN_DIR . '/lib';
}
set_include_path($minifyLibPath . PATH_SEPARATOR . get_include_path());

// friendly error if lib wasn't in the include_path
if (! (include 'Minify.php')) {
    trigger_error(
    	'Minify: You must add Minify/lib to the include_path or set $minifyLibPath in config.php'
        ,E_USER_ERROR
    );
}

if (isset($_GET['g'])) {
    
    Minify::setCache(isset($minifyCachePath) ? $minifyCachePath : null);
    // Groups expects the group key as PATH_INFO
    // we want to allow ?g=groupKey
    $_SERVER['PATH_INFO'] = '/' . $_GET['g'];
    Minify::serve('Groups', array(
        'groups' => (require MINIFY_MIN_DIR . '/groupsConfig.php')
    ));

} elseif (!$minifyGroupsOnly && isset($_GET['f'])) {

    /**
     * crude initial implementation hacked onto on Version1 controller
     * @todo encapsulate this in a new controller 
     */
    
    if (isset($minifyCachePath)) {
        define('MINIFY_CACHE_DIR', $minifyCachePath);
    }
    $serveOpts = array();
    if (isset($minifyAllowDirs)) {
        foreach ((array)$minifyAllowDirs as $_allowDir) {
            $serveOpts['allowDirs'][] = realpath(
                $_SERVER['DOCUMENT_ROOT'] . substr($_allowDir, 1)
            );
        }
    }
    // Version1 already does validation. All we want is to prepend "b"
    // to each file if it looks right.
    $base = ($minifyAllowBase 
             && isset($_GET['b']) 
             && preg_match('@^[^/.]+(?:/[^/.]+)*$@', $_GET['b']))
        ? '/' . $_GET['b'] . '/'
        : '/';
    // Version1 expects ?files=/js/file1.js,/js/file2.js,/js/file3.js
    // we want to allow ?f=js/file1.js,js/file2.js,js/file3.js
    // or               ?b=js&f=file1.js,file2.js,file3.js
    $_GET['files'] = $base . str_replace(',', ',' . $base, $_GET['f']);
    
    Minify::serve('Version1', $serveOpts);
}
