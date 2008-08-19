<?php
/**
 * Configuration for default Minify implementation
 * @package Minify
 */


/**
 * For best performance, specify your temp directory here.
 * Otherwise Minify will have to load extra code to guess.
 */
$minifyCachePath = 'c:\\xampp\\tmp';
//$minifyCachePath = 'c:\\WINDOWS\Temp';
//$minifyCachePath = '/tmp';


/**
 * Manually set the path to Minify's lib folder
 */
//$minifyLibPath = '../lib';


/**
 * Set to true to disable the "f" GET parameter for specifying files.
 * Only the "g" parameter will be considered.
 */
$minifyGroupsOnly = false;


/**
 * Allows specification of base directory via the "b" GET parameter.
 * E.g. these are the same:
 * ?f=jsFiles/file1.js,jsFiles/file2.js
 * ?b=jsFiles&f=file1.js,file2.js
 */
$minifyAllowBase = true;


/**
 * If you'd like to restrict the "f" option to files within/below
 * particular directories below DOCUMENT_ROOT, set this here.
 * You will still need to include the directory in the
 * f or b GET parameters.
 * 
 * // = DOCUMENT_ROOT 
 */
$minifyAllowDirs = array('//js', '//css');
