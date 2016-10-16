<?php

class Minify_Source_Factory
{

    /**
     * @var array
     */
    protected $options;

    /**
     * @var callable[]
     */
    protected $handlers = array();

    /**
     * @var Minify_Env
     */
    protected $env;

    /**
     * @param Minify_Env            $env
     * @param array                 $options
     *
     *   noMinPattern        : Pattern matched against basename of the filepath (if present). If the pattern
     *                         matches, Minify will try to avoid re-compressing the resource.
     *
     *   fileChecker         : Callable responsible for verifying the existence of the file.
     *
     *   resolveDocRoot      : If true, a leading "//" will be replaced with the document root.
     *
     *   checkAllowDirs      : If true, the filepath will be verified to be within one of the directories
     *                         specified by allowDirs.
     *
     *   allowDirs           : Directory paths in which sources can be served.
     *
     *   uploaderHoursBehind : How many hours behind are the file modification times of uploaded files?
     *                         If you upload files from Windows to a non-Windows server, Windows may report
     *                         incorrect mtimes for the files. Immediately after modifying and uploading a
     *                         file, use the touch command to update the mtime on the server. If the mtime
     *                         jumps ahead by a number of hours, set this variable to that number. If the mtime
     *                         moves back, this should not be needed.
     *
     * @param Minify_CacheInterface $cache Optional cache for handling .less files.
     *
     */
    public function __construct(Minify_Env $env, array $options = array(), Minify_CacheInterface $cache = null)
    {
        $this->env = $env;
        $this->options = array_merge(array(
            'noMinPattern' => '@[-\\.]min\\.(?:[a-zA-Z]+)$@i', // matched against basename
            'fileChecker' => array($this, 'checkIsFile'),
            'resolveDocRoot' => true,
            'checkAllowDirs' => true,
            'allowDirs' => array('//'),
            'uploaderHoursBehind' => 0,
        ), $options);

        // resolve // in allowDirs
        $docRoot = $env->getDocRoot();
        foreach ($this->options['allowDirs'] as $i => $dir) {
            if (0 === strpos($dir, '//')) {
                $this->options['allowDirs'][$i] = $docRoot . substr($dir, 1);
            }
        }

        if ($this->options['fileChecker'] && !is_callable($this->options['fileChecker'])) {
            throw new InvalidArgumentException("fileChecker option is not callable");
        }

        $this->setHandler('~\.less$~i', function ($spec) use ($cache) {
            return new Minify_LessCssSource($spec, $cache);
        });

        $this->setHandler('~\.scss~i', function ($spec) use ($cache) {
            return new Minify_ScssCssSource($spec, $cache);
        });

        $this->setHandler('~\.(js|css)$~i', function ($spec) {
            return new Minify_Source($spec);
        });
    }

    /**
     * @param string   $basenamePattern A pattern tested against basename. E.g. "~\.css$~"
     * @param callable $handler         Function that recieves a $spec array and returns a Minify_SourceInterface
     */
    public function setHandler($basenamePattern, $handler)
    {
        $this->handlers[$basenamePattern] = $handler;
    }

    /**
     * @param string $file
     * @return string
     *
     * @throws Minify_Source_FactoryException
     */
    public function checkIsFile($file)
    {
        $realpath = realpath($file);
        if (!$realpath) {
            throw new Minify_Source_FactoryException("File failed realpath(): $file");
        }

        $basename = basename($file);
        if (0 === strpos($basename, '.')) {
            throw new Minify_Source_FactoryException("Filename starts with period (may be hidden): $basename");
        }

        if (!is_file($realpath) || !is_readable($realpath)) {
            throw new Minify_Source_FactoryException("Not a file or isn't readable: $file");
        }

        return $realpath;
    }

    /**
     * @param mixed $spec
     *
     * @return Minify_SourceInterface
     *
     * @throws Minify_Source_FactoryException
     */
    public function makeSource($spec)
    {
        if (is_string($spec)) {
            $spec = array(
                'filepath' => $spec,
            );
        } elseif ($spec instanceof Minify_SourceInterface) {
            return $spec;
        }

        $source = null;

        if (empty($spec['filepath'])) {
            // not much we can check
            return new Minify_Source($spec);
        }

        if ($this->options['resolveDocRoot'] && 0 === strpos($spec['filepath'], '//')) {
            $spec['filepath'] = $this->env->getDocRoot() . substr($spec['filepath'], 1);
        }

        if (!empty($this->options['fileChecker'])) {
            $spec['filepath'] = call_user_func($this->options['fileChecker'], $spec['filepath']);
        }

        if ($this->options['checkAllowDirs']) {
            $allowDirs = (array)$this->options['allowDirs'];
            $inAllowedDir = false;
            $filePath = $this->env->normalizePath($spec['filepath']);
            foreach ($allowDirs as $allowDir) {
                if (strpos($filePath, $this->env->normalizePath($allowDir)) === 0) {
                    $inAllowedDir = true;
                }
            }

            if (!$inAllowedDir) {
                $allowDirsStr = implode(';', $allowDirs);
                throw new Minify_Source_FactoryException("File '{$spec['filepath']}' is outside \$allowDirs "
                    . "($allowDirsStr). If the path is resolved via an alias/symlink, look into the "
                    . "\$min_symlinks option.");
            }
        }

        $basename = basename($spec['filepath']);

        if ($this->options['noMinPattern'] && preg_match($this->options['noMinPattern'], $basename)) {
            if (preg_match('~\.(css|less)$~i', $basename)) {
                $spec['minifyOptions']['compress'] = false;
                // we still want URI rewriting to work for CSS
            } else {
                $spec['minifier'] = 'Minify::nullMinifier';
            }
        }

        $hoursBehind = $this->options['uploaderHoursBehind'];
        if ($hoursBehind != 0) {
            $spec['uploaderHoursBehind'] = $hoursBehind;
        }

        foreach ($this->handlers as $basenamePattern => $handler) {
            if (preg_match($basenamePattern, $basename)) {
                $source = call_user_func($handler, $spec);
                break;
            }
        }

        if (!$source) {
            throw new Minify_Source_FactoryException("Handler not found for file: $basename");
        }

        return $source;
    }
}
