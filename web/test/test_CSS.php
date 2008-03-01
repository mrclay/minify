<?php
require '_inc.php';

require 'Minify/CSS.php';

// build test file list
$d = dir(dirname(__FILE__) . '/css');
while (false !== ($entry = $d->read())) {
    if (preg_match('/^([\w\\-]+)\.css$/', $entry, $m)) {
        $list[] = $m[1];
    }
}
$d->close();

foreach ($list as $item) {

    $options = ($item === 'paths') 
        ? array('prependRelativePath' => '../')
        : array();
    
    $src = file_get_contents($thisDir . '/css/' . $item . '.css');
    $minExpected = file_get_contents($thisDir . '/css/' . $item . '.min.css');
    $minOutput = Minify_CSS::minify($src, $options);
    assertTrue($minExpected === $minOutput, 'Minify_CSS : ' . $item);
    
    if ($minExpected !== $minOutput) {
        echo "\n---Source\n\n{$src}";
        echo "\n\n---Expected\n\n{$minExpected}";
        echo "\n\n---Output\n\n{$minOutput}\n\n\n\n";
    }
}

