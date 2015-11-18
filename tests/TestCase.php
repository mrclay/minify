<?php

class TestCase extends PHPUnit_Framework_TestCase
{
    /** @var string */
    protected static $test_files;

    public static function setupBeforeClass()
    {
        self::$test_files = __DIR__ . '/../min_unit_tests/_test_files';
    }
}