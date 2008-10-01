<?php
/**
 * Class Minify_CSS_UriRewriter  
 * @package Minify
 */

/**
 * Rewrite file-relative URIs as root-relative in CSS files
 *
 * @todo: prepend() method
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_CSS_UriRewriter {
    
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
     * @return string
     */
    public static function rewrite($css, $currentDir, $docRoot = null) 
    {
        self::$_docRoot = $docRoot
            ? $docRoot
            : $_SERVER['DOCUMENT_ROOT'];
        self::$_docRoot = realpath(self::$_docRoot);
        self::$_currentDir = realpath($currentDir);
        
        // remove ws around urls
        $css = preg_replace('/
                url\\(      # url(
                \\s*
                ([^\\)]+?)  # 1 = URI (really just a bunch of non right parenthesis)
                \\s*
                \\)         # )
            /x', 'url($1)', $css);
        
        // rewrite
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'
            ,array('Minify_CSS_UriRewriter', '_uriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
            ,array('Minify_CSS_UriRewriter', '_uriCB'), $css);

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
            if (strpos($uri, '//') > 0) {
                // probably starts with protocol, do not alter
            } else {
                // it's a file relative URI!
                // prepend path with current dir separator (OS-independent)
                $path =  strtr(self::$_currentDir, '/', DIRECTORY_SEPARATOR)  
                    . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
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
        if ($isImport) {
            return "@import {$quoteChar}{$uri}{$quoteChar}";
        } else {
            return "url({$quoteChar}{$uri}{$quoteChar})";
        }
    }
}
