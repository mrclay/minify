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
     * @param array $options controller and Minify options
     *
     * @return array Minify options
     */
    public function createConfiguration(array $options) {
        // PHP insecure by default: realpath() and other FS functions can't handle null bytes.
        $get = $this->env->get();
        foreach (array('g', 'b', 'f') as $key) {
            if (isset($get[$key])) {
                $get[$key] = str_replace("\x00", '', (string)$get[$key]);
            }
        }

        // filter controller options
        $localOptions = array_merge(
            array(
                'groupsOnly' => false,
                'groups' => array(),
                'symlinks' => array(),
            )
            ,(isset($options['minApp']) ? $options['minApp'] : array())
        );
        unset($options['minApp']);

        // normalize $symlinks in order to map to target
        $symlinks = array();
        foreach ($localOptions['symlinks'] as $link => $target) {
            if (0 === strpos($link, '//')) {
                $link = rtrim(substr($link, 1), '/') . '/';
                $target = rtrim($target, '/\\');
                $symlinks[$link] = $target;
            }
        }

        $sources = array();
        $selectionId = '';
        $firstMissingResource = null;

        if (isset($get['g'])) {
            // add group(s)
            $selectionId .= 'g=' . $get['g'];
            $keys = explode(',', $get['g']);
            if ($keys != array_unique($keys)) {
                $this->log("Duplicate group key found.");
                return new Minify_ServeConfiguration($options);
            }
            foreach ($keys as $key) {
                if (! isset($localOptions['groups'][$key])) {
                    $this->log("A group configuration for \"{$key}\" was not found");
                    return new Minify_ServeConfiguration($options);
                }
                $files = $localOptions['groups'][$key];
                // if $files is a single object, casting will break it
                if (is_object($files)) {
                    $files = array($files);
                } elseif (! is_array($files)) {
                    $files = (array)$files;
                }
                foreach ($files as $file) {
                    if ($file instanceof Minify_SourceInterface) {
                        $sources[] = $file;
                        continue;
                    }
                    try {
                        $source = $this->sourceFactory->makeSource(array(
                            'filepath' => $file,
                        ));
                        $sources[] = $source;
                    } catch (Minify_Source_FactoryException $e) {
                        $this->log($e->getMessage());
                        if (null === $firstMissingResource) {
                            $firstMissingResource = basename($file);
                            continue;
                        } else {
                            $secondMissingResource = basename($file);
                            $this->log("More than one file was missing: '$firstMissingResource', '$secondMissingResource'");
                            return new Minify_ServeConfiguration($options);
                        }
                    }
                }
            }
        }
        if (! $localOptions['groupsOnly'] && isset($get['f'])) {
            // try user files
            // The following restrictions are to limit the URLs that minify will
            // respond to.
            if (// verify at least one file, files are single comma separated, 
                // and are all same extension
                ! preg_match('/^[^,]+\\.(css|less|js)(?:,[^,]+\\.\\1)*$/', $get['f'], $m)
                // no "//"
                || strpos($get['f'], '//') !== false
                // no "\"
                || strpos($get['f'], '\\') !== false
            ) {
                $this->log("GET param 'f' was invalid");
                return new Minify_ServeConfiguration($options);
            }
            $ext = ".{$m[1]}";
            $files = explode(',', $get['f']);
            if ($files != array_unique($files)) {
                $this->log("Duplicate files were specified");
                return new Minify_ServeConfiguration($options);
            }
            if (isset($get['b'])) {
                // check for validity
                if (preg_match('@^[^/]+(?:/[^/]+)*$@', $get['b'])
                        && false === strpos($get['b'], '..')
                        && $get['b'] !== '.') {
                    // valid base
                    $base = "/{$get['b']}/";
                } else {
                    $this->log("GET param 'b' was invalid");
                    return new Minify_ServeConfiguration($options);
                }
            } else {
                $base = '/';
            }

            $basenames = array(); // just for cache id
            foreach ($files as $file) {
                $uri = $base . $file;
                $path = $this->env->getDocRoot() . $uri;

                // try to rewrite path
                foreach ($symlinks as $link => $target) {
                    if (0 === strpos($uri, $link)) {
                        $path = $target . DIRECTORY_SEPARATOR . substr($uri, strlen($link));
                        break;
                    }
                }

                try {
                    $source = $this->sourceFactory->makeSource(array(
                        'filepath' => $path,
                    ));
                    $sources[] = $source;
                    $basenames[] = basename($path, $ext);
                } catch (Minify_Source_FactoryException $e) {
                    $this->log($e->getMessage());
                    if (null === $firstMissingResource) {
                        $firstMissingResource = $uri;
                        continue;
                    } else {
                        $secondMissingResource = $uri;
                        $this->log("More than one file was missing: '$firstMissingResource', '$secondMissingResource`'");
                        return new Minify_ServeConfiguration($options);
                    }
                }
            }
            if ($selectionId) {
                $selectionId .= '_f=';
            }
            $selectionId .= implode(',', $basenames) . $ext;
        }

        if (!$sources) {
            $this->log("No sources to serve");
            return new Minify_ServeConfiguration($options);
        }

        if (null !== $firstMissingResource) {
            array_unshift($sources, new Minify_Source(array(
                'id' => 'missingFile'
                // should not cause cache invalidation
                ,'lastModified' => 0
                // due to caching, filename is unreliable.
                ,'content' => "/* Minify: at least one missing file. See " . Minify::URL_DEBUG . " */\n"
                ,'minifier' => ''
            )));
        }

        return new Minify_ServeConfiguration($options, $sources, $selectionId);
    }
}
