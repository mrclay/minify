<?php
require_once '_inc.php';

require_once 'MrClay/Pattern.php';
require_once 'MrClay/Matcher.php';

function test_MrClay_Matcher()
{
    global $thisDir;
   
    $tests = array(
        array(
            'pattern' => '/cat/',
            'numGroups' => 0,
            'input' => 'one cat two cats in the yard',
            'replacement' => 'dog',
        ),
        array(
            'pattern' => '/([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3/',
            'numGroups' => 3,
            'input' => 'Hello World! aa66ff fefe 44ee6677 gg',
            'replacement' => '######',
        ),
        array(
            'pattern' => '/^([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3/',
            'numGroups' => 3,
            'input' => 'Hello World! aa66ff fefe 44ee6677 gg',
            'replacement' => '######',
        ),
        array(
            'pattern' => '/([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3/',
            'numGroups' => 3,
            'input' => 'Hello World! aa66ff fefe 44ee6677 gg',
            'replacement' => '$1$1$2$2$3$3',
        ),
    );
    foreach ($tests as $test) {
        $pattern = new MrClay_Pattern($test['pattern'], $test['numGroups']);
        $matcher = $pattern->matcher($test['input']);
        var_export($matcher->matches()); echo "\n";
        while ($matcher->find()) {
            var_export($matcher->group());
        }
        $matcher->reset(); echo "\n";
        $sb = '';
        while ($matcher->find()) {
            $matcher->appendReplacement($sb, $test['replacement']);
        }
        $matcher->appendTail($sb);
        var_export($sb);
        echo "\n\n";
    }
}

test_MrClay_Matcher();
