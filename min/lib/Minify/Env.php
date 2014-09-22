<?php

class Minify_Env {

    /**
     * How many hours behind are the file modification times of uploaded files?
     *
     * If you upload files from Windows to a non-Windows server, Windows may report
     * incorrect mtimes for the files. Immediately after modifying and uploading a
     * file, use the touch command to update the mtime on the server. If the mtime
     * jumps ahead by a number of hours, set this variable to that number. If the mtime
     * moves back, this should not be needed.
     *
     * @var int $uploaderHoursBehind
     */
    protected $uploaderHoursBehind = 0;

    /**
     * @return null
     */
    public function getDocRoot()
    {
        return $this->server['DOCUMENT_ROOT'];
    }

    /**
     * @return null
     */
    public function getRequestUri()
    {
        return $this->server['REQUEST_URI'];
    }

    public function __construct($options = array())
    {
        $options = array_merge(array(
            'server' => $_SERVER,
            'get' => $_GET,
            'cookie' => $_COOKIE,
        ), $options);

        $this->server = $options['server'];
        if (empty($this->server['DOCUMENT_ROOT'])) {
            $this->server['DOCUMENT_ROOT'] = $this->computeDocRoot($options['server']);
        }
        $this->get = $options['get'];
        $this->cookie = $options['cookie'];
    }

    public function server($key)
    {
        return isset($this->server[$key])
            ? $this->server[$key]
            : null;
    }

    public function cookie($key)
    {
        return isset($this->cookie[$key])
            ? $this->cookie[$key]
            : null;
    }

    public function get($key)
    {
        return isset($this->get[$key])
            ? $this->get[$key]
            : null;
    }

    protected $server = null;
    protected $get = null;
    protected $cookie = null;

    /**
     * Compute $_SERVER['DOCUMENT_ROOT'] for IIS using SCRIPT_FILENAME and SCRIPT_NAME.
     *
     * @param array $server
     * @return string
     */
    protected function computeDocRoot(array $server)
    {
        if (isset($server['SERVER_SOFTWARE'])
                && 0 === strpos($server['SERVER_SOFTWARE'], 'Microsoft-IIS/')) {
            $docRoot = substr(
                $server['SCRIPT_FILENAME']
                ,0
                ,strlen($server['SCRIPT_FILENAME']) - strlen($server['SCRIPT_NAME']));
            $docRoot = rtrim($docRoot, '\\');
        } else {
            throw new InvalidArgumentException('DOCUMENT_ROOT is not provided and could not be computed');
        }
        return $docRoot;
    }
}
