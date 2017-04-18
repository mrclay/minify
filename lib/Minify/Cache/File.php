<?php
/**
 * Class Minify_Cache_File
 * @package Minify
 */

use Psr\Log\LoggerInterface;

class Minify_Cache_File implements Minify_CacheInterface
{

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $locking;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string          $path
     * @param bool            $fileLocking
     * @param LoggerInterface $logger
     */
    public function __construct($path = '', $fileLocking = false, LoggerInterface $logger = null)
    {
        if (! $path) {
            $path = sys_get_temp_dir();
        }
        $this->locking = $fileLocking;
        $this->path = $path;

        if (!$logger) {
            $logger = new \Monolog\Logger('minify');
        }
        $this->logger = $logger;
    }

    /**
     * Write data to cache.
     *
     * @param string $id cache id (e.g. a filename)
     *
     * @param string $data
     *
     * @return bool success
     */
    public function store($id, $data)
    {
        $flag = $this->locking ? LOCK_EX : null;
        $file = $this->path . '/' . $id;

        if (! @file_put_contents($file, $data, $flag)) {
            $this->logger->warning("Minify_Cache_File: Write failed to '$file'");
        }

        // write control
        if ($data !== $this->fetch($id)) {
            @unlink($file);
            $this->logger->warning("Minify_Cache_File: Post-write read failed for '$file'");

            return false;
        }

        return true;
    }

    /**
     * Get the size of a cache entry
     *
     * @param string $id cache id (e.g. a filename)
     *
     * @return int size in bytes
     */
    public function getSize($id)
    {
        return filesize($this->path . '/' . $id);
    }

    /**
     * Does a valid cache entry exist?
     *
     * @param string $id cache id (e.g. a filename)
     *
     * @param int $srcMtime mtime of the original source file(s)
     *
     * @return bool exists
     */
    public function isValid($id, $srcMtime)
    {
        $file = $this->path . '/' . $id;

        return (is_file($file) && (filemtime($file) >= $srcMtime));
    }

    /**
     * Send the cached content to output
     *
     * @param string $id cache id (e.g. a filename)
     */
    public function display($id)
    {
        if (!$this->locking) {
            readfile($this->path . '/' . $id);

            return;
        }

        $fp = fopen($this->path . '/' . $id, 'rb');
        flock($fp, LOCK_SH);
        fpassthru($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * Fetch the cached content
     *
     * @param string $id cache id (e.g. a filename)
     *
     * @return string
     */
    public function fetch($id)
    {
        if (!$this->locking) {
            return file_get_contents($this->path . '/' . $id);
        }

        $fp = fopen($this->path . '/' . $id, 'rb');
        if (!$fp) {
            return false;
        }

        flock($fp, LOCK_SH);
        $ret = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $ret;
    }

    /**
     * Fetch the cache path used
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get a usable temp directory
     *
     * @return string
     * @deprecated
     */
    public static function tmp()
    {
        trigger_error(__METHOD__ . ' is deprecated in Minfy 3.0', E_USER_DEPRECATED);

        return sys_get_temp_dir();
    }

    /**
     * Send message to the Minify logger
     * @param string $msg
     * @return null
     * @deprecated Use $this->logger
     */
    protected function _log($msg)
    {
        trigger_error(__METHOD__ . ' is deprecated in Minify 3.0.', E_USER_DEPRECATED);
        $this->logger->warning($msg);
    }
}
