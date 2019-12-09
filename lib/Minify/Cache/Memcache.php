<?php
/**
 * Class Minify_Cache_Memcache
 */

/**
 * Memcache-based cache class for Minify
 *
 * <code>
 * // fall back to disk caching if memcache can't connect
 * $memcache = new Memcache;
 * if ($memcache->connect('localhost', 11211)) {
 *     Minify::setCache(new Minify_Cache_Memcache($memcache));
 * } else {
 *     Minify::setCache();
 * }
 * </code>
 **/
class Minify_Cache_Memcache implements Minify_CacheInterface {
    private $_mc;

    private $_exp;

    private $_lm;

    private $_data;

    private $_id;

    /**
     * Create a Minify_Cache_Memcache object, to be passed to
     * Minify::setCache().
     *
     * @param Memcache $memcache already-connected instance
     * @param int      $expire   seconds until expiration (default = 0
     *                           meaning the item will not get an expiration date)
     */
    public function __construct($memcache, $expire = 0) {
        $this->_mc = $memcache;
        $this->_exp = $expire;
    }

    /**
     * Send the cached content to output
     *
     * @param string $id cache id
     */
    public function display($id) {
        echo $this->_fetch($id) ? $this->_data : '';
    }

    /**
     * Fetch the cached content
     *
     * @param string $id cache id
     *
     * @return string
     */
    public function fetch($id) {
        return $this->_fetch($id) ? $this->_data : '';
    }

    // cache of most recently fetched id

    /**
     * Get the size of a cache entry
     *
     * @param string $id cache id
     *
     * @return int size in bytes
     */
    public function getSize($id) {
        if (!$this->_fetch($id)) {
            return false;
        }

        if (\function_exists('mb_strlen') && ((int)\ini_get('mbstring.func_overload') & 2)) {
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
    public function isValid($id, $srcMtime) {
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
    public function store($id, $data) {
        return $this->_mc->set($id, "{$_SERVER['REQUEST_TIME']}|{$data}", 0, $this->_exp);
    }

    /**
     * Fetch data and timestamp from memcache, store in instance
     *
     * @param string $id
     *
     * @return bool success
     */
    private function _fetch($id) {
        if ($this->_id === $id) {
            return true;
        }

        $ret = $this->_mc->get($id);
        if ($ret === false) {
            $this->_id = null;

            return false;
        }

        list($this->_lm, $this->_data) = \explode('|', $ret, 2);
        $this->_id = $id;

        return true;
    }
}
