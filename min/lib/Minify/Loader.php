<?php
/**
 * Class Minify_Loader
 * @package Minify
 */

/**
 * Class autoloader
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 *
 * @deprecated 2.3 This will be removed in Minify 3.0
 */
class Minify_Loader {
    public function loadClass($class)
    {
        $file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        $file .= strtr($class, "\\_", DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR) . '.php';
        if (is_readable($file)) {
            require $file;
        }
    }

    /**
     * @deprecated 2.3 This will be removed in Minify 3.0
     */
    static public function register()
    {
        $inst = new self();
        spl_autoload_register(array($inst, 'loadClass'));
    }
}
