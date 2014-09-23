<?php
/**
 * Class Minify_Controller_Files  
 * @package Minify
 */

/**
 * Controller class for minifying a set of files
 * 
 * E.g. the following would serve the minified Javascript for a site
 * <code>
 * Minify::serve('Files', array(
 *     'files' => array(
 *         '//js/jquery.js'
 *         ,'//js/plugins.js'
 *         ,'/home/username/file.js'
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
     * @return Minify_ServeConfiguration
     * 
     * Controller options:
     * 
     * 'files': (required) array of complete file paths, or a single path
     */
    public function createConfiguration(array $options) {
        // strip controller options
        
        $files = $options['files'];
        // if $files is a single object, casting will break it
        if (is_object($files)) {
            $files = array($files);
        } elseif (! is_array($files)) {
            $files = (array)$files;
        }
        unset($options['files']);
        
        $sources = array();
        foreach ($files as $file) {
            if ($file instanceof Minify_SourceInterface) {
                $sources[] = $file;
                continue;
            }
            try {
                $sources[] = $this->sourceFactory->makeSource(array(
                    'filepath' => $file,
                ));
            } catch (Minify_Source_FactoryException $e) {
                $this->log($e->getMessage());
                return new Minify_ServeConfiguration($options);
            }
        }
        return new Minify_ServeConfiguration($options, $sources);
    }
}

