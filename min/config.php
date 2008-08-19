<?php
/**
 * Configuration for default Minify implementation
 * @package Minify
 */


/**
 * For best performance, specify your temp directory here.
 * Otherwise Minify will have to load extra code to guess.
 */
//$minifyCachePath = 'c:\\WINDOWS\Temp';
//$minifyCachePath = '/tmp';


/**
 * If you'd like to restrict the "f" option to files within/below
 * particular directories below DOCUMENT_ROOT, set this here.
 * You will still need to include the directory in the
 * f or b GET parameters.
 * 
 * // = DOCUMENT_ROOT 
 */
//$minifyAllowDirs = array('//js', '//css');


/**
 * Manually set the path to Minify's lib folder
 */
//$minifyLibPath = 'lib';


/**
 * Set to true to disable the "f" GET parameter for specifying files.
 * Only the "g" parameter will be considered.
 */
$minifyGroupsOnly = false;
