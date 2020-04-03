<?php

namespace Minify;

use Minify_Cache_File;
use Minify_CacheInterface;
use Minify_Controller_MinApp;
use Minify_ControllerInterface;
use Minify_DebugDetector;
use Minify_Env;
use Minify_Source_Factory;
use Props\Container;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Monolog;
use Minify;

/**
 * @property Minify_CacheInterface            $cache
 * @property Config                           $config
 * @property string                           $configPath
 * @property Minify_ControllerInterface      $controller
 * @property string                           $dir
 * @property string                           $docRoot
 * @property Minify_Env                      $env
 * @property Monolog\Handler\ErrorLogHandler $errorLogHandler
 * @property array                            $groupsConfig
 * @property string                           $groupsConfigPath
 * @property LoggerInterface         $logger
 * @property \Minify                          $minify
 * @property array                            $serveOptions
 * @property Minify_Source_Factory           $sourceFactory
 * @property array                            $sourceFactoryOptions
 */
class App extends Container
{

    /**
     * Constructor
     *
     * @param string $dir Directory containing config files
     */
    public function __construct($dir)
    {
        $that = $this;

        $this->dir = rtrim($dir, '/\\');

        $this->cache = function (App $app) use ($that) {
            $config = $app->config;

            if ($config->cachePath instanceof Minify_CacheInterface) {
                return $config->cachePath;
            }

            if (!$config->cachePath || is_string($config->cachePath)) {
                return new Minify_Cache_File($config->cachePath, $config->cacheFileLocking, $app->logger);
            }

            $type = $that->typeOf($config->cachePath);
            throw new RuntimeException('$min_cachePath must be a path or implement Minify_CacheInterface.'
                . " Given $type");
        };

        $this->config = function (App $app) {
            $config = (require $app->configPath);

            if ($config instanceof Minify\Config) {
                return $config;
            }

            // copy from vars into properties

            $config = new Minify\Config();

            $propNames = array_keys(get_object_vars($config));

            $prefixer = function ($name) {
                return "min_$name";
            };
            $varNames = array_map($prefixer, $propNames);

            $varDefined = get_defined_vars();

            $varNames = array_filter($varNames, function ($name) use ($varDefined) {
                return array_key_exists($name, $varDefined);
            });

            $vars = compact($varNames);

            foreach ($varNames as $varName) {
                if (isset($vars[$varName])) {
                    $config->{substr($varName, 4)} = $vars[$varName];
                }
            }

            if ($config->documentRoot) {
                // copy into env
                if (empty($config->envArgs['server'])) {
                    $config->envArgs['server'] = $_SERVER;
                }
                $config->envArgs['server']['DOCUMENT_ROOT'] = $config->documentRoot;
            }

            return $config;
        };

        $this->configPath = "{$this->dir}/config.php";

        $this->controller = function (App $app) use ($that) {
            $config = $app->config;

            if (empty($config->factories['controller'])) {
                $ctrl = new Minify_Controller_MinApp($app->env, $app->sourceFactory, $app->logger);
            } else {
                $ctrl = call_user_func($config->factories['controller'], $app);
            }

            if ($ctrl instanceof Minify_ControllerInterface) {
                return $ctrl;
            }

            $type = $that->typeOf($ctrl);
            throw new RuntimeException('$min_factories["controller"] callable must return an implementation'
                ." of Minify_CacheInterface. Returned $type");
        };

        $this->docRoot = function (App $app) {
            $config = $app->config;
            if (empty($config->documentRoot)) {
                return $app->env->getDocRoot();
            }

            return $app->env->normalizePath($config->documentRoot);
        };

        $this->env = function (App $app) {
            return new Minify_Env($app->config->envArgs);
        };

        $this->errorLogHandler = function (App $app) {
            $format = "%channel%.%level_name%: %message% %context% %extra%";
            $handler = new Monolog\Handler\ErrorLogHandler();
            $handler->setFormatter(new Monolog\Formatter\LineFormatter($format));

            return $handler;
        };

        $this->groupsConfig = function (App $app) {
            return (require $app->groupsConfigPath);
        };

        $this->groupsConfigPath = "{$this->dir}/groupsConfig.php";

        $this->logger = function (App $app) use ($that) {
            $value = $app->config->errorLogger;

            if ($value instanceof LoggerInterface) {
                return $value;
            }

            $logger = new Monolog\Logger('minify');

            if (!$value) {
                return $logger;
            }

            if ($value === true || $value instanceof \FirePHP) {
                $logger->pushHandler($app->errorLogHandler);
                $logger->pushHandler(new Monolog\Handler\FirePHPHandler());

                return $logger;
            }

            if ($value instanceof Monolog\Handler\HandlerInterface) {
                $logger->pushHandler($value);

                return $logger;
            }

            // BC
            if (is_object($value) && is_callable(array($value, 'log'))) {
                $handler = new Minify\Logger\LegacyHandler($value);
                $logger->pushHandler($handler);

                return $logger;
            }

            $type = $that->typeOf($value);
            throw new RuntimeException('If set, $min_errorLogger must be a PSR-3 logger or a Monolog handler.'
                ." Given $type");
        };

        $this->minify = function (App $app) use ($that) {
            $config = $app->config;

            if (empty($config->factories['minify'])) {
                return new \Minify($app->cache, $app->logger);
            }

            $minify = call_user_func($config->factories['minify'], $app);
            if ($minify instanceof \Minify) {
                return $minify;
            }

            $type = $that->typeOf($minify);
            throw new RuntimeException('$min_factories["minify"] callable must return a Minify object.'
                ." Returned $type");
        };

        $this->serveOptions = function (App $app) {
            $config = $app->config;
            $env = $app->env;

            $ret = $config->serveOptions;

            $ret['minifierOptions']['text/css']['docRoot'] = $app->docRoot;
            $ret['minifierOptions']['text/css']['symlinks'] = $config->symlinks;
            $ret['minApp']['symlinks'] = $config->symlinks;

            // auto-add targets to allowDirs
            foreach ($config->symlinks as $uri => $target) {
                $ret['minApp']['allowDirs'][] = $target;
            }

            if ($config->allowDebugFlag) {
                $ret['debug'] = Minify_DebugDetector::shouldDebugRequest($env);
            }

            if ($config->concatOnly) {
                $ret['concatOnly'] = true;
            }

            // check for URI versioning
            if ($env->get('v') !== null || preg_match('/&\\d/', $app->env->server('QUERY_STRING'))) {
                $ret['maxAge'] = 31536000;
            }

            // need groups config?
            if ($env->get('g') !== null) {
                $ret['minApp']['groups'] = $app->groupsConfig;
            }

            return $ret;
        };

        $this->sourceFactory = function (App $app) {
            return new Minify_Source_Factory($app->env, $app->sourceFactoryOptions, $app->cache);
        };

        $this->sourceFactoryOptions = function (App $app) {
            $serveOptions = $app->serveOptions;
            $ret = array();

            // translate legacy setting to option for source factory
            if (isset($serveOptions['minApp']['noMinPattern'])) {
                $ret['noMinPattern'] = $serveOptions['minApp']['noMinPattern'];
            }

            if (isset($serveOptions['minApp']['allowDirs'])) {
                $ret['allowDirs'] = $serveOptions['minApp']['allowDirs'];
            }

            if (isset($serveOptions['checkAllowDirs'])) {
                $ret['checkAllowDirs'] = $serveOptions['checkAllowDirs'];
            }

            if (is_numeric($app->config->uploaderHoursBehind)) {
                $ret['uploaderHoursBehind'] = $app->config->uploaderHoursBehind;
            }

            return $ret;
        };
    }

    public function runServer()
    {
        if (!$this->env->get('f') && $this->env->get('g') === null) {
            // no spec given
            $msg = '<p>No "f" or "g" parameters were detected.</p>';
            $url = 'https://github.com/mrclay/minify/blob/master/docs/CommonProblems.wiki.md#long-url-parameters-are-ignored';
            $defaults = $this->minify->getDefaultOptions();
            $this->minify->errorExit($defaults['badRequestHeader'], $url, $msg);
        }

        $this->minify->serve($this->controller, $this->serveOptions);
    }

    /**
     * @param mixed $var
     * @return string
     */
    private function typeOf($var)
    {
        $type = gettype($var);

        return $type === 'object' ? get_class($var) : $type;
    }
}
