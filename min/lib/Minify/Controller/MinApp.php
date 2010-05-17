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
        $this->selectionId = '';
        $missingUri = '';
        
        if (isset($_GET['g'])) {
            // add group(s)
            $this->selectionId .= 'g=' . $_GET['g'];
            $keys = explode(',', $_GET['g']);
            if ($keys != array_unique($keys)) {
                $this->log("Duplicate group key found.");
                return $options;
            }
            foreach (explode(',', $_GET['g']) as $key) {
                if (! isset($cOptions['groups'][$key])) {
                    $this->log("A group configuration for \"{$key}\" was not found");
                    return $options;
                }
                $files = $cOptions['groups'][$key];
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
                    if ($file && is_file($file)) {
                        $sources[] = $this->_getFileSource($file, $cOptions);
                    } else {
                        $this->log("The path \"{$file}\" could not be found (or was not a file)");
                        return $options;
                    }
                }
                if ($sources) {
                    try {
                        $this->checkType($sources[0]);
                    } catch (Exception $e) {
                        $this->log($e->getMessage());
                        return $options;
                    }
                }
            }
        }
        if (! $cOptions['groupsOnly'] && isset($_GET['f'])) {
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
            try {
                $this->checkType($m[1]);
            } catch (Exception $e) {
                $this->log($e->getMessage());
                return $options;
            }
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
                $uri = $base . $file;
                $path = $_SERVER['DOCUMENT_ROOT'] . $uri;
                $file = realpath($path);
                if (false === $file || ! is_file($file)) {
                    if (! $missingUri) {
                        $missingUri = $uri;
                        continue;
                    } else {
                        $this->log("At least two files missing: '$missingUri', '$uri'");
                        return $options;
                    }
                }
                try {
                    parent::checkNotHidden($file);
                    parent::checkAllowDirs($file, $allowDirs, $uri);
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                    return $options;
                }
                $sources[] = $this->_getFileSource($file, $cOptions);
                $basenames[] = basename($file, $ext);
            }
            if ($this->selectionId) {
                $this->selectionId .= '_f=';
            }
            $this->selectionId .= implode(',', $basenames) . $ext;
        }
        if ($sources) {
            if ($missingUri) {
                array_unshift($sources, new Minify_Source(array(
                    'id' => 'missingFile'
                    ,'lastModified' => 0
                    ,'content' => "/* Minify: missing file '" . ltrim($missingUri, '/') . "' */\n"
                    ,'minifier' => ''
                )));
            }
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

    protected $_type = null;

    /*
     * Make sure that only source files of a single type are registered
     */
    public function checkType($sourceOrExt)
    {
        if ($sourceOrExt === 'js') {
            $type = Minify::TYPE_JS;
        } elseif ($sourceOrExt === 'css') {
            $type = Minify::TYPE_CSS;
        } elseif ($sourceOrExt->contentType !== null) {
            $type = $sourceOrExt->contentType;
        } else {
            return;
        }
        if ($this->_type === null) {
            $this->_type = $type;
        } elseif ($this->_type !== $type) {
            throw new Exception('Content-Type mismatch');
        }
    }
}
