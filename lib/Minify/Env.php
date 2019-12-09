<?php

class Minify_Env
{
    /**
     * @var array
     */
    protected $server;

    /**
     * @var array
     */
    protected $get;

    /**
     * @var array
     */
    protected $post;

    /**
     * @var array
     */
    protected $cookie;

    /**
     * @param array $options
     */
    public function __construct($options = array())
    {
        $options = \array_merge(
            array(
                'server' => $_SERVER,
                'get'    => $_GET,
                'post'   => $_POST,
                'cookie' => $_COOKIE,
            ),
            $options
        );

        if (!isset($options['server'])) {
            $options['server'] = array();
        }
        $this->server = $options['server'];
        if (empty($this->server['DOCUMENT_ROOT'])) {
            $this->server['DOCUMENT_ROOT'] = $this->computeDocRoot($options['server']);
        } else {
            $this->server['DOCUMENT_ROOT'] = \rtrim($this->server['DOCUMENT_ROOT'], '/\\');
        }

        $this->server['DOCUMENT_ROOT'] = $this->normalizePath($this->server['DOCUMENT_ROOT']);

        if (!isset($options['get'])) {
            $options['get'] = array();
        }
        $this->get = $options['get'];

        if (!isset($options['post'])) {
            $options['post'] = array();
        }
        $this->post = $options['post'];

        if (!isset($options['cookie'])) {
            $options['cookie'] = array();
        }
        $this->cookie = $options['cookie'];
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
        $realpath = \realpath($path);
        if ($realpath) {
            $path = $realpath;
        }

        $path = \str_replace('\\', '/', $path);
        $path = \rtrim($path, '/');
        if ($path[1] === ':') {
            $path = \lcfirst($path);
        }

        return $path;
    }

    /**
     * @param string|null $key
     * @param mixed|null  $default
     *
     * @return array|mixed|null
     */
    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookie;
        }

        return isset($this->cookie[$key]) ? $this->cookie[$key] : $default;
    }

    /**
     * @param string|null $key
     * @param mixed|null  $default
     *
     * @return array|mixed|null
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }

        return isset($this->get[$key]) ? $this->get[$key] : $default;
    }

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

    /**
     * @param string|null $key
     * @param mixed|null  $default
     *
     * @return array|mixed|null
     */
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }

        return isset($this->post[$key]) ? $this->post[$key] : $default;
    }

    /**
     * @param string|null $key
     *
     * @return array|mixed|null
     */
    public function server($key = null)
    {
        if ($key === null) {
            return $this->server;
        }

        return isset($this->server[$key]) ? $this->server[$key] : null;
    }

    /**
     * Compute $_SERVER['DOCUMENT_ROOT'] for IIS using SCRIPT_FILENAME and SCRIPT_NAME.
     *
     * @param array $server
     *
     * @return string
     */
    protected function computeDocRoot(array $server)
    {
        if (
            isset($server['SERVER_SOFTWARE'])
            &&
            \strpos($server['SERVER_SOFTWARE'], 'Microsoft-IIS/') !== 0
        ) {
            throw new InvalidArgumentException('DOCUMENT_ROOT is not provided and could not be computed');
        }

        $substrLength = \strlen($server['SCRIPT_FILENAME']) - \strlen($server['SCRIPT_NAME']);
        $docRoot = \substr($server['SCRIPT_FILENAME'], 0, $substrLength);

        return \rtrim($docRoot, '\\');
    }
}
