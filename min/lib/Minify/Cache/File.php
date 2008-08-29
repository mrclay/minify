<?php
/**
 * Class Minify_Cache_File  
 * @package Minify
 */

class Minify_Cache_File {
    
    public function __construct($path = '')
    {
        if (! $path) {
            require_once 'Solar/Dir.php';
            $path = rtrim(Solar_Dir::tmp(), DIRECTORY_SEPARATOR);
        }
        $this->_path = $path;
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
        return self::_verifiedWrite($this->_path . '/' . $id, $data);
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
        return filesize($this->_path . '/' . $id);
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
        $file = $this->_path . '/' . $id;
        return (file_exists($file) && (filemtime($file) >= $srcMtime));
    }
    
    /**
     * Send the cached content to output
     *
     * @param string $id cache id (e.g. a filename)
     */
    public function display($id)
    {
        readfile($this->_path . '/' . $id);
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
        return file_get_contents($this->_path . '/' . $id);
    }
    
    private $_path = null;
    
	/**
     * Write data to file and verify its contents
     * 
     * @param string $file path
     * 
     * @param string $data
     * 
     * @return bool success
     */
    private static function _verifiedWrite($file, $data)
    {
        if (! @file_put_contents($file, $data)) {
            return false;
        }
        if (md5($data) !== md5_file($file)) {
            @unlink($file);
            return false;
        }
        return true;
    }
}
