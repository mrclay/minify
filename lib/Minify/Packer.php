<?php
/**
 * Class Minify_Packer
 * 
 * @package Minify  
 */

require dirname(__FILE__) . '/3rd_party/packer.php';

/**
 * Minify Javascript using Dean Edward's Packer
 * 
 * @package Minify
 */
class Minify_Packer {
    public static function minify($code, $options = array())
    {
        // @todo: set encoding options based on $options :)
        $packer = new JavascriptPacker($code, 'Normal', true, false);
        return trim($packer->pack());
    }
}
