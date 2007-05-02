<?php
/**
 * Minify - Combines, minifies, and caches JavaScript and CSS files on demand.
 *
 * This library was inspired by jscsscomp by Maxim Martynyuk <flashkot@mail.ru>
 * and by the article "Supercharged JavaScript" by Patrick Hunlock
 * <wb@hunlock.com>.
 *
 * The JSMin library used for JavaScript minification was originally written by
 * Douglas Crockford <douglas@crockford.com> and was ported to PHP by
 * David Holmes <dholmes@cfdsoftware.net>.
 *
 * Requires PHP 5.2.1+.
 *
 * @package Minify
 * @author Ryan Grove <ryan@wonko.com>
 * @copyright 2007 Ryan Grove. All rights reserved.
 * @license http://opensource.org/licenses/bsd-license.php  New BSD License
 * @version 1.0.0 (2007-05-01)
 * @link http://code.google.com/p/minify/
 */

if (!defined('MINIFY_BASE_DIR')) {
  /** 
   * Base path from which all relative file paths should be resolved. By default
   * this is set to the document root.
   */
  define('MINIFY_BASE_DIR', $_SERVER['DOCUMENT_ROOT']);
}

if (!defined('MINIFY_CACHE_DIR')) {
  /** Directory where compressed files will be cached. */
  define('MINIFY_CACHE_DIR', sys_get_temp_dir());
}

if (!defined('MINIFY_ENCODING')) {
  /** Character set to use when outputting the minified files. */
  define('MINIFY_ENCODING', 'utf-8');
}

if (!defined('MINIFY_MAX_FILES')) {
  /** Maximum number of files to combine in one request. */
  define('MINIFY_MAX_FILES', 16);
}

/**
 * Minify is a library for combining, minifying, and caching JavaScript and CSS
 * files on demand before sending them to a web browser.
 *
 * @package Minify
 * @author Ryan Grove <ryan@wonko.com>
 * @copyright 2007 Ryan Grove. All rights reserved.
 * @license http://opensource.org/licenses/bsd-license.php  New BSD License
 * @version 1.0.0 (2007-05-01)
 * @link http://code.google.com/p/minify/
 */
class Minify {
  const TYPE_CSS = 'text/css';
  const TYPE_JS  = 'text/javascript';

  protected $files      = array();
  protected $type       = TYPE_JS;
  protected $useCache   = true;

  // -- Public Static Methods --------------------------------------------------

  /**
   * Combines, minifies, and outputs the requested files.
   *
   * Inspects the $_GET array for a 'files' entry containing a comma-separated
   * list and uses this as the set of files to be combined and minified.
   */
  public static function handleRequest() {
    // 404 if no files were requested.
    if (!isset($_GET['files'])) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }

    $files = array_map('trim', explode(',', $_GET['files'], MINIFY_MAX_FILES));

    // 404 if the $files array is empty for some weird reason.
    if (!count($files)) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }

    // Determine the content type based on the extension of the first file
    // requested.
    $type = preg_match('/\.js$/iD', $files[0]) ? self::TYPE_JS : self::TYPE_CSS;

    // Minify and spit out the result.
    try {
      $minify = new Minify($files, $type);
  
      header("Content-Type: $type;charset=".MINIFY_ENCODING);
      
      $minify->cache();
      echo $minify->combine();
      exit;
    }
    catch (MinifyException $e) {
      header('HTTP/1.0 404 Not Found');
      echo htmlentities($e->getMessage());
      exit;
    }
  }

  /**
   * Minifies the specified string and returns it.
   *
   * @param string $string JavaScript or CSS string to minify
   * @param string $type content type of the string (either Minify::TYPE_CSS or
   *   Minify::TYPE_JS)
   * @return string minified string
   */
  public static function minify($string, $type = self::TYPE_JS) {
    return $type === self::TYPE_JS ? self::minifyJS($string) :
        self::minifyCSS($string);
  }

  // -- Protected Static Methods -----------------------------------------------

  /**
   * Minifies the specified CSS string and returns it.
   *
   * @param string $string CSS string
   * @return string minified string
   * @see minify()
   * @see minifyJS()
   */
  protected static function minifyCSS($string) {
    // Compress whitespace.
    $string = preg_replace('/\s+/', ' ', $string);

    // Remove comments.
    $string = preg_replace('/\/\*.*?\*\//', '', $string);

    return trim($string);
  }

  /**
   * Minifies the specified JavaScript string and returns it.
   *
   * @param string $string JavaScript string
   * @return string minified string
   * @see minify()
   * @see minifyCSS()
   */
  protected static function minifyJS($string) {
    define('JSMIN_AS_LIB', true);

    require_once dirname(__FILE__).'/lib/JSMin_lib.php';

    $jsMin = new JSMin($string, false);
    return $jsMin->minify();
  }

  // -- Public Instance Methods ------------------------------------------------
  
  /**
   * Instantiates a new Minify object. A filename can be in the form of a
   * relative path or a URL that resolves to the same site that hosts Minify.
   *
   * @param array|string $files filename or array of filenames to be minified
   * @param string $type content type of the specified files (either
   *   Minify::TYPE_CSS or Minify::TYPE_JS)
   * @param bool $useCache whether or not to use the disk-based cache
   */
  public function __construct($files = array(), $type = self::TYPE_JS,
      $useCache = true) {

    if ($type !== self::TYPE_JS && $type !== self::TYPE_CSS) {
      throw new MinifyInvalidArgumentException('Invalid argument ($type): '.
          $type);
    }

    $this->type     = $type;
    $this->useCache = (bool) $useCache;

    if (count((array) $files)) {
      $this->addFile($files);
    }
  }

  /**
   * Adds the specified filename or array of filenames to the list of files to
   * be minified. A filename can be in the form of a relative path or a URL
   * that resolves to the same site that hosts Minify.
   *
   * @param array|string $files filename or array of filenames
   * @see getFiles()
   * @see removeFile()
   */
  public function addFile($files) {
    $files = @array_map(array($this, 'resolveFilePath'), (array) $files);
    $this->files = array_unique(array_merge($this->files, $files));
  }
  
  /**
   * Attempts to serve the combined, minified files from the cache if possible.
   *
   * This method first checks the ETag value and If-Modified-Since timestamp
   * sent by the browser and exits with an HTTP "304 Not Modified" response if
   * the requested files haven't changed since they were last sent to the
   * client.
   *
   * If the browser hasn't cached the content, we check to see if it's been
   * cached on the server and, if so, we send the cached content and exit.
   *
   * If neither the client nor the server has the content in its cache, we don't
   * do anything.
   */
  public function cache() {
    $hash         = $this->getHash();
    $lastModified = 0;

    // Get the timestamp of the most recently modified file.
    foreach($this->files as $file) {
      $modified = filemtime($file);
      
      if ($modified !== false && $modified > $lastModified) {
        $lastModified = $modified;
      }
    }

    $lastModifiedGMT = gmdate('D, d M Y H:i:s', $lastModified).' GMT';

    // Check/set the ETag.
    $etag = $hash.'_'.$lastModified;

    if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
      if (strpos($_SERVER['HTTP_IF_NONE_MATCH'], $etag) !== false) {
        header("Last-Modified: $lastModifiedGMT", true, 304);
        exit;
      }
    }

    header('ETag: "'.$etag.'"');

    // Check If-Modified-Since.
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      if ($lastModified <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        header("Last-Modified: $lastModifiedGMT", true, 304);
        exit;
      }
    }

    header("Last-Modified: $lastModifiedGMT");

    // Check the server-side cache.
    if ($this->useCache) {
      $cacheFile = MINIFY_CACHE_DIR.'/minify_'.$hash;
  
      if (is_file($cacheFile) && $lastModified <= filemtime($cacheFile)) {
        echo file_get_contents($cacheFile);
        exit;
      }
    }
  }

  /**
   * Combines and returns the contents of all files that have been added with
   * addFile() or via this class's constructor.
   *
   * If Minify->useCache is true, the results will be saved to the on-disk
   * cache.
   *
   * @param bool $minify minify the combined contents before returning them
   * @return string combined file contents
   */
  public function combine($minify = true) {
    $combined = array();

    foreach($this->files as $file) {
      $combined[] = file_get_contents($file);
    }

    $combined = $minify ? self::minify(implode("\n", $combined), $this->type) :
        implode("\n", $combined);

    // Save combined contents to the cache.
    if ($this->useCache) {
      $cacheFile = MINIFY_CACHE_DIR.'/minify_'.$this->getHash();
      @file_put_contents($cacheFile, $combined, LOCK_EX);
    }

    return $combined;
  }

  /**
   * Gets an array of absolute pathnames of all files that have been added with
   * addFile() or via this class's constructor.
   *
   * @return array array of absolute pathnames
   * @see addFile()
   * @see removeFile()
   */
  public function getFiles() {
    return $this->files;
  }

  /**
   * Gets the MD5 hash of the concatenated filenames from the list of files to
   * be minified.
   */
  public function getHash() {
    return hash('md5', implode('', $this->files));
  }

  /**
   * Removes the specified filename or array of filenames from the list of files
   * to be minified.
   *
   * @param array|string $files filename or array of filenames
   * @see addFile()
   * @see getFiles()
   */
  public function removeFile($files) {
    $files = @array_map(array($this, 'resolveFilePath'), (array) $files);
    $this->files = array_diff($this->files, $files);
  }

  // -- Protected Instance Methods ---------------------------------------------

  /**
   * Returns the canonicalized absolute pathname to the specified file or local
   * URL.
   *
   * @param string $file relative file path
   * @return string canonicalized absolute pathname
   */
  protected function resolveFilePath($file) {
    // Is this a URL?
    if (preg_match('/^https?:\/\//i', $file)) {
      if (!$parsedUrl = parse_url($file)) {
        throw new MinifyInvalidUrlException("Invalid URL: $file");
      }

      // Does the server name match the local server name?
      if (!isset($parsedUrl['host']) ||
          $parsedUrl['host'] != $_SERVER['SERVER_NAME']) {
        throw new MinifyInvalidUrlException('Non-local URL not supported: '.
            $file);
      }

      // Get the file's absolute path.
      $filepath = realpath(MINIFY_BASE_DIR.$parsedUrl['path']);
    }
    else {
      // Get the file's absolute path.
      $filepath = realpath(MINIFY_BASE_DIR.'/'.$file);
    }

    // Ensure that the file exists, that the path is under the base directory,
    // that the file's extension is either '.css' or '.js', and that the file is
    // actually readable.
    if (!$filepath ||
        !is_file($filepath) ||
        !is_readable($filepath) ||
        !preg_match('/^'.preg_quote(MINIFY_BASE_DIR, '/').'/', $filepath) ||
        !preg_match('/\.(?:css|js)$/iD', $filepath)) {

      // Even when the file exists, we still throw a
      // MinifyFileNotFoundException in order to try to prevent an information
      // disclosure vulnerability.
      throw new MinifyFileNotFoundException("File not found: $file");
    }

    return $filepath;
  }
}

// -- Exception Classes --------------------------------------------------------
class MinifyException extends Exception {}
class MinifyFileNotFoundException extends MinifyException {}
class MinifyInvalidArgumentException extends MinifyException {}
class MinifyInvalidUrlException extends MinifyException {}

// -- Global Scope -------------------------------------------------------------
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) {
  Minify::handleRequest();
}
?>