<?php
/**
 * Sets up autoloader for Minify
 *
 * @package Minify
 */

call_user_func(function () {
    $file = __DIR__ . '/vendor/autoload.php';
    if (!is_file($file)) {
        echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
            'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
            'php composer.phar install'.PHP_EOL;
        exit(1);
    }
    require $file;
});
