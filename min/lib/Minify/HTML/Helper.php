<?php
/**
 * Class Minify_HTML_Helper
 * @package Minify
 */

/**
 * Helpers for writing Minfy URIs into HTML
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_HTML_Helper {
    public $rewriteWorks = true;
    public $minAppUri = '/min';

    public static function groupUri($key, $farExpires = true, $debug = false, $charset = 'UTF-8')
    {
        $h = new self;
        $h->setGroup($key, $farExpires);
        $uri = $h->getRawUri($farExpires, $debug);
        return htmlspecialchars($uri, ENT_QUOTES, $charset);
    }

    public static function filesUri($files, $farExpires = true, $debug = false, $charset = 'UTF-8')
    {
        $h = new self;
        $h->setFiles($files, $farExpires);
        $uri = $h->getRawUri($farExpires, $debug);
        return htmlspecialchars($uri, ENT_QUOTES, $charset);
    }

    /*
     * Get URI (not html-escaped) to minify a group/set of files
     */
    public function getRawUri($farExpires = true, $debug = false)
    {
        $path = rtrim($this->minAppUri, '/') . '/';
        if (! $this->rewriteWorks) {
            $path .= '?';
        }
        if (null === $this->_groupKey) {
            // @todo: implement shortest uri
            $path .= "f=" . $this->_fileList;
        } else {
            $path .= "g=" . $this->_groupKey;
        }
        if ($debug) {
            $path .= "&debug";
        } elseif ($farExpires && $this->_lastModified) {
            $path .= "&" . $this->_lastModified;
        }
        return $path;
    }

    public function setFiles($files, $checkLastModified = true)
    {
        $this->_groupKey = null;
        if ($checkLastModified) {
            $this->_sniffLastModified($files);
        }
        // normalize paths like in /min/f=<paths>
        foreach ($files as $k => $file) {
            if (0 === strpos($file, '//')) {
                $file = substr($file, 2);
            } elseif (0 === strpos($file, '/')
                      || 1 === strpos($file, ':\\')) {
                $file = substr($file, strlen($_SERVER['DOCUMENT_ROOT']) + 1);
            }
            $file = strtr($file, '\\', '/');
            $files[$k] = $file;
        }
        $this->_fileList = implode(',', $files);
    }

    public function setGroup($key, $checkLastModified = true)
    {
        $this->_groupKey = $key;
        if ($checkLastModified) {
            $gcFile = (null === $this->_groupsConfigFile)
                ? dirname(dirname(dirname(dirname(__FILE__)))) . '/groupsConfig.php'
                : $this->_groupsConfigFile;
            if (is_file($gcFile)) {
                $gc = (require $gcFile);
                if (isset($gc[$key])) {
                    $this->_sniffLastModified($gc[$key]);
                }
            }
        }
    }

    public function setPathToMin($path)
    {
        if (0 === strpos($path, '.')) {
            // relative path
            $path = dirname(__FILE__) . "/" . $path;
        }
        $file = realpath(rtrim($path, '/\\') . '/groupsConfig.php');
        if (! $file) {
            return false;
        }
        $this->_groupsConfigFile = $file;
        return true;
    }


    protected $_groupKey = null; // if present, URI will be like g=...
    protected $_fileList = '';
    protected $_groupsConfigArray = array();
    protected $_groupsConfigFile = null;
    protected $_lastModified = null;

    protected function _sniffLastModified($sources)
    {
        $max = 0;
        foreach ((array)$sources as $source) {
            if ($source instanceof Minify_Source) {
                $max = max($max, $source->lastModified);
            } elseif (is_string($source)) {
                if (0 === strpos($source, '//')) {
                    $source = $_SERVER['DOCUMENT_ROOT'] . substr($source, 1);
                }
                if (is_file($source)) {
                    $max = max($max, filemtime($source));
                }
            }
        }
        $this->_lastModified = $max;
    }

    /**
     * In a given array of strings, find the character they all have at
     * a particular index
     * @param Array arr array of strings
     * @param Number pos index to check
     * @return mixed a common char or '' if any do not match
     */
    protected static function _getCommonCharAtPos($arr, $pos) {
        $l = count($arr);
        $c = $arr[0][$pos];
        if ($c === '' || $l === 1)
            return $c;
        for ($i = 1; $i < l; ++$i)
            if ($arr[$i][$pos] !== $c)
                return '';
        return $c;
    }

    /**
     * Get the shortest URI to minify the set of source files
     * @param Array sources URIs
     *//*
    ,getBestUri : function (sources) {
        var pos = 0
           ,base = ''
           ,c;
        while (true) {
            c = MUB.getCommonCharAtPos(sources, pos);
            if (c === '')
                break;
            else
                base += c;
            ++pos;
        }
        base = base.replace(/[^\/]+$/, '');
        var uri = MUB._minRoot + 'f=' + sources.join(',');
        if (base.charAt(base.length - 1) === '/') {
            // we have a base dir!
            var basedSources = sources
               ,i
               ,l = sources.length;
            for (i = 0; i < l; ++i) {
                basedSources[i] = sources[i].substr(base.length);
            }
            base = base.substr(0, base.length - 1);
            var bUri = MUB._minRoot + 'b=' + base + '&f=' + basedSources.join(',');
            //window.console && console.log([uri, bUri]);
            uri = uri.length < bUri.length
                ? uri
                : bUri;
        }
        return uri;
    }//*/
}
