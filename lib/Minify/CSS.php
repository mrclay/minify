<?php
/**
 * Class Minify_CSS  
 * @package Minify
 */

/**
 * Compress CSS
 *
 * This is a heavy regex-based removal of whitespace, unnecessary
 * comments and tokens, and some CSS value minimization, where practical.
 * Many steps have been taken to avoid breaking comment-based hacks, 
 * including the ie5/mac filter (and its inversion), but expect tricky
 * hacks involving comment tokens in 'content' value strings to break
 * minimization badly. A test suite is available.
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_CSS {
    
    /**
     * Minify a CSS string
     * 
     * @param string $css
     * 
     * @param array $options available options:
     * 
     * 'preserveComments': (default true) multi-line comments that begin
     * with "/*!" will be preserved with newlines before and after to
     * enhance readability.
     * 
     * @return string
     */
    public static function minify($css, $options = array()) 
    {
        if (isset($options['preserveComments']) 
            && !$options['preserveComments']) {
            return self::_minify($css, $options);    
        }
        $ret = '';
        while (1) {
            list($beforeComment, $comment, $afterComment)
                = self::_nextYuiComment($css);
            $ret .= self::_minify($beforeComment, $options);
            if (false === $comment) {
                break;
            }
            $ret .= $comment;
            $css = $afterComment;
        }
        return $ret;
    }

    /**
     * Extract comments that YUI Compressor preserves.
     * 
     * @param string $in input
     * 
     * @return array 3 elements are returned. If a YUI comment is found, the
     * 2nd element is the comment and the 1st and 2nd are the surrounding
     * strings. If no comment is found, the entire string is returned as the 
     * 1st element and the other two are false.
     */
    private static function _nextYuiComment($in)
    {
        return (
            (false !== ($start = strpos($in, '/*!')))
            && (false !== ($end = strpos($in, '*/', $start + 3)))
        )
            ? array(
                substr($in, 0, $start)
                ,"\n/*" . substr($in, $start + 3, $end - $start - 1) . "\n"
                ,substr($in, -(strlen($in) - $end - 2))
            )
            : array($in, false, false);
    }
    
    /**
     * Minify a CSS string
     * 
     * @param string $css
     * 
     * @param array $options To enable URL rewriting, set the value
     * for key 'prependRelativePath'.
     * 
     * @return string
     */
    protected static function _minify($css, $options) 
    {
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

        // compress whitespace.
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
        $css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i'
            , '$1#$2$3$4$5', $css);
        
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
        // $m is everything after the opening tokens and before the closing 
        // tokens but return will replace the entire comment.
        if ($m === 'keep') {
            return '/*keep*/';
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
        if ($m[0] === '/') {
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

