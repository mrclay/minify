<?php
/**
 * Reports server info useful in configuring the options $min_documentRoot, $min_symlinks,
 * and $min_serveOptions['minApp']['allowDirs'].
 *
 * Change to true to expose this info.
 */
$enabled = false;

///////////////////////

if (!$enabled) {
    die('Set $enabled to true to see server info.');
}

header('Content-Type: text/plain');

$file = __FILE__;
$tmp = sys_get_temp_dir();

echo <<<EOD
__FILE__        : $file
SCRIPT_FILENAME : {$_SERVER['SCRIPT_FILENAME']}
DOCUMENT_ROOT   : {$_SERVER['DOCUMENT_ROOT']}
SCRIPT_NAME     : {$_SERVER['SCRIPT_NAME']}
REQUEST_URI     : {$_SERVER['REQUEST_URI']}
Cache directory : $tmp
EOD;
