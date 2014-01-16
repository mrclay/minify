<?php
/**
 * Class Minify_FastClosureCompiler
 * @package Minify
 */

/**
 * Compress Javascript using the Closure Compiler
 *
 * Needs https://npmjs.org/package/fast-closure-compiler to be installed
 * You must set $binPath and $tempDir before calling the minify functions.
 *
 * <code>
 * Minify_ClosureCompiler::$binPath = '/usr/local/bin/closure';
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
 * @todo unit tests, $options docs
 * @todo more options support (or should just passthru them all?)
 *
 * @package Minify
 */
class Minify_FastClosureCompiler {

    /**
     * Filepath of the Closure Compiler jar file. This must be set before
     * calling minifyJs().
     *
     * @var string
     */
    public static $binPath = '/usr/local/bin/closure';

    /**
     * Writable temp directory. This must be set before calling minifyJs().
     *
     * @var string
     */
    public static $tempDir = '/tmp';

    /**
     * Minify a Javascript string
     *
     * @param string $js
     *
     * @param array $options (verbose is ignored)
     *
     * @see https://code.google.com/p/closure-compiler/source/browse/trunk/README
     *
     * @return string
     */
    public static function minify($js, $options = array())
    {
        self::_prepare();
        if (! ($tmpFile = tempnam(self::$tempDir, 'cc_'))) {
            throw new Exception('Minify_FastClosureCompiler : could not create temp file in "'.self::$tempDir.'".');
        }
        file_put_contents($tmpFile, $js);
        exec(self::_getCmd($options, $tmpFile), $output, $result_code);
        unlink($tmpFile);
        // For some reasons Nailgun thinks that it's server
        // broke the connection and returns 227 instead of 0
        // We'll just handle this here instead of fixing
        // the nailgun client itself.
        if ($result_code != 0 && $result_code != 227) {
            throw new Exception('Minify_FastClosureCompiler : Closure Compiler execution failed.');

        }
        return implode("\n", $output);
    }

    private static function _getCmd($userOptions, $tmpFile)
    {
        $o = array_merge(
            array(
                'charset' => 'utf-8',
                'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
            ),
            $userOptions
        );
        $cmd = self::$binPath . ' '
             . (preg_match('/^[\\da-zA-Z0-9\\-]+$/', $o['charset'])
                ? " --charset {$o['charset']}"
                : '');

        foreach ($o as $key => $value) {
            $cmd .= " --{$key} ". escapeshellarg($value);
        }
        return $cmd . ' ' . escapeshellarg($tmpFile);
    }

    private static function _prepare()
    {
        if (! is_file(self::$binPath)) {
            throw new Exception('Minify_FastClosureCompiler : $binPath('.self::$binPath.') is not a valid file.');
        }
        if (! is_readable(self::$binPath)) {
            throw new Exception('Minify_FastClosureCompiler : $binPath('.self::$binPath.') is not readable.');
        }
        if (! is_dir(self::$tempDir)) {
            throw new Exception('Minify_FastClosureCompiler : $tempDir('.self::$tempDir.') is not a valid directory.');
        }
        if (! is_writable(self::$tempDir)) {
            throw new Exception('Minify_FastClosureCompiler : $tempDir('.self::$tempDir.') is not writable.');
        }
    }
}

/* vim:ts=4:sw=4:et */
