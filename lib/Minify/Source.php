<?php

/** 
 * A content source to be minified by Minify. 
 * 
 * This allows per-source minification options and the mixing of files with
 * content from other sources.
 */
class Minify_Source {

    /**
     * @var int time of last modification
     */
    public $lastModified = null;
    
    /**
     * @var callback minifier function specifically for this source.
     */
    public $minifier = null;
    
    /**
     * @var array minification options specific to this source.
     */
    public $minifyOptions = null;

    /**
     * Create a Minify_Source
     * 
     * In the $spec array(), you can either provide a 'filepath' to an existing
     * file (existence will not be checked!) or give 'id' (unique string for 
     * the content), 'content' (the string content) and 'lastModified' 
     * (unixtime of last update).
     *
     * @param array $spec options
     */
    public function __construct($spec)
    {
        if (isset($spec['filepath'])) {
            $this->_filepath = $spec['filepath'];
            $this->_id = $spec['filepath'];
            $this->lastModified = filemtime($spec['filepath']);
        } elseif (isset($spec['id'])) {
            $this->_id = 'id::' . $spec['id'];
            $this->_content = $spec['content'];
            $this->lastModified = isset($spec['lastModified'])
                ? $spec['lastModified']
                : time();
        }
        if (isset($spec['minifier'])) {
            $this->minifier = $spec['minifier'];
        }
        if (isset($spec['minifyOptions'])) {
            $this->minifyOptions = $spec['minifyOptions'];
        }
    }
    
    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return (null !== $this->_content)
            ? $this->_content
            : file_get_contents($this->_filepath);
    }
    
    /**
     * Verifies a single minification call can handle all sources
     *
     * @param array $sources Minify_Source instances
     * 
     * @return bool true iff there no sources with specific minifier preferences.
     */
    public static function haveNoMinifyPrefs($sources)
    {
        foreach ($sources as $source) {
            if (null !== $source->minifier
                || null !== $source->minifyOptions) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get unique string for a set of sources
     *
     * @param array $sources Minify_Source instances
     * 
     * @return string
     */
    public static function getDigest($sources)
    {
        foreach ($sources as $source) {
            $info[] = array(
                $source->_id, $source->minifier, $source->minifyOptions
            );
        }
        return md5(serialize($info));
    }
    
    /**
     * Guess content type from the first filename extension available
     * 
     * This is called if the user doesn't pass in a 'contentType' options  
     * 
     * @param array $sources Minify_Source instances
     * 
     * @return string content type. e.g. 'text/css'
     */
    public static function getContentType($sources)
    {
        $exts = array(
            'css' => Minify::TYPE_CSS
            ,'js' => Minify::TYPE_JS
            ,'html' => Minify::TYPE_HTML
        );
        foreach ($sources as $source) {
            if (null !== $source->_filepath) {
                $segments = explode('.', $source->_filepath);
                $ext = array_pop($segments);
                if (isset($exts[$ext])) {
                    return $exts[$ext];
                }
            }
        }
        return 'text/plain';
    }
    
    private $_content = null;
    private $_filepath = null;
    private $_id = null;
}

