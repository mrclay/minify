<?php

/**
 * Interface for Minify cache adapters
 */
interface Minify_CacheInterface
{
    /**
     * Send the cached content to output
     *
     * @param string $id cache id (e.g. a filename)
     *
     * @return void
     */
    public function display($id);

    /**
     * Fetch the cached content
     *
     * @param string $id cache id (e.g. a filename)
     *
     * @return string
     */
    public function fetch($id);

    /**
     * Get the size of a cache entry
     *
     * @param string $id cache id (e.g. a filename)
     *
     * @return false|int size in bytes or false on error
     */
    public function getSize($id);

    /**
     * Does a valid cache entry exist?
     *
     * @param string $id       cache id (e.g. a filename)
     * @param int    $srcMtime mtime of the original source file(s)
     *
     * @return bool exists
     */
    public function isValid($id, $srcMtime);

    /**
     * Write data to cache.
     *
     * @param string $id cache id (e.g. a filename)
     * @param string $data
     *
     * @return bool success
     */
    public function store($id, $data);
}
