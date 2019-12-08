<?php
/**
 * Sets up MinApp controller and serves files
 *
 * DO NOT EDIT! Configure this utility via config.php and groupsConfig.php
 */
$app = (require __DIR__ . '/bootstrap.php');
/* @var \Minify\App $app */

$app->runServer();
