<?php
/**
 * Configuration for default Minify implementation
 * @package Minify
 */


/**
 * Forward empty requests to URI Builder app. After initial setup this
 * should be set to false.
 **/
$min_forwardToBuilder = true;


/**
 * For best performance, specify your temp directory here.
 * Otherwise Minify will have to load extra code to guess.
 */
//$min_cachePath = 'c:\\WINDOWS\Temp';
//$min_cachePath = '/tmp';


/**
 * Maximum age of browser cache in seconds. After this period,
 * the browser will send another conditional GET. You might
 * want to shorten this before making changes if it's crucial
 * those changes are seen immediately.
 */
$min_serveOptions['maxAge'] = 1800;


/**
 * If you'd like to restrict the "f" option to files within/below
 * particular directories below DOCUMENT_ROOT, set this here.
 * You will still need to include the directory in the
 * f or b GET parameters.
 * 
 * // = DOCUMENT_ROOT 
 */
//$min_allowDirs = array('//js', '//css');


/**
 * If you move Minify's lib folder, give the path to it here.
 */
//$min_libPath = 'lib';


/**
 * Set to true to disable the "f" GET parameter for specifying files.
 * Only the "g" parameter will be considered.
 */
$min_groupsOnly = false;


/**
 * Uncomment to enable debug mode. Files will be combined with no 
 * minification, and comments will be added to indicate the line #s
 * of the original files. This will allow you to debug the combined
 * file while knowing where to modify the originals.
 */
//$min_serveOptions['debug'] = true;


/**
 * If you upload files from Windows to a non-Windows server, Windows may report
 * incorrect mtimes for the files. Immediately after modifying and uploading a 
 * file, use the touch command to update the mtime on the server. If the mtime 
 * jumps ahead by a number of hours, set this variable to that number. If the mtime 
 * moves back, this should not be needed.
 */
$min_uploaderHoursBehind = 0;

