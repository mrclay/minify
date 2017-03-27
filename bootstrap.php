<?php
/**
 * Sets up autoloading and returns the Minify\App
 */

call_user_func(function () {
    if (is_dir(__DIR__ . '/../../../vendor')) {
        // Used as a composer library
        $vendorDir = __DIR__ . '/../../../vendor';
    } else {
        $vendorDir = __DIR__ . '/vendor';
    }

    $file = $vendorDir . '/autoload.php';
    if (!is_file($file)) {
        echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
            'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
            'php composer.phar install'.PHP_EOL;
        exit(1);
    }

    require $file;
});

return new \Minify\App(__DIR__);
