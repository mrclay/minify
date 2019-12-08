<?php

namespace Minify\Test;

use Minify_CacheInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    protected static $document_root;

    /** @var string */
    protected static $test_files;

    public static function setupBeforeClass()
    {
        self::$document_root = __DIR__;
        self::$test_files = __DIR__ . '/_test_files';
    }

    /**
     * Get number of bytes in a string regardless of mbstring.func_overload
     *
     * @param string $str
     *
     * @return int
     */
    protected function countBytes($str)
    {
        return (\function_exists('mb_strlen') && ((int) \ini_get('mbstring.func_overload') & 2))
            ? \mb_strlen($str, '8bit')
            : \strlen($str);
    }

    /**
     * Common assertion for cache tests.
     *
     * @param Minify_CacheInterface $cache
     * @param string $id
     * @param string $data
     */
    protected function assertTestCache(Minify_CacheInterface $cache, $id, $data)
    {
        static::assertTrue($cache->store($id, $data), "${id} store");
        static::assertSame($cache->getSize($id), $this->countBytes($data), "${id} getSize");
        static::assertTrue($cache->isValid($id, $_SERVER['REQUEST_TIME'] - 10), "${id} isValid");

        \ob_start();
        $cache->display($id);
        $displayed = \ob_get_contents();
        \ob_end_clean();

        static::assertSame($data, $displayed, "${id} display");
        static::assertSame($data, $cache->fetch($id), "${id} fetch");
    }

    /**
     * Read data file, assert that it exists and is not empty.
     * As a side effect calls trim() to fight against different Editors that insert or strip final newline.
     *
     * @param string $filename
     *
     * @return string
     */
    protected function getDataFile($filename)
    {
        $path = self::$test_files . '/' . $filename;
        static::assertFileExists($path);
        $contents = \file_get_contents($path);
        static::assertNotEmpty($contents);
        $contents = \trim($contents);
        static::assertNotEmpty($contents);

        return $contents;
    }
}
