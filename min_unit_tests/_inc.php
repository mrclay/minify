<?php

require dirname(__FILE__) . '/../min/config.php';

set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());

// set cache path and doc root if configured
$minifyCachePath = isset($min_cachePath) 
    ? $min_cachePath 
    : '';
if ($min_documentRoot) {
    $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
}

// default log to FirePHP
require_once 'Minify/Logger.php';
if ($min_errorLogger && true !== $min_errorLogger) { // custom logger
    Minify_Logger::setLogger($min_errorLogger);
} else {
    require_once 'FirePHP.php';
    Minify_Logger::setLogger(FirePHP::getInstance(true));
}

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

$thisDir = dirname(__FILE__);

/**
 * pTest - PHP Unit Tester
 * @param mixed $test Condition to test, evaluated as boolean
 * @param string $message Descriptive message to output upon test
 * @url http://www.sitepoint.com/blogs/2007/08/13/ptest-php-unit-tester-in-9-lines-of-code/
 */
function assertTrue($test, $message)
{
	static $count;
	if (!isset($count)) $count = array('pass'=>0, 'fail'=>0, 'total'=>0);

	$mode = $test ? 'pass' : 'fail';
	$outMode = $test ? 'PASS' : '!FAIL';
	printf("%s: %s (%d of %d tests run so far have %sed)\n",
		$outMode, $message, ++$count[$mode], ++$count['total'], $mode);
	
	return (bool)$test;
}

ob_start();