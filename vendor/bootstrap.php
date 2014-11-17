<?php
/**
 * Sets up autoloader for Minify
 *
 * @package Minify
 */

$includeIfExists = function($file) {
    return file_exists($file) ? include $file : false;
};

if (!$includeIfExists(__DIR__.'/autoload.php')) {
    echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL;
    exit(1);
}

unset($includeIfExists);
