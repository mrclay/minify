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
 * $options = [
 *     'checkAllowDirs' => false, // allow files to be anywhere
 * ];
 * $sourceFactory = new Minify_Source_Factory($env, $options, $cache);
 * $controller = new Minify_Controller_Files($env, $sourceFactory);
 * $minify->serve($controller, [
 *     'files' => [
 *         '//js/jquery.js',
 *         '//js/plugins.js',
 *         '/home/username/file.js',
 *     ],
 * ]);
 * </code>
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Controller_Files extends Minify_Controller_Base {

    /**
     * @param Minify_Env            $env           Environment
     * @param Minify_Source_Factory $sourceFactory Source factory. If you need to serve files from any path, this
     *                                             component must have its "checkAllowDirs" option set to false.
     */
    public function __construct(Minify_Env $env, Minify_Source_Factory $sourceFactory)
    {
        parent::__construct($env, $sourceFactory);
    }

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

