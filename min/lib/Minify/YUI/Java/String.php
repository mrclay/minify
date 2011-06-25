<?php
/**
 * Class Minify_YUI_Java_String
 * @package Minify
 */

if (function_exists('mb_strlen')) {
    mb_internal_encoding('8bit'); // no multibyte strong functions, please
}

/**
 * Allow PHP syntax of YUI's CssCompressor port to more closely match Java version
 */
class Minify_YUI_Java_String {
    public $content;

    public function __construct($content = '')
    {
        $this->content = $content;
    }

    public function replace($target, $replacement)
    {
        return new self(str_replace($target, $replacement, $this->content));
    }

    public function replaceAll($regex, $replacement)
    {
        $pattern = '/' . str_replace('/', '\/', $regex) . '/';
        return new self(preg_replace($pattern, $replacement, $this->content));
    }

    /**
     * Return position (in bytes) of string found or -1 (not FALSE!)
     * @param string $needle
     * @param int $offset
     * @return int
     */
    public function indexOf($needle, $offset = 0) {
        $pos = strpos($this->content, $needle, $offset);
        return ($pos === false)
            ? -1
            : $pos;
    }

    /**
     * Get number of bytes (not characters) in string
     * @return int
     */
    public function length()
    {
        return strlen($this->content);
    }

    public function toString()
    {
        return $this->content;
    }

    public function append($str)
    {
        $this->content .= $str;
    }
}