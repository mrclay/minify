<?php
/**
 * String Subs configuration for default Minify implementation
 * @package Minify
 */

/** 
 * Allows string substitutions in combination requests, significantly
 * shortening URLs.
 **/
return array(
    'js' => array('/([^,\\/]+)/', '$1/$1-min.js'),
    'jsraw' => array('/([^,\\/]+)/', '$1/$1.js'),
    'css' => array('/([^,\\/]+)/', '$1/$1-min.css'),
    'cssraw' => array('/([^,\\/]+)/', '$1/$1.css'),
    'any' => array('/([^,\\/]+)\\.(js|css)/', '$1/$1-min.$2'),
    'anyraw' => array('/([^,\\/]+)\\.(js|css)/', '$1/$1.$2')
);
