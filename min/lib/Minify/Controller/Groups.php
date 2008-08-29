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
 * Minify::serve('Groups', array( 
 *     'groups' => array(
 *         'css' => array('//css/type.css', '//css/layout.css')
 *        ,'js' => array('//js/jquery.js', '//js/site.js')
 *     )
 * ));
 * </code>
 * 
 * If the above code were placed in /serve.php, it would enable the URLs
 * /serve.php/js and /serve.php/css
 * 
 * As a shortcut, the controller will replace "//" at the beginning
 * of a filename with $_SERVER['DOCUMENT_ROOT'] . '/'.
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
        
        // mod_fcgid places PATH_INFO in ORIG_PATH_INFO
        $pi = isset($_SERVER['ORIG_PATH_INFO'])
            ? substr($_SERVER['ORIG_PATH_INFO'], 1) 
            : (isset($_SERVER['PATH_INFO'])
                ? substr($_SERVER['PATH_INFO'], 1) 
                : false
            );
        if (false === $pi || ! isset($groups[$pi])) {
            // no PATH_INFO or not a valid group
            return $options;
        }
        $sources = array();
        foreach ((array)$groups[$pi] as $file) {
            if ($file instanceof Minify_Source) {
                $sources[] = $file;
                continue;
            }
            if (0 === strpos($file, '//')) {
                $file = $_SERVER['DOCUMENT_ROOT'] . substr($file, 1);
            }
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

