<?php
/**
 * Interface Minify_SourceInterface
 */

/**
 * A content source to be minified by Minify.
 *
 * This allows per-source minification options and the mixing of files with
 * content from other sources.
 */
interface Minify_SourceInterface
{
    /**
     * Get content
     *
     * @return string
     */
    public function getContent();

    /**
     * Get the content type
     *
     * @return string|null
     */
    public function getContentType();

    /**
     * Get the path of the file that this source is based on (may be null)
     *
     * @return string|null
     */
    public function getFilePath();

    /**
     * Get id
     *
     * @return string
     */
    public function getId();

    /**
     * Get last modified timestamp
     *
     * @return int
     */
    public function getLastModified();

    /**
     * Get the minifier
     *
     * @return callable|null
     */
    public function getMinifier();

    /**
     * Get options for the minifier
     *
     * @return array
     */
    public function getMinifierOptions();

    /**
     * Set the minifier
     *
     * @param callable $minifier
     *
     * @return void
     */
    public function setMinifier($minifier = null);

    /**
     * Set options for the minifier
     *
     * @param array $options
     *
     * @return void
     */
    public function setMinifierOptions(array $options);
}
