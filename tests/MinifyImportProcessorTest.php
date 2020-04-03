<?php

namespace Minify\Test;

use Minify_ImportProcessor;

class MinifyImportProcessorTest extends TestCase
{
    public function test()
    {
        $linDir = self::$test_files . '/importProcessor';

        $expected = file_get_contents($linDir . '/css/output.css');
        $actual = Minify_ImportProcessor::process($linDir . '/css/input.css');
        $this->assertSame($expected, $actual, 'ImportProcessor');

        $expectedIncludes = array(
            realpath($linDir . '/css/input.css'),
            realpath($linDir . '/css/adjacent.css'),
            realpath($linDir . '/../css/styles.css'),
            realpath($linDir . '/css/1/tv.css'),
            realpath($linDir . '/css/1/adjacent.css'),
            realpath($linDir . '/lib/css/example.css'),
        );

        $this->assertEquals(
            $expectedIncludes,
            Minify_ImportProcessor::$filesIncluded,
            'included right files in right order'
        );
    }
}
