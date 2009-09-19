<?php
/**
 * Include for serving HTML pages through Minify
 * 
 * DO NOT EDIT! Configure this utility via pageConfig.php
 * 
 * @package Minify
 */

$_min_preIncludedFiles = get_included_files();

function min_autoload($name) {
    require str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
}
spl_autoload_register('min_autoload');

function ob_minify_page($content) {
    global $min_serveOptions, $min_cacheMaxAge;

    $includedFiles = array_diff(
        $GLOBALS['_min_preIncludedFiles'], get_included_files()
    );
    $includedFiles[] = realpath($_SERVER['SCRIPT_FILENAME']);
    $mtime = 0;
    foreach ($includedFiles as $file) {
        $mtime = max($mtime, filemtime($file));
    }

    define('MINIFY_MIN_DIR', dirname(__FILE__));

    // load config
    require MINIFY_MIN_DIR . '/pageConfig.php';

    // setup include path
    set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());

    Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;
    Minify::setCache(
        isset($min_cachePath) ? $min_cachePath : ''
        ,$min_cacheFileLocking
    );

    if ($min_errorLogger) {
        if (true === $min_errorLogger) {
            Minify_Logger::setLogger(FirePHP::getInstance(true));
        } else {
            Minify_Logger::setLogger($min_errorLogger);
        }
    }

    // some array keys may already be set in globals
    $min_serveOptions['id'] = $_SERVER['SCRIPT_FILENAME'];
    $min_serveOptions['content'] = $content;
    $min_serveOptions['lastModified'] = $mtime;
    $min_serveOptions['minifyAll'] = true;
    $min_serveOptions['quiet'] = true;

    // page option: $min_lastModified
    if (isset($GLOBALS['min_lastModified'])) {
        $min_serveOptions['lastModified'] = $GLOBALS['min_lastModified'];
    }

    if ($min_cacheMaxAge) {
        $min_serveOptions['lastModified'] = _steppedTime(
            $min_cacheMaxAge, $_SERVER['SCRIPT_FILENAME']);
    }

    $out = Minify::serve('Page', $min_serveOptions);

    foreach ($out['headers'] as $prop => $value) {
        header($prop === '_responseCode' ? $value : "{$prop}: {$value}");
    }
    return $out['content'];
}

/**
 * Get a stepped version of time with a random-like constistent offset
 *
 * This emulates the mtime() of a file being modified regularly. The offset
 * allows you to use the same period in several instances without them all
 * stepping up simultaneously. Offsets should be evenly distributed.
 *
 * @param int $period in seconds
 * @param string $instanceId
 * @return int
 */
function _steppedTime($period, $instanceId = '') {
    $hashInt = hexdec(substr(md5($instanceId), 0, 8));
    $t = $_SERVER['REQUEST_TIME'];
    return $t - ($t % $period) - ($hashInt % $period);
}

ob_start('ob_minify_page');
