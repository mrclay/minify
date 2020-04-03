<?php

namespace Minify\Test;

use Minify_HTML_Helper;

class ScssSourceTest extends TestCase
{
    public function setUp()
    {
        $this->realDocRoot = $_SERVER['DOCUMENT_ROOT'];
        $_SERVER['DOCUMENT_ROOT'] = self::$document_root;
    }

    /**
     * @link https://github.com/mrclay/minify/issues/500
     */
    public function testTimestamp()
    {
        $baseDir = self::$test_files;

        $mainLess = "$baseDir/main.scss";
        $includedLess = "$baseDir/_included.scss";

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
        $res = Minify_HTML_Helper::getUri('scss', $options);

        $this->assertEquals("/min/g=scss&amp;{$max}", $res);
    }
}
