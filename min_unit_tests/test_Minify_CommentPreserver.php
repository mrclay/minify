<?php

require_once '_inc.php';

require_once 'Minify/CommentPreserver.php';

function test_Minify_CommentPreserver()
{
    global $thisDir;
    
    $inOut = array(
        '/*!*/' => "\n/**/\n"
        ,'/*!*/a' => "\n/**/\n1A"
        ,'a/*!*//*!*/b' => "2A\n/**/\n\n/**/\n3B"
        ,'a/*!*/b/*!*/' => "4A\n/**/\n5B\n/**/\n"
    );

    foreach ($inOut as $in => $expected) {
        $actual = Minify_CommentPreserver::process($in, '_test_MCP_processor');
        $passed = assertTrue($expected === $actual, 'Minify_CommentPreserver');
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n---Output: " .strlen($actual). " bytes\n\n{$actual}\n\n";
            if (!$passed) {
                echo "---Expected: " .strlen($expected). " bytes\n\n{$expected}\n\n\n";
            }
        }    
    }
}

function _test_MCP_processor($content, $options = array())
{
    static $callCount = 0;
    ++$callCount;
    return $callCount . strtoupper($content);
}

test_Minify_CommentPreserver();