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
    protected $content = null;

    /**
     * @var callable
     */
    protected $getContentFunc = null;

    /**
     * @var string
     */
    protected $id = null;

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
            $ext = pathinfo($spec['filepath'], PATHINFO_EXTENSION);
            switch ($ext) {
                case 'js'   : $this->contentType = Minify::TYPE_JS;
                              break;
                case 'less' : // fallthrough
                case 'css'  : $this->contentType = Minify::TYPE_CSS;
                              break;
                case 'htm'  : // fallthrough
                case 'html' : $this->contentType = Minify::TYPE_HTML;
                              break;
            }
            $this->filepath = $spec['filepath'];
            $this->id = $spec['filepath'];

            // TODO ideally not touch disk in constructor
            $this->lastModified = filemtime($spec['filepath']);

            if (!empty($spec['uploaderHoursBehind'])) {
                // offset for Windows uploaders with out of sync clocks
                $this->lastModified += round($spec['uploaderHoursBehind'] * 3600);
            }
        } elseif (isset($spec['id'])) {
            $this->id = 'id::' . $spec['id'];
            if (isset($spec['content'])) {
                $this->content = $spec['content'];
            } else {
                $this->getContentFunc = $spec['getContentFunc'];
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
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinifier()
    {
        return $this->minifier;
    }

    /**
     * {@inheritdoc}
     */
    public function setMinifier($minifier)
    {
        $this->minifier = $minifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinifierOptions()
    {
        return $this->minifyOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setMinifierOptions(array $options)
    {
        $this->minifyOptions = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (null === $this->filepath) {
            if (null === $this->content) {
                $content = call_user_func($this->getContentFunc, $this->id);
            } else {
                $content = $this->content;
            }
        } else {
            $content = file_get_contents($this->filepath);
        }
        // remove UTF-8 BOM if present
        return ("\xEF\xBB\xBF" === substr($content, 0, 3)) ? substr($content, 3) : $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilePath()
    {
        return $this->filepath;
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
}
