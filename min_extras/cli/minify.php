#!/usr/bin/env php
<?php

$pathToLib = dirname(dirname(__DIR__)) . '/min/lib';

require "$pathToLib/Minify/Loader.php";
Minify_Loader::register();

$cli = new MrClay\Cli;

$cli->addOptionalArg('d')->assertDir()->setDescription('Your webserver\'s DOCUMENT_ROOT: Relative paths will be rewritten relative to this path. This is required if you\'re passing in CSS.');

$cli->addOptionalArg('o')->useAsOutfile()->setDescription('Outfile: If given, output will be placed in this file.');

$cli->addOptionalArg('t')->mustHaveValue()->setDescription('Type: must be "css", "js", or "html". This must be provided if passing content via STDIN.');

if (! $cli->validate()) {
    if ($cli->isHelpRequest) {
        echo "The Minify CLI tool!\n\n";
    }
    echo "USAGE: ./minify.php [-t TYPE] [-d DOC_ROOT] [-o OUTFILE] file ...\n";
    if ($cli->isHelpRequest) {
        echo $cli->getArgumentsListing();
    }
    echo "EXAMPLE: ./minify.php ../../min_unit_tests/_test_files/js/*.js\n";
    echo "EXAMPLE: ./minify.php -d../.. ../../min_unit_tests/_test_files/css/*.css\n";
    echo "EXAMPLE: echo \"var js = 'Awesome' && /cool/;\" | ./minify.php -t js\n";
    echo "EXAMPLE: echo \"sel > ector { prop: 'value  '; }\" | ./minify.php -t css\n";
    echo "\n";
    exit(0);
}

$outfile = $cli->values['o'];
$docRoot = $cli->values['d'];
$type = $cli->values['t'];

if (is_string($type)) {
    if (! in_array($type, array('js', 'css', 'html'))) {
        echo "Type argument invalid\n";
        exit(1);
    }
    $type = constant('Minify::TYPE_' . strtoupper($type));
}

$paths = $cli->getPathArgs();
$sources = array();

if ($paths) {
    foreach ($paths as $path) {
        if (is_file($path)) {
            $sources[] = new Minify_Source(array(
                'filepath' => $path,
                'minifyOptions' => array('docRoot' => $docRoot),
            ));
        } else {
            $sources[] = new Minify_Source(array(
                'id' => $path,
                'content' => "/*** $path not found ***/\n",
                'minifier' => '',
            ));
        }
    }
} else {
    // not paths input, expect STDIN
    if (! $type) {
        echo "Type must be specified to use STDIN\n";
        exit(1);
    }
    $in = $cli->openInput();
    $sources[] = new Minify_Source(array(
        'id' => 'one',
        'content' => stream_get_contents($in),
        'contentType' => $type,
    ));
    $cli->closeInput();
}

$combined = Minify::combine($sources) . "\n";

$fp = $cli->openOutput();
fwrite($fp, $combined);
$cli->closeOutput();
