<?php
/**
 * Class Minify_CSS_UriRewriter  
 * @package Minify
 */

/**
 * Rewrite file-relative URIs as root-relative in CSS files
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_CSS_UriRewriter {
    
    /**
     * Defines which class to call as part of callbacks, change this
     * if you extend Minify_CSS_UriRewriter
     * @var string
     */
    protected static $className = 'Minify_CSS_UriRewriter';
    
    /**
     * Rewrite file relative URIs as root relative in CSS files
     * 
     * @param string $css
     * 
     * @param string $currentDir The directory of the current CSS file.
     * 
     * @param string $docRoot The document root of the web site in which 
     * the CSS file resides (default = $_SERVER['DOCUMENT_ROOT']).
     * 
     * @param array $symlinks (default = array()) If the CSS file is stored in 
     * a symlink-ed directory, provide an array of link paths to
     * target paths, where the link paths are within the document root. Because 
     * paths need to be normalized for this to work, use "//" to substitute 
     * the doc root in the link paths (the array keys). E.g.:
     * <code>
     * array('//symlink' => '/real/target/path') // unix
     * array('//static' => 'D:\\staticStorage')  // Windows
     * </code>
     * 
     * @return string
     */
    public static function rewrite($css, $currentDir, $docRoot = null, $symlinks = array()) 
    {
        self::$_docRoot = $docRoot
            ? $docRoot
            : $_SERVER['DOCUMENT_ROOT'];
        self::$_docRoot = realpath(self::$_docRoot);
        self::$_currentDir = realpath($currentDir);
        self::$_symlinks = array();
        
        // normalize symlinks
        foreach ($symlinks as $link => $target) {
            $link = str_replace('//', realpath(self::$_docRoot), $link);
            $link = strtr($link, '/', DIRECTORY_SEPARATOR);
            self::$_symlinks[$link] = realpath($target);
        }
        
        $css = self::_trimUrls($css);
        
        // rewrite
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'
            ,array(self::$className, '_uriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
            ,array(self::$className, '_uriCB'), $css);

        return $css;
    }
    
    /**
     * Prepend a path to relative URIs in CSS files
     * 
     * @param string $css
     * 
     * @param string $path The path to prepend.
     * 
     * @return string
     */
    public static function prepend($css, $path)
    {
        self::$_prependPath = $path;
        
        $css = self::_trimUrls($css);
        
        // append
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'
            ,array(self::$className, '_uriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
            ,array(self::$className, '_uriCB'), $css);

        self::$_prependPath = null;
        return $css;
    }
    
    
    /**
     * @var string directory of this stylesheet
     */
    private static $_currentDir = '';
    
    /**
     * @var string DOC_ROOT
     */
    private static $_docRoot = '';
    
    /**
     * @var array directory replacements to map symlink targets back to their
     * source (within the document root) E.g. '/var/www/symlink' => '/var/realpath'
     */
    private static $_symlinks = array();
    
    /**
     * @var string path to prepend
     */
    private static $_prependPath = null;
    
    
    private static function _trimUrls($css)
    {
        return preg_replace('/
            url\\(      # url(
            \\s*
            ([^\\)]+?)  # 1 = URI (really just a bunch of non right parenthesis)
            \\s*
            \\)         # )
        /x', 'url($1)', $css);
    }
    
    
    private static function _uriCB($m)
    {
        $isImport = ($m[0][0] === '@');
        if ($isImport) {
            $quoteChar = $m[1];
            $uri = $m[2];
        } else {
            // is url()
            // $m[1] is either quoted or not
            $quoteChar = ($m[1][0] === "'" || $m[1][0] === '"')
                ? $m[1][0]
                : '';
            $uri = ($quoteChar === '')
                ? $m[1]
                : substr($m[1], 1, strlen($m[1]) - 2);
        }
        if ('/' !== $uri[0]) {
            if (strpos($uri, '//') > 0
                || 0 === strpos($uri, 'data:')
            ) {
                // probably starts with protocol, do not alter
            } else {
                // it's a file relative URI!
                // choose mode
                if (self::$_prependPath !== null) {
                    // prepend path
                    $uri = self::$_prependPath . $uri;
                } else {
                    // rewrite path
                    // prepend path with current dir separator (OS-independent)
                    $path =  strtr(self::$_currentDir, '/', DIRECTORY_SEPARATOR)  
                        . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
                    // "unresolve" a symlink back to doc root
                    foreach (self::$_symlinks as $link => $target) {
                        if (0 === strpos($path, $target)) {
                            // replace $target with $link
                            $path = $link . substr($path, strlen($target));
                            break;
                        }
                    }
                    // strip doc root
                    $path = substr($path, strlen(self::$_docRoot));
                    // fix to root-relative URI
                    $uri = strtr($path, DIRECTORY_SEPARATOR, '/');
                    // remove /./ and /../ where possible
                    $uri = str_replace('/./', '/', $uri);
                    // inspired by patch from Oleg Cherniy
                    do {
                        $uri = preg_replace('@/[^/]+/\\.\\./@', '/', $uri, -1, $changed);
                    } while ($changed);
                }
            }
        }
        if ($isImport) {
            return "@import {$quoteChar}{$uri}{$quoteChar}";
        } else {
            return "url({$quoteChar}{$uri}{$quoteChar})";
        }
    }
}
