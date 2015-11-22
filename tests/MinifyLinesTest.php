<?php

class MinifyLinesTest extends TestCase
{
    public function test_lines()
    {
        $exp = file_get_contents(self::$test_files . "/minify/lines_output.js");

        $env = new Minify_Env(array(
            'server' => array(
                'DOCUMENT_ROOT' => dirname(__DIR__),
            ),
        ));
        $sourceFactory = new Minify_Source_Factory($env, array(), new Minify_Cache_Null());
        $controller = new Minify_Controller_Files($env, $sourceFactory);
        $minify = new Minify(new Minify_Cache_Null());

        $ret = $minify->serve($controller, array(
            'debug' => true
            ,'quiet' => true
            ,'encodeOutput' => false
            ,'files' => array(
                self::$test_files . "/js/before.js"
            )
        ));

        $this->assertEquals($exp, $ret['content']);
    }
}
