<?php
/**
 * Class Minify_Javascript  
 * @package Minify
 */

require dirname(__FILE__) . '/3rd_party/jsmin.php';

/**
 * Compress Javascript using Ryan Grove's JSMin class
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Javascript {
    public static function minify($js, $options = array()) {
        return trim(JSMin::minify($js));
    }
}

