<?php

/**
 * jsmin.php wrapper for Minify
 * @copyright 2008
 */

require dirname(__FILE__) . '/3rd_party/jsmin.php';

/** 
 * Minify Javascript using JSMin 
 */
class Minify_Javascript {
    public static function minify($js, $options = array()) {
        return trim(JSMin::minify($js));
    }
}

?>