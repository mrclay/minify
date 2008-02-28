<?php
require '_inc.php';

require 'Minify/Javascript.php';

$src = file_get_contents($thisDir . '/js/before.js');
$minExpected = file_get_contents($thisDir . '/js/before.min.js');;
$minOutput = Minify_Javascript::minify($src);

$passed = assertTrue($minExpected == $minOutput, 'Minify_Javascript converts before.js to before.min.js');

echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}";
if (! $passed) {
    echo "\n\n\n\n---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}";    
}
echo "\n\n---Source: " .strlen($src). " bytes\n\n{$src}";
