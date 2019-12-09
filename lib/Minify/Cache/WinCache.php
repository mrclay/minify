<?php

/**
 * WinCache-based cache class for Minify
 *
 * <code>
 * Minify::setCache(new Minify_Cache_WinCache());
 * </code>
 *
 **/
class Minify_Cache_WinCache implements Minify_CacheInterface
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
     * Create a Minify_Cache_Wincache object, to be passed to
     * Minify::setCache().
     *
     * @param int $expire seconds until expiration (default = 0
     *                    meaning the item will not get an expiration date)
     *
     * @throws Exception
     */
    public function __construct($expire = 0)
    {
        if (!\function_exists('wincache_ucache_info')) {
            throw new Exception('WinCache for PHP is not installed to be able to use Minify_Cache_WinCache!');
        }
        $this->_exp = $expire;
    }

    /**
     * Send the cached content to output
     *
     * @param string $id cache id
     *
     * @return void
     */
    public function display($id)
    {
        echo $this->_fetch($id) ? $this->_data : '';
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
        return wincache_ucache_set($id, "{$_SERVER['REQUEST_TIME']}|{$data}", $this->_exp);
    }

    /**
     * Fetch data and timestamp from WinCache, store in instance
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

        $suc = false;
        /** @noinspection PhpComposerExtensionStubsInspection */
        $ret = wincache_ucache_get($id, $suc);
        if (!$suc) {
            $this->_id = null;

            return false;
        }

        list($this->_lm, $this->_data) = \explode('|', $ret, 2);
        $this->_id = $id;

        return true;
    }
}
