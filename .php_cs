<?php

$rules = array(
    '@PSR2' => true,
);

$config = PhpCsFixer\Config::create();
$finder = $config->getFinder();

$finder
    ->in(array('.', 'builder/', 'lib/', 'tests/', 'min_extras/', 'static/'))
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return $config
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setIndent('    ')
    ->setLineEnding("\n");

// vim:ft=php
