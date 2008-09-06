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
     * @param array $options available options (none currently)
     * 
     * @return string 
     */
    public static function minify($js, $options = array()) 
    {
        return trim(JSMin::minify($js));    
    }
}

