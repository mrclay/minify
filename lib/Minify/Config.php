<?php

namespace Minify;

use Minify_CacheInterface;

class Config
{
    /**
     * @var bool
     */
    public $enableBuilder = false;

    /**
     * @var bool
     */
    public $enableStatic = false;

    /**
     * @var bool
     */
    public $concatOnly = false;

    /**
     * @var string
     */
    public $builderPassword = 'admin';

    /**
     * @var bool|object
     */
    public $errorLogger = false;

    /**
     * @var bool
     */
    public $allowDebugFlag = false;

    /**
     * @var string|Minify_CacheInterface
     */
    public $cachePath = '';

    /**
     * @var string
     */
    public $documentRoot = '';

    /**
     * @var bool
     */
    public $cacheFileLocking = true;

    /**
     * @var array
     */
    public $serveOptions = array();

    /**
     * @var array
     */
    public $symlinks = array();

    /**
     * @var int
     */
    public $uploaderHoursBehind = 0;

    /**
     * @var array
     */
    public $envArgs = array();

    /**
     * @var callable[]
     */
    public $factories = array();
}
