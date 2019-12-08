<?php

namespace Minify\Test;

use Minify_Build;
use Minify_Source;

/**
 * @internal
 */
final class MinifyBuildTest extends TestCase
{
    public function test()
    {
        $file1 = self::$test_files . '/css/paths_prepend.css';
        $file2 = self::$test_files . '/css/styles.css';
        $maxTime = \max(\filemtime($file1), \filemtime($file2));

        $b = new Minify_Build($file1);
        static::assertSame($b->lastModified, \filemtime($file1), 'single file path');

        $b = new Minify_Build(array($file1, $file2));
        static::assertSame($maxTime, $b->lastModified, 'multiple file paths');

        $b = new Minify_Build(array($file1, new Minify_Source(array('filepath' => $file2))));

        static::assertSame($maxTime, $b->lastModified, 'file path and a Minify_Source');
        static::assertSame($b->uri('/path'), "/path?{$maxTime}", 'uri() with no querystring');
        static::assertSame($b->uri('/path?hello'), "/path?hello&amp;{$maxTime}", 'uri() with existing querystring');
    }
}
