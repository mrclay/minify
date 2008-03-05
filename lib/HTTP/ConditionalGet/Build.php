<?php
/**
 * Class HTTP_ConditionalGet_Build  
 * @package Minify
 * @subpackage HTTP
 */
 
/**
 * Maintain a single last modification time for 1 or more directories of files
 * 
 * Since scanning many files may be slower, the value is cached for a given 
 * number of seconds.
 *
 * @package Minify
 * @subpackage HTTP
 * @author Stephen Clay <steve@mrclay.org>
 */
class HTTP_ConditionalGet_Build {
    
    /**
     * Last modification time of all files in the build
     * 
     * @var int 
     */
    public $lastModified = 0;
    
    /**
     * String to use as ampersand in uri(). Set this to '&' if
     * you are not HTML-escaping URIs.
     *
     * @var string
     */
    public static $ampersand = '&amp;';
    
    /**
     * Get a time-stamped URI
     * 
     * <code>
     * echo 'src="' . $b->uri('/site.js') . '"';
     * // outputs src="/site.js?1678242"
     * 
     * echo 'src="' . $b->uri('/scriptaculous.js?load=effects') . '"';
     * // outputs src="/scriptaculous.js?load=effects&amp1678242"
     * </code>
     *
     * @param string $uri
     * @return string
     */
    public function uri($uri) {
        $sep = strpos($uri, '?') === false
            ? '?'
            : self::$ampersand;
        return "{$uri}{$sep}{$this->lastModified}";
    }

	/**
     * Create a build object
     * 
     * @param array $options
     * 
     * 'id': (required) unique string for this build.
     * 
     * 'savePath': PHP-writeable directory to store build info. If not
     * specified, sys_get_temp_dir() will be used.
     * 
     * 'scanPaths': (required) array of directory paths to scan. A single path
     * string is also accepted.
     * 
     * 'scanDeep': should we scan descendant directories? (default true)
     * 
     * 'scanPattern': filenames must match this (default matches css & js
     * files not starting with ".")
     * 
     * 'delay': seconds to wait before scanning again (default 300)
     * 
     * @return int last modified timestamp
     */
    public function __construct($options) 
    {
        $savePath = isset($options['savePath'])
            ? $options['savePath']
            : sys_get_temp_dir();
        $file = $savePath . DIRECTORY_SEPARATOR 
            . 'HTTP_ConditionalGet_Build_' 
            . md5($options['id']) 
            . '.txt';
        
        // check build file
        $loaded = file_get_contents($file);
        if ($loaded) {
            list($this->lastModified, $nextCheck) = explode('|', $loaded);
        } else {
            $nextCheck = 0;
        }
        if ($nextCheck > $_SERVER['REQUEST_TIME']) {
            // done here
            return;
        }
        
        // scan last modified times
        $options['scanPattern'] = isset($options['scanPattern'])
            ? $options['scanPattern']
            : '/^[^\\.].*\\.(?:css|js)$/';
        $options['scanDeep'] = isset($options['scanDeep'])
            ? $options['scanDeep']
            : true;
        $max = 0;
        foreach ((array)$options['scanPaths'] as $path) {
            $max = max($max, self::_scan($max, $path, $options));
        }
        $this->lastModified = $max;
        
        $nextCheck = $_SERVER['REQUEST_TIME']
            + (isset($options['delay'])
                ? (int)$options['delay'] 
                : 300);
        
        // save build file
        file_put_contents($file, "{$this->lastModified}|{$nextCheck}");
    }
    
    protected static function _scan($max, $path, $options)
    {
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            if ('.' === $entry[0]) {
                continue;
            }
            $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($entry)) {
                if ($options['scanDeep']) {
                    $max = max($max, self::_scan($max, $fullPath, $options));
                }
            } else {
                if (preg_match($options['scanPattern'], $entry)) {
                    $max = max($max, filemtime($fullPath));
                }
            }
        }
        $d->close();
        return $max;
    }
}