<?php

namespace Minify\Test;

use Minify_CommentPreserver;

class MinifyCommentPreserverTest extends TestCase
{
    public function test()
    {
        $inOut = array(
            '/*!*/' => "\n/*!*/\n",
            '/*!*/a' => "\n/*!*/\n1A",
            'a/*!*//*!*/b' => "2A\n/*!*/\n\n/*!*/\n3B",
            'a/*!*/b/*!*/' => "4A\n/*!*/\n5B\n/*!*/\n",
        );

        $processor = array(__CLASS__, '_test_MCP_processor');
        foreach ($inOut as $in => $expected) {
            $actual = Minify_CommentPreserver::process($in, $processor);
            $this->assertSame($expected, $actual, 'Minify_CommentPreserver');
        }
    }

    /**
     * @internal
     */
    public static function _test_MCP_processor($content, $options = array())
    {
        static $callCount = 0;
        ++$callCount;
        return $callCount . strtoupper($content);
    }
}
