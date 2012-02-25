#!/usr/bin/php
<?php

$pathToLib = dirname(dirname(__DIR__)) . '/min/lib';

// needed because of dumb require statements in class files :(
set_include_path($pathToLib . PATH_SEPARATOR . get_include_path());

// barebones autoloader
spl_autoload_register(function ($class) use ($pathToLib) {
    $file = $pathToLib . '/' . str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $class) . '.php';
    if (is_file($file)) {
        require $file;
        return true;
    }
    return false;
});

$cli = new MrClay\Cli;
$cli->addRequiredArg('d')->assertDir();
$cli->addOptionalArg('o')->useAsOutfile();
$cli->addOptionalArg('v');

if (! $cli->validate()) {
    echo "USAGE: ./rewrite-uris.php -d DOC_ROOT [-o OUTFILE [-v]] file ...\n";
    echo "EXAMPLE: ./rewrite-uris.php -v -d../.. ../../min_unit_tests/_test_files/css/paths_rewrite.css ../../min_unit_tests/_test_files/css/comments.css
    \n";
    exit(0);
}

$outfile = $cli->values['o'];
$verbose = $cli->values['v'];
$docRoot = $cli->values['d'];

$pathRewriter = function($css, $options) {
    return Minify_CSS_UriRewriter::rewrite($css, $options['currentDir'], $options['docRoot']);
};

$fp = $cli->openOutput();

$paths = $cli->getPathArgs();
$sources = array();
foreach ($paths as $path) {
    $sources[] = new Minify_Source(array(
        'filepath' => $path,
        'minifier' => $pathRewriter,
        'minifyOptions' => array('docRoot' => $docRoot),
    ));
}
fwrite($fp, Minify::combine($sources) . "\n");

if ($outfile && $verbose) {
    echo Minify_CSS_UriRewriter::$debugText . "\n";
}

$cli->closeOutput();
