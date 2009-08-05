<?php
/**
 * Configuration for pageBuffer.php
 * @package Minify
 */


/**
 * Set to true to log messages to FirePHP (Firefox Firebug addon).
 * Set to false for no error logging (Minify may be slightly faster).
 * @link http://www.firephp.org/
 *
 * If you want to use a custom error logger, set this to your logger 
 * instance. Your object should have a method log(string $message).
 *
 * @todo cache system does not have error logging yet.
 */
$min_errorLogger = false;


/**
 * For best performance, specify your temp directory here. Otherwise Minify
 * will have to load extra code to guess. Some examples below:
 */
//$min_cachePath = 'c:\\WINDOWS\\Temp';
//$min_cachePath = '/tmp';
//$min_cachePath = preg_replace('/^\\d+;/', '', session_save_path());


/**
 * Cache file locking. Set to false if filesystem is NFS. On at least one 
 * NFS system flock-ing attempts stalled PHP for 30 seconds!
 */
$min_cacheFileLocking = true;


/**
 * Maximum age of browser cache in seconds. After this period, the browser
 * will send another conditional GET. Use a longer period for lower traffic
 * but you may want to shorten this before making changes if it's crucial
 * those changes are seen immediately.
 */
$min_serveOptions['maxAge'] = 1800;


/**
 * By default Minify will check the mtime of the loaded page and any pages it
 * includes to determine the last modified time. If you would rather have it
 * regularly rebuild the cache, set this to the period, in seconds, that the
 * cache should be rebuilt (note this only occurs if a request is made).
 */
$min_cacheMaxAge = false;


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


/**
 * Path to Minify's lib folder. If you happen to move it, change 
 * this accordingly.
 */
$min_libPath = dirname(__FILE__) . '/lib';


// try to disable output_compression (may not have an effect)
ini_set('zlib.output_compression', '0');
