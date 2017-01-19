<?php
/**
 * Interface Minify_SourceInterface
 * @package Minify
 */

/**
 * A content source to be minified by Minify.
 *
 * This allows per-source minification options and the mixing of files with
 * content from other sources.
 *
 * @package Minify
 */
interface Minify_SourceInterface
{

    /**
     * Get the minifier
     *
     * @return callable|null
     */
    public function getMinifier();

    /**
     * Set the minifier
     *
     * @param callable $minifier
     * @return void
     */
    public function setMinifier($minifier = null);

    /**
     * Get options for the minifier
     *
     * @return array
     */
    public function getMinifierOptions();

    /**
     * Set options for the minifier
     *
     * @param array $options
     * @return void
     */
    public function setMinifierOptions(array $options);

    /**
     * Get the content type
     *
     * @return string|null
     */
    public function getContentType();

    /**
     * Get content
     *
     * @return string
     */
    public function getContent();

    /**
     * Get last modified timestamp
     *
     * @return int
     */
    public function getLastModified();

    /**
     * Get id
     *
     * @return string
     */
    public function getId();

    /**
     * Get the path of the file that this source is based on (may be null)
     *
     * @return string|null
     */
    public function getFilePath();
}
