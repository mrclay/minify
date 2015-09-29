<?php

class Minify_Env {

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
            'cookie' => $_COOKIE,
        ), $options);

        $this->server = $options['server'];
        if (empty($this->server['DOCUMENT_ROOT'])) {
            $this->server['DOCUMENT_ROOT'] = $this->computeDocRoot($options['server']);
        } else {
            $this->server['DOCUMENT_ROOT'] = rtrim($this->server['DOCUMENT_ROOT'], '/\\');
        }
        $this->get = $options['get'];
        $this->cookie = $options['cookie'];
    }

    public function server($key = null)
    {
        if (null === $key) {
            return $this->server;
        }
        return isset($this->server[$key])
            ? $this->server[$key]
            : null;
    }

    public function cookie($key = null)
    {
        if (null === $key) {
            return $this->cookie;
        }
        return isset($this->cookie[$key])
            ? $this->cookie[$key]
            : null;
    }

    public function get($key = null)
    {
        if (null === $key) {
            return $this->get;
        }
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
        if (empty($server['SERVER_SOFTWARE'])
                || 0 !== strpos($server['SERVER_SOFTWARE'], 'Microsoft-IIS/')) {
            throw new InvalidArgumentException('DOCUMENT_ROOT is not provided and could not be computed');
        }
        $docRoot = substr(
            $server['SCRIPT_FILENAME']
            ,0
            ,strlen($server['SCRIPT_FILENAME']) - strlen($server['SCRIPT_NAME'])
        );
        return rtrim($docRoot, '\\');
    }
}
