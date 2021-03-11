<?php

namespace Minify\Test;

use Minify_HTML_Helper;

/**
 * @requires php < 7.3
 * @see https://github.com/mrclay/minify/pull/685
 */
class LessSourceTest extends TestCase
{
    public function setUp()
    {
        $this->realDocRoot = $_SERVER['DOCUMENT_ROOT'];
        $_SERVER['DOCUMENT_ROOT'] = self::$document_root;
    }

    /**
     * @link https://github.com/mrclay/minify/issues/500
     */
    public function testLessTimestamp()
    {
        $baseDir = self::$test_files;

        $mainLess = "$baseDir/main.less";
        $includedLess = "$baseDir/included.less";

        // touch timestamp with 1s difference
        touch($mainLess);
        sleep(1);
        touch($includedLess);

        $mtime1 = filemtime($mainLess);
        $mtime2 = filemtime($includedLess);

        $max = max($mtime1, $mtime2);

        $options = array(
            'groupsConfigFile' => "$baseDir/htmlHelper_groupsConfig.php",
        );
        $res = Minify_HTML_Helper::getUri('less', $options);

        $this->assertEquals("/min/g=less&amp;{$max}", $res);
    }
}
