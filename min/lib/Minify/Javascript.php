<?php
/**
 * Class Minify_Javascript  
 * @package Minify
 */

require 'JSMin.php';

/**
 * Compress Javascript using Ryan Grove's JSMin class
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Javascript {

    /**
     * Minify a Javascript string
     * 
     * @param string $js
     * 
     * @param array $options available options:
     * 
     * 'preserveComments': (default true) multi-line comments that begin
     * with "/*!" will be preserved with newlines before and after to
     * enhance readability.
     * 
     * @return string 
     */
    public static function minify($js, $options = array()) 
    {
        if (isset($options['preserveComments']) 
            && !$options['preserveComments']) {
            return trim(JSMin::minify($js));    
        }
        $ret = '';
        while (1) {
            list($beforeComment, $comment, $afterComment)
                = self::_nextYuiComment($js);
            $ret .= trim(JSMin::minify($beforeComment));
            if (false === $comment) {
                break;
            }
            $ret .= $comment;
            $js = $afterComment;
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
}

