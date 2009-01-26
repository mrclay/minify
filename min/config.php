<?php
/**
 * Configuration for default Minify application
 * @package Minify
 */


/**
 * In 'debug' mode, Minify can combine files with no minification and 
 * add comments to indicate line #s of the original files. 
 * 
 * To allow debugging, set this option to true and add "&debug=1" to 
 * a URI. E.g. /min/?f=script1.js,script2.js&debug=1
 */
$min_allowDebugFlag = false;


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
 * On most servers, this can be left empty. On others, $_SERVER['DOCUMENT_ROOT']
 * may be misconfigured or missing. If so, set this to your full document
 * root path with no trailing slash.
 * E.g. '/home/accountname/public_html' or 'c:\\xampp\\htdocs'
 *
 * If /min/ is directly inside your document root, just uncomment the 
 * second line:
 */
$min_documentRoot = '';
//$min_documentRoot = substr(__FILE__, 0, strlen(__FILE__) - 15);


/**
 * Cache file locking. Set to false if filesystem is NFS. On at least one 
 * NFS system flock-ing attempts stalled PHP for 30 seconds!
 */
$min_cacheFileLocking = true;


/**
 * Allow use of the Minify URI Builder app. If you no longer need 
 * this, set to false.
 **/
$min_enableBuilder = true;


/**
 * Maximum age of browser cache in seconds. After this period, the browser
 * will send another conditional GET. Use a longer period for lower traffic
 * but you may want to shorten this before making changes if it's crucial
 * those changes are seen immediately.
 *
 * Note: Despite this setting, if you include a number at the end of the
 * querystring, maxAge will be set to one year. E.g. /min/f=hello.css&123456
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


/**
 * Path to Minify's lib folder. If you happen to move it, change 
 * this accordingly.
 */
$min_libPath = dirname(__FILE__) . '/lib';


// try to disable output_compression (may not have an effect)
ini_set('zlib.output_compression', '0');
