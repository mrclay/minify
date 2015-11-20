<?php

/**
 * Class Minify_ClosureCompiler
 * @package Minify
 */

/**
 * Run Closure Compiler via NailGun
 *
 * @package Minify
 * @author Elan Ruusamäe <glen@delfi.ee>
 * @link https://github.com/martylamb/nailgun
 */
class Minify_NailgunClosureCompiler extends Minify_ClosureCompiler
{
    const NG_SERVER = 'com.martiansoftware.nailgun.NGServer';
    const CC_MAIN = 'com.google.javascript.jscomp.CommandLineRunner';

    // exit code that ng gives
    // FIXME: figure out the meaning of the value
    const NG_EXIT_CODE = 227;

    /**
     * Filepath of "ng" executable (from Nailgun package)
     *
     * @var string
     */
    public static $ngExecutable = 'ng';

    /**
     * Filepath of the Nailgun jar file.
     *
     * @var string
     */
    public static $ngJarFile;

    /**
     * Get command to launch NailGun server.
     *
     * @return array
     */
    protected function getServerCommandLine()
    {
        $this->checkJar(self::$ngJarFile);
        $this->checkJar(self::$jarFile);

        $classPath = array(
            self::$ngJarFile,
            self::$jarFile,
        );

        // The command for the server that should show up in the process list
        $server = array(
            self::$javaExecutable,
            '-server',
            '-cp', join(':', $classPath),
            self::NG_SERVER,
        );

        return $server;
    }

    /**
     * @return array
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function getCompilerCommandLine()
    {
        $server = array(
            self::$ngExecutable,
            escapeshellarg(self::CC_MAIN)
        );

        return $server;
    }

    /**
     * @param string $tmpFile
     * @param array $options
     * @return string
     * @throws Minify_ClosureCompiler_Exception
     */
    protected function compile($tmpFile, $options)
    {
        $this->startServer();

        $command = $this->getCommand($options, $tmpFile);

        return implode("\n", $this->shell($command, array(self::NG_EXIT_CODE)));
    }

    private function startServer()
    {
        $serverCommand = join(' ', $this->getServerCommandLine());
        $psCommand = $this->shell("ps -o cmd= -C " . self::$javaExecutable);
        if (array_search($serverCommand, $psCommand) !== false) {
            // already started!
            return;
        }

        $this->shell("$serverCommand </dev/null >/dev/null 2>/dev/null & sleep 10");
    }
}