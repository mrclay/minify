<?php
/**
 * Class Minify_Controller_Groups  
 * @package Minify
 */

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for serving predetermined groups of minimized sets, selected
 * by PATH_INFO
 * 
 * <code>
 * $dr = $_SERVER['DOCUMENT_ROOT'];
 * Minify::serve('Groups', array( 
 *     'groups' => array(
 *         'css' => array($dr . '/css/type.css', $dr . '/css/layout.css')
 *        ,'js' => array($dr . '/js/jquery.js', $dr . '/js/site.js')
 *     )
 * ));
 * </code>
 * 
 * If the above code were placed in /serve.php, it would enable the URLs
 * /serve.php/js and /serve.php/css
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Controller_Groups extends Minify_Controller_Base {
    
    /**
     * Set up groups of files as sources
     * 
     * @param array $options controller and Minify options
     * @return array Minify options
     * 
     * Controller options:
     * 
     * 'groups': (required) array mapping PATH_INFO strings to arrays
     * of complete file paths. @see Minify_Controller_Groups 
     */
    public function setupSources($options) {
        // strip controller options
        $groups = $options['groups'];
        unset($options['groups']);
        
        $pi = substr($_SERVER['PATH_INFO'], 1);
        if (! isset($groups[$pi])) {
            // not a valid group
            return $options;
        }
        $sources = array();
        foreach ($groups[$pi] as $file) {
            $file = realpath($file);
            if (file_exists($file)) {
                $sources[] = new Minify_Source(array(
                    'filepath' => $file
                ));    
            } else {
                // file doesn't exist
                return $options;
            }
        }
        if ($sources) {
            $this->sources = $sources;
        }
        return $options;
    }
}

