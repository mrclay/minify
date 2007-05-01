<?php
/**
 * minify.php - On the fly JavaScript/CSS minifier.
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
 * See http://wonko.com/software/minify/ for news and updates.
 *
 * @author Ryan Grove <ryan@wonko.com>
 * @copyright Copyright (c) 2007 Ryan Grove. All rights reserved.
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 * @version 1.0.0 (?)
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

class Minify {
  const TYPE_CSS = 'text/css';
  const TYPE_JS  = 'text/javascript';

  private $files      = array();
  private $type       = TYPE_JS;
  private $useCache   = true;

  // -- Public Static Methods --------------------------------------------------

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

  public static function minify($string, $type = self::TYPE_JS) {
    return $type === self::TYPE_JS ? self::jsMinify($string) :
        self::cssMinify($string);
  }

  // -- Private Static Methods -------------------------------------------------

  private static function cssMinify($string) {
    // Compress whitespace.
    $string = preg_replace('/\s+/', ' ', $string);

    // Remove comments.
    $string = preg_replace('/\/\*.*?\*\//', '', $string);

    return trim($string);
  }

  private static function jsMinify($string) {
    define('JSMIN_AS_LIB', true);

    require_once dirname(__FILE__).'/lib/JSMin_lib.php';

    $jsMin = new JSMin($string, false);
    return $jsMin->minify();
  }

  // -- Public Instance Methods ------------------------------------------------
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
   * be minified.
   *
   * @param array|string $files filename or array of filenames
   */
  public function addFile($files) {
    $files = @array_map(array($this, 'resolveFilePath'), (array) $files);
    $this->files = array_unique(array_merge($this->files, $files));
  }
  
  /**
   * Checks the ETag value and/or If-Modified-Since timestamp sent by the
   * browser and exits with an HTTP "304 Not Modified" response if the
   * requested files haven't changed since they were last sent to the client.
   *
   * If the browser hasn't cached the content, checks to see if we've cached it
   * on the server and, if so, sends the cached content and exits.
   *
   * If neither the client nor the server has the content in its cache,
   * execution will continue.
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
   */
  public function removeFile($files) {
    $files = @array_map(array($this, 'resolveFilePath'), (array) $files);
    $this->files = array_diff($this->files, $files);
  }

  // -- Private Instance Methods -----------------------------------------------

  /**
   * Returns the canonicalized absolute pathname to the specified file.
   *
   * @param string $file relative file path
   * @return string canonicalized absolute pathname
   */
  private function resolveFilePath($file) {
    // Get the file's absolute path.
    $filepath = realpath(MINIFY_BASE_DIR.'/'.$file);

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

// -- Global Scope -------------------------------------------------------------
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) {
  Minify::handleRequest();
}
?>