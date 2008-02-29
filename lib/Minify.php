<?php

/**
 * Minify - Combines, minifies, and caches JavaScript and CSS files on demand.
 *
 * See README for usage instructions (for now).
 *
 * This library was inspired by jscsscomp by Maxim Martynyuk <flashkot@mail.ru>
 * and by the article "Supercharged JavaScript" by Patrick Hunlock
 * <wb@hunlock.com>.
 *
 * JSMin was originally written by Douglas Crockford <douglas@crockford.com>.
 *
 * Requires PHP 5.2.1+.
 *
 * @package Minify
 * @author Ryan Grove <ryan@wonko.com>
 * @author Stephen Clay <steve@mrclay.org>
 * @copyright 2007 Ryan Grove. All rights reserved.
 * @license http://opensource.org/licenses/bsd-license.php  New BSD License
 * @version 1.9.0
 * @link http://code.google.com/p/minify/
 */

require_once 'Minify/Source.php';

class Minify {

    /**
     * @var bool Should the un-encoded version be cached? 
     * 
     * True results in more cache files, but lower PHP load if different 
     * encodings are commonly requested.
     */
    public static $cacheUnencodedVersion = true;

    /**
     * Specify a writeable directory for cache files. If not called, Minify
     * will not use a disk cache and, for each 200 response, will need to
     * recombine files, minify and encode the output.
     *
     * @param string $path Full directory path for cache files (should not end
     * in directory separator character). If not provided, Minify will attempt to
     * write to the path returned by sys_get_temp_dir().
     *
     * @return null
     */
    public static function useServerCache($path = null) {
        self::$_cachePath = (null === $path)
            ? sys_get_temp_dir()
            : $path;
    }

    /**
     * Create a controller instance and handle the request
     * 
     * @param string type This should be the filename of the controller without
     * extension. e.g. 'Group'
     * 
     * @param array $spec options for the controller's constructor
     * 
     * @param array $options options passed on to Minify
     *
     * @return mixed false on failure or array of content and headers sent
     */
    public static function serve($type, $spec = array(), $options = array()) {
        $class = 'Minify_Controller_' . $type;
        if (! class_exists($class, false)) {
            require_once "Minify/Controller/{$type}.php";    
        }
        $ctrl = new $class($spec, $options);
        $ret = self::handleRequest($ctrl);
        if (false === $ret) {
            if (! isset($ctrl->options['quiet']) || ! $ctrl->options['quiet']) {
                header("HTTP/1.0 400 Bad Request");
                exit('400 Bad Request');      
            }
        }
        return $ret;
    }
    
    /**
     * Handle a request for a minified file. 
     * 
     * You must supply a controller object which has the same public API
     * as Minify_Controller.
     * 
     * @param Minify_Controller $controller
     * 
     * @return mixed false on failure or array of content and headers sent
     */
    public static function handleRequest($controller) {
        if (! $controller->requestIsValid) {
            return false;
        }
        
        self::$_controller = $controller;
        self::_setOptions();
        
        $cgOptions = array(
            'lastModifiedTime' => self::$_options['lastModifiedTime']
            ,'isPublic' => self::$_options['isPublic']
        );
        if (null !== self::$_options['setExpires']) {
            $cgOptions['setExpires'] = self::$_options['setExpires'];
        }
        
        // check client cache
        require_once 'HTTP/ConditionalGet.php';
        $cg = new HTTP_ConditionalGet($cgOptions);
        if ($cg->cacheIsValid) {
            // client's cache is valid
            if (self::$_options['quiet']) {
                return array(
                    'content' => ''
                    ,'headers' => $cg->getHeaders()                
                );
            } else {
                $cg->sendHeaders();    
            } 
        }
        // client will need output
        $headers = $cg->getHeaders();
        unset($cg);

        // determine encoding
        if (self::$_options['encodeOutput']) {
            if (self::$_options['encodeMethod'] !== null) {
                // controller specifically requested this
                $contentEncoding = self::$_options['encodeMethod'];
            } else {
                // sniff request header
                require_once 'HTTP/Encoder.php';
                // depending on what the client accepts, $contentEncoding may be 
                // 'x-gzip' while our internal encodeMethod is 'gzip'
                list(self::$_options['encodeMethod'], $contentEncoding) = HTTP_Encoder::getAcceptedEncoding();
            }
        } else {
            self::$_options['encodeMethod'] = ''; // identity (no encoding)
        }
        
        if (null !== self::$_cachePath) {
            self::_setupCache();
            // fetch content from cache file(s).
            $content = self::_fetchContent(self::$_options['encodeMethod']);
            self::$_cache = null;
        } else {
            // no cache, just combine, minify, encode
            $content = self::_combineMinify();
            $content = self::_encode($content);
        }

        // add headers to those from ConditionalGet
        //$headers['Content-Length'] = strlen($content);
        $headers['Content-Type'] = (null !== self::$_options['contentTypeCharset'])
            ? self::$_options['contentType'] . ';charset=' . self::$_options['contentTypeCharset']
            : self::$_options['contentType'];
        if (self::$_options['encodeMethod'] !== '') {
            $headers['Content-Encoding'] = $contentEncoding;
            $headers['Vary'] = 'Accept-Encoding';
        }

        if (! self::$_options['quiet']) {
            // output headers & content
            foreach ($headers as $name => $val) {
                header($name . ': ' . $val);
            }
            echo $content;    
        }
        return array(
            'content' => $content
            ,'headers' => $headers                
        );
    }
    
    /**
     * @var mixed null if disk cache is not to be used
     */
    private static $_cachePath = null;

    /**
     * @var Minify_Controller active controller for current request
     */
    private static $_controller = null;
    
    /**
     * @var array options for current request
     */
    private static $_options = null;
    
    /**
     * @var Cache_Lite_File cache obj for current request
     */
    private static $_cache = null;
    
    /**
     * Set class options based on controller's options and defaults
     * 
     * @return null
     */
    private static function _setOptions()
    {
        $given = self::$_controller->options;
        self::$_options = array_merge(array(
            // default options
            'isPublic' => true
            ,'encodeOutput' => true
            ,'encodeMethod' => null // determine later
            ,'encodeLevel' => 9
            ,'perType' => array() // per-type minifier options
            ,'contentTypeCharset' => null // leave out of Content-Type header
            ,'setExpires' => null // send Expires header
            ,'quiet' => false
        ), $given);
        $defaultMinifiers = array(
            'text/css' => array('Minify_CSS', 'minify')
            ,'application/x-javascript' => array('Minify_Javascript', 'minify')
            ,'text/html' => array('Minify_HTML', 'minify')
        );
        if (! isset($given['minifiers'])) {
            $given['minifiers'] = array();
        }
        self::$_options['minifiers'] = array_merge($defaultMinifiers, $given['minifiers']); 
    }
    
    /**
     * Fetch encoded content from cache (or generate and store it).
     * 
     * If self::$cacheUnencodedVersion is true and encoded content must be 
     * generated, this function will call itself recursively to fetch (or 
     * generate) the minified content. Otherwise, it will always recombine
     * and reminify files to generate different encodings.  
     * 
     * @param string $encodeMethod
     * 
     * @return string minified, encoded content
     */
    private static function _fetchContent($encodeMethod)
    {
        $cacheId = self::_getCacheId(self::$_controller->sources, self::$_options) 
            . $encodeMethod;    
        $content = self::$_cache->get($cacheId, 'Minify');
        if (false === $content) {
            // must generate
            if ($encodeMethod === '') {
                // generate identity cache to store
                $content = self::_combineMinify();
            } else {
                // fetch identity cache & encode it to store
                if (self::$cacheUnencodedVersion) {
                    // double layer cache
                    $content = self::_fetchContent('');
                } else {
                    // recombine
                    $content = self::_combineMinify();
                }
                $content = self::_encode($content);
            }
            self::$_cache->save($content, $cacheId, 'Minify');
        }
        return $content;
    }
    
    /**
     * Set self::$_cache to a new instance of Cache_Lite_File (patched 2007-10-03)
     * 
     * @return null
     */
    private static function _setupCache() {
        // until the patch is rolled into PEAR, we'll provide the
        // class in our package
        require_once dirname(__FILE__) . '/Cache/Lite/File.php';

        self::$_cache = new Cache_Lite_File(array(
            'cacheDir' => self::$_cachePath . '/'
            ,'fileNameProtection' => false

            // currently only available in patched Cache_Lite_File
            ,'masterTime' => self::$_options['lastModifiedTime']
        ));
    }
    
    /**
     * Combines sources and minifies the result.
     *
     * @return string
     */
    private static function _combineMinify() {
        $type = self::$_options['contentType']; // ease readability
        
        // when combining scripts, make sure all statements separated
        $implodeSeparator = ($type === 'application/x-javascript')
            ? ';'
            : '';
        
        // default options and minifier function for all sources
        $defaultOptions = isset(self::$_options['perType'][$type])
            ? self::$_options['perType'][$type]
            : array();
        // if minifier not set, default is no minification
        $defaultMinifier = isset(self::$_options['minifiers'][$type])
            ? self::$_options['minifiers'][$type]
            : false;
       
        if (Minify_Source::haveNoMinifyPrefs(self::$_controller->sources)) {
            // all source have same options/minifier, better performance
            foreach (self::$_controller->sources as $source) {
                $pieces[] = $source->getContent();
            }
            $content = implode($implodeSeparator, $pieces);
            if ($defaultMinifier) {
                self::$_controller->loadMinifier($defaultMinifier);
                $content = call_user_func($defaultMinifier, $content, $defaultOptions);    
            }
        } else {
            // minify each source with its own options and minifier
            foreach (self::$_controller->sources as $source) {
                // allow the source to override our minifier and options
                $minifier = (null !== $source->minifier)
                    ? $source->minifier
                    : $defaultMinifier;
                $options = (null !== $source->minifyOptions)
                    ? array_merge($defaultOptions, $source->minifyOptions)
                    : $defaultOptions;
                if ($defaultMinifier) {
                    self::$_controller->loadMinifier($minifier);
                    // get source content and minify it
                    $pieces[] = call_user_func($minifier, $source->getContent(), $options);     
                } else {
                    $pieces[] = $source->getContent();     
                }
            }
            $content = implode($implodeSeparator, $pieces);
        }
        return $content;
    }
    
    /**
     * Applies HTTP encoding
     *
     * @param string $content
     * 
     * @return string
     */
    private static function _encode($content)
    {
        if (self::$_options['encodeMethod'] === '' 
            || ! self::$_options['encodeOutput']) {
            // "identity" encoding
            return $content;
        }
        require_once 'HTTP/Encoder.php';
        $encoder = new HTTP_Encoder(array(
            'content' => $content
            ,'method' => self::$_options['encodeMethod']
        ));
        $encoder->encode(self::$_options['encodeLevel']);
        return $encoder->getContent();
    }

    /**
     * Make a unique cache id for for this request.
     * 
     * Any settings that could affect output are taken into consideration  
     *
     * @return string
     */
    private static function _getCacheId() {
        return md5(serialize(array(
            Minify_Source::getDigest(self::$_controller->sources)
            ,self::$_options['minifiers'] 
            ,self::$_options['perType']
        )));
    }
}
