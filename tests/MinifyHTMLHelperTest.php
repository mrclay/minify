<?php

namespace Minify\Test;

use Minify_HTML_Helper;
use Minify_Source;

class MinifyHTMLHelperTest extends TestCase
{
    private $realDocRoot;

    public function setUp()
    {
        $this->realDocRoot = $_SERVER['DOCUMENT_ROOT'];
        $_SERVER['DOCUMENT_ROOT'] = self::$document_root;
    }

    // TODO: this is probably not needed if backupGlobals is enabled?
    public function tearDown()
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->realDocRoot;
    }

    public function test1()
    {
        $file1 = self::$test_files . '/css/paths_prepend.css';
        $file2 = self::$test_files . '/css/styles.css';
        $maxTime = max(filemtime($file1), filemtime($file2));

        $uri1 = '//_test_files/css/paths_prepend.css';
        $uri2 = '//_test_files/css/styles.css';

        $expected = "/min/b=_test_files/css&amp;f=paths_prepend.css,styles.css&amp;{$maxTime}";
        $actual = Minify_HTML_Helper::getUri(array($uri1, $uri2));
        $this->assertEquals($expected, $actual, 'given URIs');

        $expected = "/min/b=_test_files/css&amp;f=paths_prepend.css,styles.css&amp;{$maxTime}";
        $actual = Minify_HTML_Helper::getUri(array($file1, $file2));
        $this->assertEquals($expected, $actual, 'given filepaths');

        $expected = "/min/g=notRealGroup&amp;debug";
        $actual = Minify_HTML_Helper::getUri('notRealGroup', array('debug' => true));
        $this->assertEquals($expected, $actual, 'non-existent group & debug');

        $expected = "/myApp/min/?g=css&amp;{$maxTime}";
        $actual = Minify_HTML_Helper::getUri('css', array(
            'rewriteWorks' => false
        ,
            'minAppUri' => '/myApp/min/'
        ,
            'groupsConfigFile' => self::$test_files . '/htmlHelper_groupsConfig.php'
        ));
        $this->assertEquals($expected, $actual, 'existing group');


        $utilsFile = dirname(__DIR__) . '/min/utils.php';
        if (is_file($utilsFile)) {
            require_once $utilsFile;

            $fiveSecondsAgo = $_SERVER['REQUEST_TIME'] - 5;
            $obj = new Minify_Source(array(
                'id' => '1',
                'content' => '1',
                'lastModified' => $fiveSecondsAgo,
            ));

            $output = Minify_mtime(array($uri1, $uri2, $obj));
            $this->assertEquals($fiveSecondsAgo, $output, 'utils.php : Minify_mtime w/ files & obj');

            $obj = new Minify_Source(array(
                'id' => '2',
                'content' => '2',
                'lastModified' => strtotime('2000-01-01'),
            ));
            $output = Minify_mtime(array(
                $obj
            ,
                'css'
            ), self::$test_files . '/htmlHelper_groupsConfig.php');
            $this->assertEquals($maxTime, $output, 'utils.php : Minify_mtime w/ obj & group');
        }
    }
}
