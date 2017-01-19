<?php

class Minify_Env
{

    /**
     * @return string
     */
    public function getDocRoot()
    {
        return $this->server['DOCUMENT_ROOT'];
    }

    /**
     * @return string
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
            'post' => $_POST,
            'cookie' => $_COOKIE,
        ), $options);

        $this->server = $options['server'];
        if (empty($this->server['DOCUMENT_ROOT'])) {
            $this->server['DOCUMENT_ROOT'] = $this->computeDocRoot($options['server']);
        } else {
            $this->server['DOCUMENT_ROOT'] = rtrim($this->server['DOCUMENT_ROOT'], '/\\');
        }

        $this->server['DOCUMENT_ROOT'] = $this->normalizePath($this->server['DOCUMENT_ROOT']);
        $this->get = $options['get'];
        $this->post = $options['post'];
        $this->cookie = $options['cookie'];
    }

    public function server($key = null)
    {
        if (null === $key) {
            return $this->server;
        }

        return isset($this->server[$key]) ? $this->server[$key] : null;
    }

    public function cookie($key = null, $default = null)
    {
        if (null === $key) {
            return $this->cookie;
        }

        return isset($this->cookie[$key]) ? $this->cookie[$key] : $default;
    }

    public function get($key = null, $default = null)
    {
        if (null === $key) {
            return $this->get;
        }

        return isset($this->get[$key]) ? $this->get[$key] : $default;
    }

    public function post($key = null, $default = null)
    {
        if (null === $key) {
            return $this->post;
        }

        return isset($this->post[$key]) ? $this->post[$key] : $default;
    }

    /**
     * turn windows-style slashes into unix-style,
     * remove trailing slash
     * and lowercase drive letter
     *
     * @param string $path absolute path
     *
     * @return string
     */
    public function normalizePath($path)
    {
        $realpath = realpath($path);
        if ($realpath) {
            $path = $realpath;
        }

        $path = str_replace('\\', '/', $path);
        $path = rtrim($path, '/');
        if (substr($path, 1, 1) === ':') {
            $path = lcfirst($path);
        }

        return $path;
    }

    protected $server = null;
    protected $get = null;
    protected $post = null;
    protected $cookie = null;

    /**
     * Compute $_SERVER['DOCUMENT_ROOT'] for IIS using SCRIPT_FILENAME and SCRIPT_NAME.
     *
     * @param array $server
     * @return string
     */
    protected function computeDocRoot(array $server)
    {
        if (isset($server['SERVER_SOFTWARE']) && 0 !== strpos($server['SERVER_SOFTWARE'], 'Microsoft-IIS/')) {
            throw new InvalidArgumentException('DOCUMENT_ROOT is not provided and could not be computed');
        }

        $substrLength = strlen($server['SCRIPT_FILENAME']) - strlen($server['SCRIPT_NAME']);
        $docRoot = substr($server['SCRIPT_FILENAME'], 0, $substrLength);

        return rtrim($docRoot, '\\');
    }
}
