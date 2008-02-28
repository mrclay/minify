<?php

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for minifying a set of files
 * 
 * E.g. the following would serve minified Javascript for a site
 * <code>
 * $dr = $_SERVER['DOCUMENT_ROOT'];
 * Minify::minify('Files', array(
 *    $dr . '/js/jquery.js'
 *     ,$dr . '/js/plugins.js'
 *     ,$dr . '/js/site.js'
 * ));
 * </code>
 * 
 */
class Minify_Controller_Page extends Minify_Controller_Base {
    
    /**
     * 
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
    
    public function loadMinifier($minifierCallback)
    {
        if ($this->_loadCssJsMinifiers) {
            require 'Minify/CSS.php';
            require 'Minify/Javascript.php';
        }
        parent::loadMinifier($minifierCallback);
    }
}

