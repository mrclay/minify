<?php

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for minifying a set of files
 * 
 * E.g. the following would serve the minified Javascript for a site
 * <code>
 * $dr = $_SERVER['DOCUMENT_ROOT'];
 * Minify::serve('Files', array(
 *    $dr . '/js/jquery.js'
 *     ,$dr . '/js/plugins.js'
 *     ,$dr . '/js/site.js'
 * ));
 * </code>
 * 
 */
class Minify_Controller_Files extends Minify_Controller_Base {
    
    /**
     * @param array $spec array of full paths of files to be minified
     * 
     * @param array $options options to pass to Minify
     * 
     * @return null 
     */
    public function __construct($spec, $options = array()) {
        $sources = array();
        foreach ($spec as $file) {
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

