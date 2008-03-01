<?php

//define('MINIFY_BASE_DIR', realpath($_SERVER['DOCUMENT_ROOT'] . '/_3rd_party'));

require '../config.php'; // just to set include_path
require 'Minify.php';
require 'Minify/Controller/Base.php';

if (!defined('MINIFY_BASE_DIR')) {
    // files cannot be served above this
    define('MINIFY_BASE_DIR', realpath($_SERVER['DOCUMENT_ROOT']));
}
if (!defined('MINIFY_CACHE_DIR')) {
    define('MINIFY_CACHE_DIR', sys_get_temp_dir());
}
if (!defined('MINIFY_ENCODING')) {
    define('MINIFY_ENCODING', 'utf-8');
}
if (!defined('MINIFY_MAX_FILES')) {
    define('MINIFY_MAX_FILES', 16);
}
if (!defined('MINIFY_REWRITE_CSS_URLS')) {
    define('MINIFY_REWRITE_CSS_URLS', true);
}
if (!defined('MINIFY_USE_CACHE')) {
    define('MINIFY_USE_CACHE', true);
}

class V1Controller extends Minify_Controller_Base {
    
    // setup $this->sources and return $options
    public function setupSources($options) {
        $options['badRequestHeader'] = 'HTTP/1.0 404 Not Found';
        $options['contentTypeCharset'] = MINIFY_ENCODING;

        // The following restrictions are to limit the URLs that minify will
        // respond to. Ideally there should be only one way to reference a file.
        if (! isset($_GET['files'])
            // verify at least one file, files are single comma separated, 
            // and are all same extension
            || ! preg_match('/^[^,]+\\.(css|js)(,[^,]+\\.\\1)*$/', $_GET['files'], $m)
            // no "//" (makes URL rewriting easier)
            || strpos($_GET['files'], '//') !== false
            // no "\"
            || strpos($_GET['files'], '\\') !== false
            // no "./"
            || preg_match('/(?:^|[^\\.])\\.\\//', $_GET['files'])
        ) {
            return $options;
        }
        $extension = $m[1];
        
        $files = explode(',', $_GET['files']);
        if (count($files) > MINIFY_MAX_FILES) {
            return $options;
        }
        
        // strings for prepending to relative/absolute paths
        $prependRelPaths = dirname($_SERVER['SCRIPT_FILENAME'])
            . DIRECTORY_SEPARATOR;
        $prependAbsPaths = $_SERVER['DOCUMENT_ROOT'];
        
        $sources = array();
        $goodFiles = array();
        $hasBadSource = false;
        foreach ($files as $file) {
            // prepend appropriate string for abs/rel paths
            $file = ($file[0] === '/' ? $prependAbsPaths : $prependRelPaths) . $file;
            // make sure a real file!
            $file = realpath($file);
            // don't allow unsafe or duplicate files
            if (parent::_fileIsSafe($file, MINIFY_BASE_DIR) 
                && !in_array($file, $goodFiles)) 
            {
                $goodFiles[] = $file;
                $srcOptions = array(
                    'filepath' => $file
                );
                if ('css' === $extension && MINIFY_REWRITE_CSS_URLS) {
                    $srcOptions['minifyOptions']['currentPath'] = dirname($file);
                }
                $this->sources[] = new Minify_Source($srcOptions);
            } else {
                $hasBadSource = true;
                break;
            }
        }
        if ($hasBadSource) {
            $this->sources = array();
        }
        return $options;
    }
}

$v1 = new V1Controller();
if (MINIFY_USE_CACHE) {
    Minify::useServerCache(MINIFY_CACHE_DIR);
}
Minify::serve($v1);