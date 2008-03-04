<?php
require_once '_inc.php';

require_once 'Minify/CSS.php';

function test_CSS()
{
    global $thisDir;
    
    $cssPath = dirname(__FILE__) . '/_test_files/css';
    
    // build test file list
    $d = dir($cssPath);
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
        
        $src = file_get_contents($cssPath . "/{$item}.css");
        $minExpected = file_get_contents($cssPath . "/{$item}.min.css");
        $minOutput = Minify_CSS::minify($src, $options);
        $passed = assertTrue($minExpected === $minOutput, 'Minify_CSS : ' . $item);
        
        if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
            echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}\n\n";
            if (!$passed) {
                echo "---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}\n\n";
                echo "---Source: " .strlen($src). " bytes\n\n{$src}\n\n\n";    
            }
        }
    }    
}

test_CSS();
