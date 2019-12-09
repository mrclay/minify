<?php

/**
 * Compress Javascript/CSS using the YUI Compressor
 *
 * You must set $jarFile and $tempDir before calling the minify functions.
 * Also, depending on your shell's environment, you may need to specify
 * the full path to java in $javaExecutable or use putenv() to setup the
 * Java environment.
 *
 * <code>
 * Minify_YUICompressor::$jarFile = '/path/to/yuicompressor-2.4.6.jar';
 * Minify_YUICompressor::$tempDir = '/tmp';
 * $code = Minify_YUICompressor::minifyJs(
 *   $code
 *   ,array('nomunge' => true, 'line-break' => 1000)
 * );
 * </code>
 *
 * Note: In case you run out stack (default is 512k), you may increase stack size in $options:
 *   array('stack-size' => '2048k')
 *
 * @TODO: unit tests, $options docs
 */
class Minify_YUICompressor
{
    /**
     * Filepath of the YUI Compressor jar file. This must be set before
     * calling minifyJs() or minifyCss().
     *
     * @var string
     */
    public static $jarFile;

    /**
     * Writable temp directory. This must be set before calling minifyJs()
     * or minifyCss().
     *
     * @var string
     */
    public static $tempDir;

    /**
     * Filepath of "java" executable (may be needed if not in shell's PATH)
     *
     * @var string
     */
    public static $javaExecutable = 'java';

    /**
     * Minify a CSS string
     *
     * @param string $css
     * @param array  $options (verbose is ignored)
     *
     * @return string
     *
     * @see http://www.julienlecomte.net/yuicompressor/README
     */
    public static function minifyCss($css, $options = array())
    {
        return self::_minify('css', $css, $options);
    }

    /**
     * Minify a Javascript string
     *
     * @param string $js
     * @param array  $options (verbose is ignored)
     *
     * @return string
     *
     * @see http://www.julienlecomte.net/yuicompressor/README
     */
    public static function minifyJs($js, $options = array())
    {
        return self::_minify('js', $js, $options);
    }

    /**
     * @param string $type
     * @param string $content
     * @param array  $options
     *
     * @return string
     */
    private static function _minify($type, $content, $options)
    {
        self::_prepare();
        if (!($tmpFile = \tempnam(self::$tempDir, 'yuic_'))) {
            throw new Exception('Minify_YUICompressor : could not create temp file in "' . self::$tempDir . '".');
        }

        \file_put_contents($tmpFile, $content);
        \exec(self::_getCmd($options, $type, $tmpFile), $output, $result_code);
        \unlink($tmpFile);
        if ($result_code !== 0) {
            throw new Exception('Minify_YUICompressor : YUI compressor execution failed.');
        }

        return \implode("\n", $output);
    }

    /**
     * @return void
     */
    private static function _prepare()
    {
        if (!\is_file(self::$jarFile)) {
            throw new Exception('Minify_YUICompressor : $jarFile(' . self::$jarFile . ') is not a valid file.');
        }

        if (!\is_readable(self::$jarFile)) {
            throw new Exception('Minify_YUICompressor : $jarFile(' . self::$jarFile . ') is not readable.');
        }

        if (!\is_dir(self::$tempDir)) {
            throw new Exception('Minify_YUICompressor : $tempDir(' . self::$tempDir . ') is not a valid direcotry.');
        }

        if (!\is_writable(self::$tempDir)) {
            throw new Exception('Minify_YUICompressor : $tempDir(' . self::$tempDir . ') is not writable.');
        }
    }

    /**
     * @param array  $userOptions
     * @param string $type
     * @param string $tmpFile
     *
     * @return string
     */
    private static function _getCmd($userOptions, $type, $tmpFile)
    {
        $defaults = array(
            'charset'               => '',
            'line-break'            => 5000,
            'type'                  => $type,
            'nomunge'               => false,
            'preserve-semi'         => false,
            'disable-optimizations' => false,
            'stack-size'            => '',
        );
        $o = \array_merge($defaults, $userOptions);

        $cmd = self::$javaExecutable
               . (!empty($o['stack-size']) ? ' -Xss' . $o['stack-size'] : '')
               . ' -jar ' . \escapeshellarg(self::$jarFile)
               . " --type {$type}"
               . (\preg_match('/^[\\da-zA-Z0-9\\-]+$/', $o['charset'])
                ? " --charset {$o['charset']}"
                : '')
               . (\is_numeric($o['line-break']) && $o['line-break'] >= 0
                ? ' --line-break ' . (int) $o['line-break']
                : '');

        if ($type === 'js') {
            foreach (array('nomunge', 'preserve-semi', 'disable-optimizations') as $opt) {
                $cmd .= $o[$opt]
                    ? " --{$opt}"
                    : '';
            }
        }

        return $cmd . ' ' . \escapeshellarg($tmpFile);
    }
}
