<?php
/**
 * Class Minify_Cache_Interface
 * @package Minify
 */

/**
 * @package Minify
 * @author Stephen Clay
 **/
interface Minify_Cache_Interface {

    /**
     * Write data to cache.
     *
     * @param string $id cache id
     *
     * @param string $data
     *
     * @return bool success
     */
    public function store($id, $data);

    /**
     * Get the size of a cache entry
     *
     * @param string $id cache id
     *
     * @return int size in bytes
     */
    public function getSize($id);

    /**
     * Does a valid cache entry exist?
     *
     * @param string $id cache id
     *
     * @param int $srcMtime mtime of the original source file(s)
     *
     * @return bool exists
     */
    public function isValid($id, $srcMtime);

    /**
     * Send the cached content to output
     *
     * @param string $id cache id
     */
    public function display($id);

    /**
     * Fetch the cached content
     *
     * @param string $id cache id
     *
     * @return string
     */
    public function fetch($id);
}
