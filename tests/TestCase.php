<?php

class TestCase extends PHPUnit_Framework_TestCase
{
    /** @var string */
    protected static $test_files;

    public static function setupBeforeClass()
    {
        self::$test_files = __DIR__ . '/../min_unit_tests/_test_files';
    }

    /**
     * Get number of bytes in a string regardless of mbstring.func_overload
     *
     * @param string $str
     * @return int
     */
    protected function countBytes($str)
    {
        return (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2))
            ? mb_strlen($str, '8bit')
            : strlen($str);
    }

    /**
     * Excluding from phpunit.xml does not work, even using dir,
     * hence this dummy test.
     *
     * @link http://stackoverflow.com/q/2736343/2314626
     */
    public function test_does_nothing()
    {

    }
}