<?php
/**
 * Configuration for default Minify implementation
 * @package Minify
 */


/**
 * Path to Minify's lib folder. If you happen to move it, change 
 * this accordingly.
 */
$min_libPath = dirname(__FILE__) . '/lib';


/**
 * For best performance, specify your temp directory here. Otherwise 
 * Minify will have to load extra code to guess. Commented out below
 * are a few possible choices.
 */
//$min_cachePath = 'c:\\WINDOWS\Temp';
//$min_cachePath = '/tmp';
//$min_cachePath = preg_replace('/^\\d+;/', '', session_save_path());


/**
 * Cache file locking. Set to false if filesystem is NFS.
 */
$min_cacheFileLocking = true;


/**
 * Allow use of the Minify URI Builder app. If you no longer need 
 * this, set to false.
 **/
$min_enableBuilder = true;


/**
 * In 'debug' mode, Minify can combine files with no minification and 
 * add comments to indicate line #s of the original files. 
 * 
 * To allow debugging, set this option to true and add "&debug=1" to 
 * a URI. E.g. /min/?f=script1.js,script2.js&debug=1
 */
$min_allowDebugFlag = false;


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
//$min_serveOptions['minApp']['allowDirs'] = array('//js', '//css');

/**
 * Set to true to disable the "f" GET parameter for specifying files.
 * Only the "g" parameter will be considered.
 */
$min_serveOptions['minApp']['groupsOnly'] = false;

/**
 * Maximum # of files that can be specified in the "f" GET parameter
 */
$min_serveOptions['minApp']['maxFiles'] = 10;


/**
 * If you upload files from Windows to a non-Windows server, Windows may report
 * incorrect mtimes for the files. This may cause Minify to keep serving stale 
 * cache files when source file changes are made too frequently (e.g. more than
 * once an hour).
 * 
 * Immediately after modifying and uploading a file, use the touch command to 
 * update the mtime on the server. If the mtime jumps ahead by a number of hours,
 * set this variable to that number. If the mtime moves back, this should not be 
 * needed.
 *
 * In the Windows SFTP client WinSCP, there's an option that may fix this 
 * issue without changing the variable below. Under login > environment, 
 * select the option "Adjust remote timestamp with DST".
 * @link http://winscp.net/eng/docs/ui_login_environment#daylight_saving_time
 */
$min_uploaderHoursBehind = 0;

