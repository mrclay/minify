<?php
/**
 * Class Minify_Controller_MinApp  
 * @package Minify
 */

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for requests to /min/index.php
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Controller_MinApp extends Minify_Controller_Base {
    
    /**
     * Set up groups of files as sources
     * 
     * @param array $options controller and Minify options
     * @return array Minify options
     * 
     */
    public function setupSources($options) {
        // filter controller options
        $cOptions = array_merge(
            array(
                'allowDirs' => '//'
                ,'groupsOnly' => false
                ,'groups' => array()
                ,'noMinPattern' => '@[-\\.]min\\.(?:js|css)$@i' // matched against basename
            )
            ,(isset($options['minApp']) ? $options['minApp'] : array())
        );
        unset($options['minApp']);
        $sources = array();
        if (isset($_GET['g'])) {
            // try groups
            if (! isset($cOptions['groups'][$_GET['g']])) {
                $this->log("A group configuration for \"{$_GET['g']}\" was not set");
                return $options;
            }

            $this->selectionId = "g=" . $_GET['g'];
            $files = $cOptions['groups'][$_GET['g']];
            // if $files is a single object, casting will break it
            if (is_object($files)) {
                $files = array($files);
            } elseif (! is_array($files)) {
                $files = (array)$files;
            }
            foreach ($files as $file) {
                if ($file instanceof Minify_Source) {
                    $sources[] = $file;
                    continue;
                }
                if (0 === strpos($file, '//')) {
                    $file = $_SERVER['DOCUMENT_ROOT'] . substr($file, 1);
                }
                $file = realpath($file);
                if (is_file($file)) {
                    $sources[] = $this->_getFileSource($file, $cOptions);
                } else {
                    $this->log("The path \"{$file}\" could not be found (or was not a file)");
                    return $options;
                }
            }
        } elseif (! $cOptions['groupsOnly'] && isset($_GET['f'])) {
            // try user files
            // The following restrictions are to limit the URLs that minify will
            // respond to. Ideally there should be only one way to reference a file.
            if (// verify at least one file, files are single comma separated, 
                // and are all same extension
                ! preg_match('/^[^,]+\\.(css|js)(?:,[^,]+\\.\\1)*$/', $_GET['f'], $m)
                // no "//"
                || strpos($_GET['f'], '//') !== false
                // no "\"
                || strpos($_GET['f'], '\\') !== false
                // no "./"
                || preg_match('/(?:^|[^\\.])\\.\\//', $_GET['f'])
            ) {
                $this->log("GET param 'f' invalid (see MinApp.php line 63)");
                return $options;
            }
            $ext = ".{$m[1]}";
            $files = explode(',', $_GET['f']);
            if ($files != array_unique($files)) {
                $this->log("Duplicate files specified");
                return $options;
            }

            if (isset($_GET['b'])) {
                // check for validity
                if (preg_match('@^[^/]+(?:/[^/]+)*$@', $_GET['b'])
                    && false === strpos($_GET['b'], '..')
                    && $_GET['b'] !== '.') {
                    // valid base
                    $base = "/{$_GET['b']}/";       
                } else {
                    $this->log("GET param 'b' invalid (see MinApp.php line 84)");
                    return $options;
                }
            } else {
                $base = '/';
            }
            $allowDirs = array();
            foreach ((array)$cOptions['allowDirs'] as $allowDir) {
                $allowDirs[] = realpath(str_replace('//', $_SERVER['DOCUMENT_ROOT'] . '/', $allowDir));
            }
            $basenames = array(); // just for cache id
            foreach ($files as $file) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $base . $file;
                $file = realpath($path);
                if (false === $file) {
                    $this->log("Path \"{$path}\" failed realpath()");
                    return $options;
                } elseif (! parent::_fileIsSafe($file, $allowDirs)) {
                    $this->log("Path \"{$path}\" failed Minify_Controller_Base::_fileIsSafe()");
                    return $options;
                } else {
                    $sources[] = $this->_getFileSource($file, $cOptions);
                    $basenames[] = basename($file, $ext);
                }
            }
            $this->selectionId = implode(',', $basenames) . $ext;
        }
        if ($sources) {
            $this->sources = $sources;
        } else {
            $this->log("No sources to serve");
        }
        return $options;
    }

    protected function _getFileSource($file, $cOptions)
    {
        $spec['filepath'] = $file;
        if ($cOptions['noMinPattern']
            && preg_match($cOptions['noMinPattern'], basename($file))) {
            $spec['minifier'] = '';
        }
        return new Minify_Source($spec);
    }
}
