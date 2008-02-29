<?php

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for serving a single HTML page
 * 
 * @link http://code.google.com/p/minify/source/browse/trunk/web/examples/1/index.php#59
 * 
 */
class Minify_Controller_Page extends Minify_Controller_Base {
    
    /**
     * @param array $spec array of options. You *must* set 'content' and 'id',
     * but setting 'lastModifiedTime' is recommeded in order to allow server
     * and client-side caching.
     * 
     * If you set <code>'minifyAll' => 1</code>, all CSS and Javascript blocks
     * will be individually minified. 
     * 
     * @param array $options optional options to pass to Minify
     * 
     * @return null 
     */
    public function __construct($spec, $options = array()) {
        $sourceSpec = array(
            'content' => $spec['content']
            ,'id' => $spec['id']
            ,'minifier' => array('Minify_HTML', 'minify')
        );
        if (isset($spec['minifyAll'])) {
            $sourceSpec['minifyOptions'] = array(
                'cssMinifier' => array('Minify_CSS', 'minify')
                ,'jsMinifier' => array('Minify_Javascript', 'minify')
            );
            $this->_loadCssJsMinifiers = true;
        }
        $sources[] = new Minify_Source($sourceSpec);
        if (isset($spec['lastModifiedTime'])) {
            $options['lastModifiedTime'] = $spec['lastModifiedTime'];
        }
        $options['contentType'] = 'text/html';
        $this->requestIsValid = true;
        parent::__construct($sources, $options);
    }
    
    private $_loadCssJsMinifiers = false;
    
    /**
     * @see Minify_Controller_Base::loadMinifier()
     */
    public function loadMinifier($minifierCallback)
    {
        if ($this->_loadCssJsMinifiers) {
            // Minify will not call for these so we must manually load
            // them when Minify/HTML.php is called for.
            require 'Minify/CSS.php';
            require 'Minify/Javascript.php';
        }
        parent::loadMinifier($minifierCallback); // load Minify/HTML.php
    }
}

