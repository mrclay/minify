<?php
/**
 * Class Minify_Controller_Files
 * @package Minify
 */

use Monolog\Logger;

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
class Minify_Controller_Files extends Minify_Controller_Base
{

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
    public function createConfiguration(array $options)
    {
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
            try {
                $sources[] = $this->sourceFactory->makeSource($file);
            } catch (Minify_Source_FactoryException $e) {
                $this->logger->error($e->getMessage());

                return new Minify_ServeConfiguration($options);
            }
        }

        return new Minify_ServeConfiguration($options, $sources);
    }
}
