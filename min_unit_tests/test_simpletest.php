<?php

require_once 'simpletest/unit_tester.php';
require_once 'simpletest/reporter.php';

class MinifyTestCase extends UnitTestCase {
    function __construct() {
        $this->UnitTestCase(preg_replace('/^Test_/', '', get_class($this)));
    }
}

class Test_Sample extends MinifyTestCase {
    function test_Hello() {
        $this->assertTrue(true, 'Hello World!');
    }
}

$test = new Grouptest('All tests');
$test->addTestCase(new Test_Sample());
$test->run(new HtmlReporter());
