<?php

/**
 * Base class for Minify controller
 * 
 * The controller class validates a request and uses it to create sources
 * for minification and set options like contentType. It's also responsible
 * for loading minifier code upon request.
 */
class Minify_Controller_Base {
    
    /**
     * @var array instances of Minify_Source, which provide content and
     * any individual minification needs.
     * 
     * @see Minify_Source
     */
    public $sources = array();
    
    /**
     * @var array options to be read by read by Minify
     * 
     * Any unspecified options will use the default values.
     * 
     * 'minifiers': this is an array with content-types as keys and callbacks as
     * values. Specify a custom minifier by setting this option. E.g.:
     * $this->options['minifiers']['application/x-javascript'] = 'myJsPacker';
     * Note that, when providing your own minifier, the controller must be able
     * to load its code on demand. @see loadMinifier()
     * 
     * 'perType' : this is an array of options to send to a particular content
     * type minifier by using the content-type as key. E.g. To send the CSS 
     * minifier an option: $options['perType']['text/css']['foo'] = 'bar';
     * When the CSS minifier is called, the 2nd argument will be
     * array('foo' => 'bar').
     * 
     * 'isPublic' : send "public" instead of "private" in Cache-Control headers, 
     * allowing shared caches to cache the output. (default true)
     * 
     * 'encodeOutput' : to disable content encoding, set this to false
     * 
     * 'encodeMethod' : generally you should let this be determined by 
     * HTTP_Encoder (the default null), but you can force a particular encoding
     * to be returned, by setting this to 'gzip', 'deflate', 'compress', or '' 
     * (no encoding)
     * 
     * 'encodeLevel' : level of encoding compression (0 to 9, default 9)
     * 
     * 'contentTypeCharset' : if given, this will be appended to the Content-Type
     * header sent, useful mainly for HTML docs.  
     * 
     * 'cacheUntil' : set this to a timestamp or GMT date to have Minify send
     * an HTTP Expires header instead of checking for conditional GET. 
     * E.g. (time() + 86400 * 365) for 1yr (default null)
     * This has nothing to do with server-side caching.
     *
     */
    public $options = array();

    /**
     * @var bool was the user request valid
     * 
     * This must be explicity be set to true to process the request. This should
     * be done by the child class constructor.
     */
    public $requestIsValid = false;
    
    /**
     * Parent constructor for a controller class
     * 
     * Generally you'll call this at the end of your child class constructor:
     * <code>
     * parent::__construct($sources, $options);
     * </code>
     * 
     * This function sets the sources and determines the 'contentType' and 
     * 'lastModifiedTime', if not given.
     * 
     * If no sources are provided, $this->requestIsValid will be set to false.
     * 
     * @param array $sources array of instances of Minify_Source
     * 
     * @param array $options
     * 
     * @return null
     */
    public function __construct($sources, $options = array()) {
        if (empty($sources)) {
            $this->requestIsValid = false;
        }
        $this->sources = $sources;
        if (! isset($options['contentType'])) {
            $options['contentType'] = Minify_Source::getContentType($this->sources);
        }
        // last modified is needed for caching, even if cacheUntil is set
        if (! isset($options['lastModifiedTime'])) {
            $max = 0;
            foreach ($sources as $source) {
                $max = max($source->lastModified, $max);
            }
            $options['lastModifiedTime'] = $max;
        }    
        $this->options = $options;
    }
    
    /**
     * Load any code necessary to execute the given minifier callback.
     * 
     * The controller is responsible for loading minification code on demand
     * via this method. This built-in function will only load classes for
     * static method callbacks where the class isn't already defined. It uses
     * the PEAR convention, so, given array('Jimmy_Minifier', 'minCss'), this 
     * function will include 'Jimmy/Minifier.php'
     * 
     * If you need code loaded on demand and this doesn't suit you, you'll need
     * to override this function by extending the class. 
     * 
     * @return null
     */
    public function loadMinifier($minifierCallback)
    {
        if (is_array($minifierCallback)
            && is_string($minifierCallback[0])
            && !class_exists($minifierCallback[0], false)) {
            
            require str_replace('_', '/', $minifierCallback[0]) . '.php';
        }
    }
}
