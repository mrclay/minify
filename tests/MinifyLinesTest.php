<?php

namespace Minify\Test;

use Minify;
use Minify_Cache_Null;
use Minify_Controller_Files;
use Minify_Env;
use Minify_Source_Factory;

class MinifyLinesTest extends TestCase
{
    public function test_lines()
    {
        $env = new Minify_Env(array(
            'server' => array(
                'DOCUMENT_ROOT' => dirname(__DIR__),
            ),
        ));
        $sourceFactory = new Minify_Source_Factory($env, array(), new Minify_Cache_Null());
        $controller = new Minify_Controller_Files($env, $sourceFactory);
        $minify = new Minify(new Minify_Cache_Null());

        $files = glob(self::$test_files . "/lines/*.in.js");

        // uncomment to debug one
        //$files = array(self::$test_files . "/lines/basic.in.js");

        foreach ($files as $file) {
            $ret = $minify->serve($controller, array(
                'debug' => true,
                'quiet' => true,
                'encodeOutput' => false,
                'files' => array($file),
            ));

            $outFile = str_replace('.in.js', '.out.js', $file);

            $exp = file_get_contents($outFile);

            // uncomment to set up expected output
            //file_put_contents($outFile, $ret['content']);

            $this->assertEquals($exp, $ret['content'], "Did not match: " . basename($outFile));
        }
    }
}
