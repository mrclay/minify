<?php
/**
 * Class Minify_Source  
 * @package Minify
 */

/** 
 * A content source to be minified by Minify. 
 * 
 * This allows per-source minification options and the mixing of files with
 * content from other sources.
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Source implements Minify_SourceInterface {

    /**
     * {@inheritdoc}
     */
    public function getLastModified() {
        return $this->lastModified;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinifier() {
        return $this->minifier;
    }

    /**
     * {@inheritdoc}
     */
    public function setMinifier($minifier) {
        $this->minifier = $minifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinifierOptions() {
        return $this->minifyOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setMinifierOptions(array $options) {
        $this->minifyOptions = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return $this->contentType;
    }

    /**
     * Create a Minify_Source
     * 
     * In the $spec array(), you can either provide a 'filepath' to an existing
     * file (existence will not be checked!) or give 'id' (unique string for 
     * the content), 'content' (the string content) and 'lastModified' 
     * (unixtime of last update).
     * 
     * As a shortcut, the controller will replace "//" at the beginning
     * of a filepath with $_SERVER['DOCUMENT_ROOT'] . '/'.
     *
     * @param array $spec options
     */
    public function __construct($spec)
    {
        if (isset($spec['filepath'])) {
            if (0 === strpos($spec['filepath'], '//')) {
                $spec['filepath'] = $_SERVER['DOCUMENT_ROOT'] . substr($spec['filepath'], 1);
            }
            $segments = explode('.', $spec['filepath']);
            $ext = strtolower(array_pop($segments));
            switch ($ext) {
            case 'js'   : $this->contentType = 'application/x-javascript';
                          break;
            case 'css'  : $this->contentType = 'text/css';
                          break;
            case 'htm'  : // fallthrough
            case 'html' : $this->contentType = 'text/html';
                          break;
            }
            $this->filepath = $spec['filepath'];
            $this->_id = $spec['filepath'];
            $this->lastModified = filemtime($spec['filepath'])
                // offset for Windows uploaders with out of sync clocks
                + round(Minify::$uploaderHoursBehind * 3600);
        } elseif (isset($spec['id'])) {
            $this->_id = 'id::' . $spec['id'];
            if (isset($spec['content'])) {
                $this->_content = $spec['content'];
            } else {
                $this->_getContentFunc = $spec['getContentFunc'];
            }
            $this->lastModified = isset($spec['lastModified'])
                ? $spec['lastModified']
                : time();
        }
        if (isset($spec['contentType'])) {
            $this->contentType = $spec['contentType'];
        }
        if (isset($spec['minifier'])) {
            $this->minifier = $spec['minifier'];
        }
        if (isset($spec['minifyOptions'])) {
            $this->minifyOptions = $spec['minifyOptions'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $content = (null !== $this->filepath)
            ? file_get_contents($this->filepath)
            : ((null !== $this->_content)
                ? $this->_content
                : call_user_func($this->_getContentFunc, $this->_id)
            );
        // remove UTF-8 BOM if present
        return ("\xEF\xBB\xBF" === substr($content, 0, 3))
            ? substr($content, 3)
            : $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * {@inheritdoc}
     */
    public function setupUriRewrites() {
        if ($this->filepath
            && !isset($this->minifyOptions['currentDir'])
            && !isset($this->minifyOptions['prependRelativePath'])
        ) {
            $this->minifyOptions['currentDir'] = dirname($this->filepath);
        }
    }

    /**
     * @var int time of last modification
     */
    protected $lastModified = null;

    /**
     * @var callback minifier function specifically for this source.
     */
    protected $minifier = null;

    /**
     * @var array minification options specific to this source.
     */
    protected $minifyOptions = array();

    /**
     * @var string full path of file
     */
    protected $filepath = null;

    /**
     * @var string HTTP Content Type (Minify requires one of the constants Minify::TYPE_*)
     */
    protected $contentType = null;

    /**
     * @var string
     */
    protected $_content = null;

    /**
     * @var callable
     */
    protected $_getContentFunc = null;

    /**
     * @var string
     */
    protected $_id = null;
}

