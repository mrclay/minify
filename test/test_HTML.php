<?php
require '_inc.php';

require_once $thisDir . '/../lib/Minify/HTML.php';
require_once $thisDir . '/../lib/Minify/CSS.php';
require_once $thisDir . '/../lib/Minify/Javascript.php';

$src = file_get_contents($thisDir . '/html/before.html');
$minExpected = file_get_contents($thisDir . '/html/before.min.html');

$minOutput = Minify_HTML::minify($src, array(
    'cssMinifier' => array('Minify_CSS', 'minify')
    ,'jsMinifier' => array('Minify_Javascript', 'minify')
));

$passed = assertTrue($minExpected === $minOutput, 'Minify_HTML');

echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}";
if (! $passed) {
    echo "\n\n\n\n---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}";    
}
echo "\n\n---Source: " .strlen($src). " bytes\n\n{$src}";
