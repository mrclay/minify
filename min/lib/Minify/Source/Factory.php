<?php

class Minify_Source_Factory {

    protected $options;
    protected $handlers = array();
    protected $env;

    public function __construct(Minify_Env $env, array $options = array())
    {
        $this->env = $env;
        $this->options = array_merge(array(
            'noMinPattern' => '@[-\\.]min\\.(?:js|css)$@i', // matched against basename
            'uploaderHoursBehind' => 0,
            'fileChecker' => array($this, 'checkIsFile'),
            'resolveDocRoot' => true,
            'checkAllowDirs' => true,
            'allowDirs' => array($env->getDocRoot()),
        ), $options);

        if ($this->options['fileChecker'] && !is_callable($this->options['fileChecker'])) {
            throw new InvalidArgumentException("fileChecker option is not callable");
        }
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
        $source = null;

        if ($spec instanceof Minify_SourceInterface) {
            $source = $spec;
        }

        if (empty($spec['filepath'])) {
            // not much we can check
            return new Minify_Source($spec);
        }

        if ($this->options['resolveDocRoot']) {
            if (0 === strpos($spec['filepath'], '//')) {
                $spec['filepath'] = $this->env->getDocRoot() . substr($spec['filepath'], 1);
            }
        }

        if (!empty($this->options['fileChecker'])) {
            $spec['filepath'] = call_user_func($this->options['fileChecker'], $spec['filepath']);
        }

        if ($this->options['checkAllowDirs']) {
            foreach ((array)$this->options['allowDirs'] as $allowDir) {
                if (strpos($spec['filepath'], $allowDir) !== 0) {
                    throw new Minify_Source_FactoryException("File '{$spec['filepath']}' is outside \$allowDirs."
                        . " If the path is resolved via an alias/symlink, look into the \$min_symlinks option.");
                }
            }
        }

        $basename = basename($spec['filepath']);

        if ($this->options['noMinPattern'] && preg_match($this->options['noMinPattern'], $basename)) {
            if (preg_match('~\.css$~i', $basename)) {
                $spec['minifyOptions']['compress'] = false;
                // we still want URI rewriting to work for CSS
            } else {
                $spec['minifier'] = '';
            }
        }

        $hoursBehind = $this->options['uploaderHoursBehind'];
        if ($hoursBehind != 0) {
            $spec['uploaderHoursBehind'] = $hoursBehind;
        }

        foreach ($this->handlers as $basenamePattern => $handler) {
            if (preg_match($basenamePattern, $basename)) {
                $source = $handler($spec);
                break;
            }
        }

        if (!$source) {
            if (in_array(pathinfo($spec['filepath'], PATHINFO_EXTENSION), array('css', 'js'))) {
                $source = new Minify_Source($spec);
            } else {
                throw new Minify_Source_FactoryException("Handler not found for file: {$spec['filepath']}");
            }
        }

        return $source;
    }
}
