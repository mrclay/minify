<?php
/**
 * Class Minify_Controller_Files  
 * @package Minify
 */

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for minifying a set of files
 * 
 * E.g. the following would serve the minified Javascript for a site
 * <code>
 * $dr = $_SERVER['DOCUMENT_ROOT'];
 * Minify::serve('Files', array(
 *     'files' => array(
 *         $dr . '/js/jquery.js'
 *         ,$dr . '/js/plugins.js'
 *         ,$dr . '/js/site.js'
 *     )
 * ));
 * </code>
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Controller_Files extends Minify_Controller_Base {
    
    /**
     * Set up file sources
     * 
     * @param array $options controller and Minify options
     * @return array Minify options
     * 
     * Controller options:
     * 
     * 'files': (required) array of complete file paths 
     */
    public function setupSources($options) {
        // strip controller options
        $files = $options['files'];
        unset($options['files']);
        
        $sources = array();
        foreach ($files as $file) {
            if ($file instanceof Minify_Source) {
                $sources[] = $file;
                continue;
            }
            $file = realpath($file);
            if (file_exists($file)) {
                $sources[] = new Minify_Source(array(
                    'filepath' => $file
                ));    
            } else {
                // file not found
                return $options;
            }
        }
        if ($sources) {
            $this->sources = $sources;
        }
        return $options;
    }
}

