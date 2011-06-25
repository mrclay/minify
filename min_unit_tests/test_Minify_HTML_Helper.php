<?php
require_once '_inc.php';

require_once 'Minify/HTML/Helper.php';

function test_Minify_HTML_Helper()
{
    global $thisDir;
    
    $realDocRoot = $_SERVER['DOCUMENT_ROOT'];
    $_SERVER['DOCUMENT_ROOT'] = $thisDir;

    $file1 = $thisDir . '/_test_files/css/paths_prepend.css';
    $file2 = $thisDir . '/_test_files/css/styles.css';
    $maxTime = max(filemtime($file1), filemtime($file2));

    $uri1 = '//_test_files/css/paths_prepend.css';
    $uri2 = '//_test_files/css/styles.css';

    $expected = "/min/b=_test_files/css&amp;f=paths_prepend.css,styles.css&amp;{$maxTime}";
    $actual = Minify_HTML_Helper::getUri(array($uri1, $uri2));
    $passed = assertTrue($actual === $expected, 'Minify_HTML_Helper : given URIs');

    $expected = "/min/b=_test_files/css&amp;f=paths_prepend.css,styles.css&amp;{$maxTime}";
    $actual = Minify_HTML_Helper::getUri(array($file1, $file2));
    $passed = assertTrue($actual === $expected, 'Minify_HTML_Helper : given filepaths');

    $expected = "/min/g=notRealGroup&amp;debug";
    $actual = Minify_HTML_Helper::getUri('notRealGroup', array('debug' => true));
    $passed = assertTrue($actual === $expected, 'Minify_HTML_Helper : non-existent group & debug');

    $expected = "/myApp/min/?g=css&amp;{$maxTime}";
    $actual = Minify_HTML_Helper::getUri('css', array(
        'rewriteWorks' => false
        ,'minAppUri' => '/myApp/min/'
        ,'groupsConfigFile' => $thisDir . '/_test_files/htmlHelper_groupsConfig.php'
    ));
    $passed = assertTrue($actual === $expected, 'Minify_HTML_Helper : existing group');

    $utilsFile = dirname(dirname(__FILE__)) . '/min/utils.php';
    if (is_file($utilsFile)) {
        require_once $utilsFile;

        $fiveSecondsAgo = $_SERVER['REQUEST_TIME'] - 5;
        $obj = new stdClass();
        $obj->lastModified = $fiveSecondsAgo;

        $output = Minify_mtime(array(
            $uri1
            ,$uri2
            ,$obj
        ));
        $passed = assertTrue($output === $fiveSecondsAgo, 'utils.php : Minify_mtime w/ files & obj');

        $obj = new stdClass();
        $obj->lastModified = strtotime('2000-01-01');
        $output = Minify_mtime(array(
            $obj
            ,'css'
        ), $thisDir . '/_test_files/htmlHelper_groupsConfig.php');
        $passed = assertTrue($output === $maxTime, 'utils.php : Minify_mtime w/ obj & group');

    }

    $_SERVER['DOCUMENT_ROOT'] = $realDocRoot;
}

test_Minify_HTML_Helper();