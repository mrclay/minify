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
        require_once 'Minify/CommentPreserver.php';
        // recursive calls don't preserve comments
        $options['preserveComments'] = false;
        return Minify_CommentPreserver::process(
            $js
            ,array('Minify_Javascript', 'minify')
            ,array($options)
        );
    }
}

