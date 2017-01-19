<?php
/**
 * Class Minify_ClosureCompiler
 * @package Minify
 */

/**
 * Compress Javascript using the Closure Compiler
 *
 * You must set $jarFile and $tempDir before calling the minify functions.
 * Also, depending on your shell's environment, you may need to specify
 * the full path to java in $javaExecutable or use putenv() to setup the
 * Java environment.
 *
 * <code>
 * Minify_ClosureCompiler::$jarFile = '/path/to/closure-compiler-20120123.jar';
 * Minify_ClosureCompiler::$tempDir = '/tmp';
 * $code = Minify_ClosureCompiler::minify(
 *   $code,
 *   array('compilation_level' => 'SIMPLE_OPTIMIZATIONS')
 * );
 *
 * --compilation_level WHITESPACE_ONLY, SIMPLE_OPTIMIZATIONS, ADVANCED_OPTIMIZATIONS
 *
 * </code>
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 * @author Elan Ruusamäe <glen@delfi.ee>
 */
class Minify_ClosureCompiler
{
    public static $isDebug = false;

    /**
     * Filepath of the Closure Compiler jar file. This must be set before
     * calling minifyJs().
     *
     * @var string
     */
    public static $jarFile = null;

    /**
     * Writable temp directory. This must be set before calling minifyJs().
     *
     * @var string
     */
    public static $tempDir = null;

    /**
     * Filepath of "java" executable (may be needed if not in shell's PATH)
     *
     * @var string
     */
    public static $javaExecutable = 'java';

    /**
     * Default command line options passed to closure-compiler
     *
     * @var array
     */
    public static $defaultOptions = array(
        'charset' => 'utf-8',
        'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
        'warning_level' => 'QUIET',
    );

    /**
     * Minify a Javascript string
     *
     * @param string $js
     * @param array $options (verbose is ignored)
     * @see https://code.google.com/p/closure-compiler/source/browse/trunk/README
     * @return string
     * @throws Minify_ClosureCompiler_Exception
     */
    public static function minify($js, $options = array())
    {
        $min = new static();

        return $min->process($js, $options);
    }

    /**
     * Process $js using $options.
     *
     * @param string $js
     * @param array $options
     * @return string
     * @throws Exception
     * @throws Minify_ClosureCompiler_Exception
     */
    public function process($js, $options)
    {
        $tmpFile = $this->dumpFile(self::$tempDir, $js);
        try {
            $result = $this->compile($tmpFile, $options);
        } catch (Exception $e) {
            unlink($tmpFile);
            throw $e;
        }
        unlink($tmpFile);

        return $result;
    }

    /**
     * @param string $tmpFile
     * @param array $options
     * @return string
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function compile($tmpFile, $options)
    {
        $command = $this->getCommand($options, $tmpFile);

        return implode("\n", $this->shell($command));
    }

    /**
     * @param array $userOptions
     * @param string $tmpFile
     * @return string
     */
    protected function getCommand($userOptions, $tmpFile)
    {
        $args = array_merge(
            $this->getCompilerCommandLine(),
            $this->getOptionsCommandLine($userOptions)
        );

        return join(' ', $args) . ' ' . escapeshellarg($tmpFile);
    }

    /**
     * @return array
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function getCompilerCommandLine()
    {
        $this->checkJar(self::$jarFile);
        $server = array(
            self::$javaExecutable,
            '-jar',
            escapeshellarg(self::$jarFile)
        );

        return $server;
    }

    /**
     * @param array $userOptions
     * @return array
     */
    protected function getOptionsCommandLine($userOptions)
    {
        $args = array();

        $options = array_merge(
            static::$defaultOptions,
            $userOptions
        );

        foreach ($options as $key => $value) {
            $args[] = "--{$key} " . escapeshellarg($value);
        }

        return $args;
    }

    /**
     * @param string $jarFile
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function checkJar($jarFile)
    {
        if (!is_file($jarFile)) {
            throw new Minify_ClosureCompiler_Exception('$jarFile(' . $jarFile . ') is not a valid file.');
        }
        if (!is_readable($jarFile)) {
            throw new Minify_ClosureCompiler_Exception('$jarFile(' . $jarFile . ') is not readable.');
        }
    }

    /**
     * @param string $tempDir
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function checkTempdir($tempDir)
    {
        if (!is_dir($tempDir)) {
            throw new Minify_ClosureCompiler_Exception('$tempDir(' . $tempDir . ') is not a valid direcotry.');
        }
        if (!is_writable($tempDir)) {
            throw new Minify_ClosureCompiler_Exception('$tempDir(' . $tempDir . ') is not writable.');
        }
    }

    /**
     * Write $content to a temporary file residing in $dir.
     *
     * @param string $dir
     * @param string $content
     * @return string
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function dumpFile($dir, $content)
    {
        $this->checkTempdir($dir);
        $tmpFile = tempnam($dir, 'cc_');
        if (!$tmpFile) {
            throw new Minify_ClosureCompiler_Exception('Could not create temp file in "' . $dir . '".');
        }
        file_put_contents($tmpFile, $content);

        return $tmpFile;
    }

    /**
     * Execute command, throw if exit code is not in $expectedCodes array
     *
     * @param string $command
     * @param array $expectedCodes
     * @return mixed
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function shell($command, $expectedCodes = array(0))
    {
        exec($command, $output, $result_code);
        if (!in_array($result_code, $expectedCodes)) {
            throw new Minify_ClosureCompiler_Exception("Unpexpected return code: $result_code");
        }

        return $output;
    }
}

class Minify_ClosureCompiler_Exception extends Exception
{
}
