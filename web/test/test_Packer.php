<?php
require '_inc.php';

require 'Minify/Packer.php';

$src = file_get_contents($thisDir . '/packer/before.js');
$minExpected = file_get_contents($thisDir . '/packer/before.min.js');
$minOutput = Minify_Packer::minify($src);

$passed = assertTrue($minExpected === $minOutput, 'Minify_Packer');

echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}";
if (! $passed) {
    echo "\n\n\n\n---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}";    
}
echo "\n\n---Source: " .strlen($src). " bytes\n\n{$src}";