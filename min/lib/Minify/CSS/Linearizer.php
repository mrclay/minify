<?php
/**
 * Class Minify_CSS_Linearizer  
 * @package Minify
 */

/**
 * Linearize a CSS file
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_CSS_Linearizer {
    
    public static $filesIncluded = array();
    
    public static function linearize($file)
    {
        self::$filesIncluded = array();
        $obj = new Minify_CSS_Linearizer(dirname($file));
        return $obj->_getStyles($file);
    }
    
    // allows callback funcs to know the current directory
    private $_currentDir = null;
    
    // allows _importCB to write the fetched content back to the obj
    private $_importedCss = '';
    
    private function __construct($currentDir)
    {
        $this->_currentDir = $currentDir;
    }
    
    private function _getStyles($file)
    {
        if (false === ($css = @file_get_contents($file))) {
            return '';
        }
        self::$filesIncluded[] = realpath($file);
        $this->_currentDir = dirname($file);
        
        // remove UTF-8 BOM if present
        if (pack("CCC",0xef,0xbb,0xbf) === substr($css, 0, 3)) {
            $css = substr($css, 3);
        }
        // ensure uniform EOLs
        $css = str_replace("\r\n", "\n", $css);
        
        // make copy w/ comments removed
        $copy = preg_replace('@/\\*.*?\\*/@', '', $css);
        
        // process remaining @imports (we work on copy because we don't want to 
        // pull in @imports that have been commented out). the replacement
        // result is unimportant; we'd use "preg_match_callback" if it existed.
        preg_replace_callback(
            '/
                @import\\s+
                (?:url\\(\\s*)?      # maybe url(
                [\'"]?               # maybe quote
                (.*?)                # 1 = URI
                [\'"]?               # maybe end quote
                (?:\\s*\\))?         # maybe )
                ([a-zA-Z,\\s]*)?     # 2 = media list
                ;                    # end token
            /x'
            ,array($this, '_importCB')
            ,$copy
        );
        
        unset($copy); // copy served its purpose

        // on original, strip all imports (we don't know which were successfull
        // and they aren't allowed to appear below the top anyway).
        $css = preg_replace(
            '/
                @import\\s+
                (?:url\\(\\s*)?[\'"]?
                .*?
                [\'"]?(?:\\s*\\))?
                [a-zA-Z,\\s]*
                ;
            /x'
            ,''
            ,$css
        );
        
        // rewrite remaining relative URIs
        $css = preg_replace_callback(
            '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
            ,array($this, '_urlCB')
            ,$css
        );
        
        return $this->_importedCss . $css;
    }
    
    private function _importCB($m)
    {
        $url = $m[1];
        $mediaList = preg_replace('/\\s+/', '', $m[2]);
        
        if (strpos($url, '://') > 0) {
            // protocol, external content will not be fetched
            return '';
        }
        if ('/' === $url[0]) {
            // protocol-relative or root path
            $url = ltrim($url, '/');
            $file = realpath($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR
                . strtr($url, '/', DIRECTORY_SEPARATOR);
        } else {
            // relative to current path
            $file = $this->_currentDir . DIRECTORY_SEPARATOR 
                . strtr($url, '/', DIRECTORY_SEPARATOR);
        }
        $obj = new Minify_CSS_Linearizer(dirname($file));
        $css = $obj->_getStyles($file);
        if ('' === $css) {
            // failed
            return '';
        }
        $this->_importedCss .= preg_match('@(?:^$|\\ball\\b)@', $mediaList)
            ? $css
            : "@media {$mediaList} {\n{$css}\n}\n";
        return '';
    }
    
    private function _urlCB($m)
    {
        // $m[1] is either quoted or not
        $quote = ($m[1][0] === "'" || $m[1][0] === '"')
            ? $m[1][0]
            : '';
        $url = ($quote === '')
            ? $m[1]
            : substr($m[1], 1, strlen($m[1]) - 2);
        if ('/' !== $url[0]) {
            if (strpos($url, '//') > 0) {
                // probably starts with protocol, do not alter
            } else {
                // prepend path with current dir separator (OS-independent)
                $path = $this->_currentDir 
                    . DIRECTORY_SEPARATOR . strtr($url, '/', DIRECTORY_SEPARATOR);
                // strip doc root
                $path = substr($path, strlen($_SERVER['DOCUMENT_ROOT']));
                // fix to absolute URL
                $url = strtr($path, DIRECTORY_SEPARATOR, '/');
                $url = str_replace('/./', '/', $url);
            }
        }
        return "url({$quote}{$url}{$quote})";
    }
}
