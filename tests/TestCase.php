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
     * Excluding from phpunit.xml does not work, even using dir,
     * hence this dummy test.
     *
     * @link http://stackoverflow.com/q/2736343/2314626
     */
    public function test_does_nothing()
    {

    }
}