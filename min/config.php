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
$minifyLibPath = '../lib';


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
$minifyAllowBase = false;


/**
 * If you'd like to restrict the "f" option to files within/below
 * a particular directory below DOCUMENT_ROOT, set this here.
 * You will still need to include this directory in the
 * f or b GET parameters.
 * 
 * // = DOCUMENT_ROOT 
 */
$minifyRestrictDir = '//js/transition';

