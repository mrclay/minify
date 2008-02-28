<?php

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for serving predetermined groups of minimized sets, selected
 * by PATH_INFO
 * 
 * <code>
 * $dr = $_SERVER['DOCUMENT_ROOT'];
 * Minify::minify('Groups', array(
 *   'css' => array(
 *     $dr . '/css/type.css'
 *     ,$dr . '/css/layout.css'
 *   )
 *   ,'js' => array(
 *     $dr . '/js/jquery.js'
 *     ,$dr . '/js/plugins.js'
 *     ,$dr . '/js/site.js'
 *   )
 * ));
 * </code>
 * 
 * If the above code were placed in /serve.php, it would enable the URLs
 * /serve.php/js and /serve.php/css
 */
class Minify_Controller_Groups extends Minify_Controller_Base {
    
    /**
     * @param array $spec associative array of keys to arrays of file paths.
     * 
     * @param array $options optional options to pass to Minify
     * 
     * @return null 
     */
    public function __construct($spec, $options = array()) {
        $pi = substr($_SERVER['PATH_INFO'], 1);
        if (! isset($spec[$pi])) {
            // not a valid group
            return;
        }
        $sources = array();
        foreach ($spec[$pi] as $file) {
            $file = realpath($file);
            if (file_exists($file)) {
                $sources[] = new Minify_Source(array(
                    'filepath' => $file
                ));    
            } else {
                return;
            }
        }
        if ($sources) {
            $this->requestIsValid = true;
        }
        parent::__construct($sources, $options);
    }
}

