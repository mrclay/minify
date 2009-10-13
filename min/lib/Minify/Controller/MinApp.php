<?php
/**
 * Class Minify_Controller_MinApp  
 * @package Minify
 */

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
     * If $_GET['g'] is set, it's split with "," and each piece is looked up as
     * a key in groupsConfig.php, and the found arrays are added to the sources.
     *
     * If $_GET['f'] is set and $options['groupsOnly'] is false, sources are
     * added based on validity of name and location.
     * 
     * Caveat: Groups always come before files, but the groups are ordered as
     * requested. E.g. 
     * /f=file.js&g=js1,js2 == /g=js1,js2&f=file.js != /g=js2,js1&f=file.js
     * 
     * Caveat: If JS group(s) are specified, the method will still allow you to 
     * add CSS files and vice-versa. E.g.:
     * /g=js1,js2&f=site.css => Minify will try to process site.css through the
     * Javascript minifier, possibly causing an exception.
     * 
     * @param array $options controller and Minify options
     * @return array Minify options
     */
    public function setupSources($options) {
        // filter controller options
        $cOptions = array_merge(
            array(
                'allowDirs' => '//'
                ,'groupsOnly' => false
                ,'groups' => array()
                ,'maxFiles' => 10                
            )
            ,(isset($options['minApp']) ? $options['minApp'] : array())
        );
        unset($options['minApp']);
        $sources = array();
        if (isset($_GET['g'])) {
            // try groups
            $files = array();
            $keys = explode(',', $_GET['g']);
            // check for duplicate key
            // note: no check for duplicate files
            if ($keys != array_unique($keys)) {
                $this->log("Duplicate key given in \"{$_GET['g']}\"");
                return $options;
            }
            foreach ($keys as $key) {
                if (! isset($cOptions['groups'][$key])) {
                    $this->log("A group configuration for \"{$key}\" was not set");
                    return $options;
                }
                $groupFiles = $cOptions['groups'][$key];
                if (is_array($groupFiles)) {
                    array_splice($files, count($files), 0, $groupFiles);
                } else {
                    $files[] = $groupFiles;
                }
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
                    $sources[] = new Minify_Source(array(
                        'filepath' => $file
                    ));    
                } else {
                    $this->log("The path \"{$file}\" could not be found (or was not a file)");
                    return $options;
                }
            }
        } 
        if (! $cOptions['groupsOnly'] && isset($_GET['f'])) {
            // try user files
            // The following restrictions are to limit the URLs that minify will
            // respond to. Ideally there should be only one way to reference a file.
            if (// verify at least one file, files are single comma separated, 
                // and are all same extension
                ! preg_match('/^[^,]+\\.(css|js)(?:,[^,]+\\.\\1)*$/', $_GET['f'])
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
            $files = explode(',', $_GET['f']);
            if (count($files) > $cOptions['maxFiles'] || $files != array_unique($files)) {
                $this->log("Too many or duplicate files specified");
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
                    $sources[] = new Minify_Source(array(
                        'filepath' => $file
                    ));
                }
            }
        }
        if ($sources) {
            $this->sources = $sources;
        } else {
            $this->log("No sources to serve");
        }
        return $options;
    }
}
