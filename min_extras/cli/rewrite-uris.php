#!/usr/bin/php
<?php

die('Must be rewritten for new API');

require __DIR__ . '/../../bootstrap.php';

$cli = new MrClay\Cli;

$cli->addRequiredArg('d')->assertDir()->setDescription('Your webserver\'s DOCUMENT_ROOT: Relative paths will be rewritten relative to this path.');

$cli->addOptionalArg('o')->useAsOutfile()->setDescription('Outfile: If given, output will be placed in this file.');

$cli->addOptionalArg('t')->setDescription('Test run: Return output followed by rewriting algorithm.');

if (! $cli->validate()) {
    echo "USAGE: ./rewrite-uris.php [-t] -d DOC_ROOT [-o OUTFILE] file ...\n";
    if ($cli->isHelpRequest) {
        echo $cli->getArgumentsListing();
    }
    echo "EXAMPLE: ./rewrite-uris.php -v -d../.. ../../tests/_test_files/css/paths_rewrite.css ../../tests/_test_files/css/comments.css
    \n";
    exit(0);
}

$outfile = $cli->values['o'];
$testRun = $cli->values['t'];
$docRoot = $cli->values['d'];

$pathRewriter = function ($css, $options) {
    return Minify_CSS_UriRewriter::rewrite($css, $options['currentDir'], $options['docRoot']);
};

$paths = $cli->getPathArgs();

$sources = array();
foreach ($paths as $path) {
    if (is_file($path)) {
        $sources[] = new Minify_Source(array(
            'filepath' => $path,
            'minifier' => $pathRewriter,
            'minifyOptions' => array('docRoot' => $docRoot),
        ));
    } else {
        $sources[] = new Minify_Source(array(
            'id' => $path,
            'content' => "/*** $path not found ***/\n",
            'minifier' => 'Minify::nullMinifier',
        ));
    }
}
$combined = Minify::combine($sources) . "\n";

if ($testRun) {
    echo $combined;
    echo Minify_CSS_UriRewriter::$debugText . "\n";
} else {
    $fp = $cli->openOutput();
    fwrite($fp, $combined);
    $cli->closeOutput();
}
