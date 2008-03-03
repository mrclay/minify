<?php

/**
 * "Minify" CSS
 *
 * This is a heavy regex-based removal of whitespace, unnecessary
 * comments and tokens, and some CSS value minimization, where practical.
 * Many steps have been taken to avoid breaking comment-based hacks, 
 * including the ie5/mac filter (and its inversion), but expect hacks 
 * involving comment tokens in 'content' value strings to break minimization
 * badly. A test suite is available
 */
class Minify_CSS {
    
    /**
     * Minify a CSS string
     * 
     * @param string $css
     * 
     * @param array $options optional. To enable URL rewriting, set the value
     * for key 'prependRelativePath'.
     * 
     * @return string
     */
    public static function minify($css, $options = array()) {
        // preserve empty comment after '>'
        // http://www.webdevout.net/css-hacks#in_css-selectors
        $css = preg_replace('/>\\/\\*\\s*\\*\\//', '>/*keep*/', $css);
        
        // preserve empty comment between property and value
        // http://css-discuss.incutio.com/?page=BoxModelHack
        $css = preg_replace('/\\/\\*\\s*\\*\\/\\s*:/', '/*keep*/:', $css);
        $css = preg_replace('/:\\s*\\/\\*\\s*\\*\\//', ':/*keep*/', $css);
        
        // apply callback to all valid comments (and strip out surrounding ws
        self::$_inHack = false;
        $css = preg_replace_callback('/\\s*\\/\\*([\\s\\S]*?)\\*\\/\\s*/'
            ,array('Minify_CSS', '_commentCB'), $css);

        // compress whitespace. Yes, this will affect "copyright" comments.
        $css = preg_replace('/\s+/', ' ', $css);

        // leave needed comments
        $css = str_replace('/*keep*/', '/**/', $css);
        
        // remove ws around { }
        $css = preg_replace('/\\s*{\\s*/', '{', $css);
        $css = preg_replace('/;?\\s*}\\s*/', '}', $css);
        
        // remove ws between rules
        $css = preg_replace('/\\s*;\\s*/', ';', $css);
        
        // remove ws around urls
        $css = preg_replace('/url\\([\\s]*([^\\)]+?)[\\s]*\\)/', 'url($1)', $css);
        
        // remove ws between rules and colons
        $css = preg_replace('/\\s*([{;])\\s*([\\w\\-]+)\\s*:\\s*\\b/', '$1$2:', $css);
        
        // remove ws in selectors
        $css = preg_replace_callback('/(?:\\s*[^~>+,\\s]+\\s*[,>+~])+\\s*[^~>+,\\s]+{/'
            ,array('Minify_CSS', '_selectorsCB'), $css);
        
        // minimize hex colors
        $css = preg_replace('/#([a-f\\d])\\1([a-f\\d])\\2([a-f\\d])\\3([\\s;\\}])/i'
            , '#$1$2$3$4', $css);
        
        $rewrite = false;
        if (isset($options['prependRelativePath'])) {
            self::$_tempPrepend = $options['prependRelativePath'];
            $rewrite = true;
        } elseif (isset($options['currentPath'])) {
            self::$_tempCurrentPath = $options['currentPath'];
            $rewrite = true;
        }
        if ($rewrite) {
            $css = preg_replace_callback('/@import ([\'"])(.*?)[\'"]\\s*;/'
                ,array('Minify_CSS', '_urlCB'), $css);
            $css = preg_replace_callback('/url\\(([^\\)]+)\\)/'
                ,array('Minify_CSS', '_urlCB'), $css);
        }
        self::$_tempPrepend = self::$_tempCurrentPath = '';
        return trim($css);
    }
    
    /**
     * @var bool Are we "in" a hack? 
     * 
     * I.e. are some browsers targetted until the next comment?   
     */
    protected static $_inHack = false;
    
    /**
     * @var string string to be prepended to relative URIs   
     */
    protected static $_tempPrepend = '';
    
    /**
     * @var string path of this stylesheet for rewriting purposes   
     */
    protected static $_tempCurrentPath = '';
    
    /**
     * Process what looks like a comment and return a replacement
     * 
     * @param array $m regex matches
     * 
     * @return string   
     */
    protected static function _commentCB($m)
    {
        $m = $m[1]; 
        // $m is everything after the opening tokens and before the closing tokens
        // but return will replace the entire comment.
        if ($m === 'keep') {
            return '/*keep*/';
        }
        if (false !== strpos($m, 'copyright')) {
            // contains copyright, preserve
            self::$_inHack = false;
            return "/*{$m}*/";
        }
        if (self::$_inHack) {
            // inversion: feeding only to one browser
            if (preg_match('/^\\/\\s*(\\S[\\s\\S]+?)\\s*\\/\\*/', $m, $n)) {
                self::$_inHack = false;
                return "/*/{$n[1]}/*keep*/";
            }
        }
        if (substr($m, -1) === '\\') {
            self::$_inHack = true;
            return '/*\\*/';
        }
        if (substr($m, 0, 1) === '/') {
            self::$_inHack = true;
            return '/*/*/';
        }
        if (self::$_inHack) {
            self::$_inHack = false;
            return '/*keep*/';
        }
        return '';
    }
    
    /**
     * Replace what looks like a set of selectors  
     *
     * @param array $m regex matches
     * 
     * @return string
     */
    protected static function _selectorsCB($m)
    {
        return preg_replace('/\\s*([,>+~])\\s*/', '$1', $m[0]);
    }
    
    protected static function _urlCB($m)
    {
        $isImport = (0 === strpos($m[0], '@import'));
        if ($isImport) {
            $quote = $m[1];
            $url = $m[2];
        } else {
            // $m[1] is surrounded by quotes or not
            $quote = ($m[1][0] === '\'' || $m[1][0] === '"')
                ? $m[1][0]
                : '';
            $url = ($quote === '')
                ? $m[1]
                : substr($m[1], 1, strlen($m[1]) - 2);
        }
        if ('/' !== $url[0]) {
            if (strpos($url, '//') > 0) {
                // probably starts with protocol, do not alter
            } else {
                // relative URI, rewrite!
                if (self::$_tempPrepend) {
                    $url = self::$_tempPrepend . $url;    
                } else {
                    // rewrite absolute url from scratch!
                    // prepend path with current dir separator (OS-independent)
                    $path = self::$_tempCurrentPath 
                        . DIRECTORY_SEPARATOR . strtr($url, '/', DIRECTORY_SEPARATOR);
                    // strip doc root
                    $path = substr($path, strlen($_SERVER['DOCUMENT_ROOT']));
                    // fix to absolute URL
                    $url = strtr($path, DIRECTORY_SEPARATOR, '/');
                    $url = str_replace('/./', '/', $url);
                }
            }
        }
        if ($isImport) {
            return "@import {$quote}{$url}{$quote};";
        } else {
            return "url({$quote}{$url}{$quote})";
        }
    }
}

