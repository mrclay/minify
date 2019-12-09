<?php

/**
 * APC-based cache class for Minify
 *
 * <code>
 * Minify::setCache(new Minify_Cache_APC());
 * </code>
 *
 **/
class Minify_Cache_APC implements Minify_CacheInterface
{
    /**
     * @var int
     */
    private $_exp;

    /**
     * cache of most recently fetched id
     *
     * @var int|null
     */
    private $_lm;

    /**
     * @var mixed
     */
    private $_data;

    /**
     * @var string|null
     */
    private $_id;

    /**
     * Create a Minify_Cache_APC object, to be passed to
     * Minify::setCache().
     *
     * @param int $expire seconds until expiration (default = 0
     *                    meaning the item will not get an expiration date)
     */
    public function __construct($expire = 0)
    {
        $this->_exp = $expire;
    }

    /**
     * Send the cached content to output
     *
     * @param string|null $id cache id
     *
     * @return void
     */
    public function display($id)
    {
        echo $id !== null && $this->_fetch($id) ? $this->_data : '';
    }

    /**
     * Fetch the cached content
     *
     * @param string $id cache id
     *
     * @return string|null
     */
    public function fetch($id)
    {
        return $this->_fetch($id) ? $this->_data : '';
    }

    /**
     * Get the size of a cache entry
     *
     * @param string $id cache id
     *
     * @return false|int size in bytes or false on error
     */
    public function getSize($id)
    {
        if (!$this->_fetch($id)) {
            return false;
        }

        if (\function_exists('mb_strlen') && ((int) \ini_get('mbstring.func_overload') & 2)) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            return \mb_strlen($this->_data, '8bit');
        }

        return \strlen($this->_data);
    }

    /**
     * Does a valid cache entry exist?
     *
     * @param string $id       cache id
     * @param int    $srcMtime mtime of the original source file(s)
     *
     * @return bool exists
     */
    public function isValid($id, $srcMtime)
    {
        return $this->_fetch($id) && ($this->_lm >= $srcMtime);
    }

    /**
     * Write data to cache.
     *
     * @param string $id cache id
     * @param string $data
     *
     * @return bool success
     */
    public function store($id, $data)
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        return \apc_store($id, "{$_SERVER['REQUEST_TIME']}|{$data}", $this->_exp);
    }

    /**
     * Fetch data and timestamp from apc, store in instance
     *
     * @param string $id
     *
     * @return bool success
     */
    private function _fetch($id)
    {
        if ($this->_id === $id) {
            return true;
        }

        /** @noinspection PhpComposerExtensionStubsInspection */
        $ret = \apc_fetch($id);
        if ($ret === false) {
            $this->_id = null;

            return false;
        }

        list($this->_lm, $this->_data) = \explode('|', $ret, 2);
        $this->_id = $id;

        return true;
    }
}
