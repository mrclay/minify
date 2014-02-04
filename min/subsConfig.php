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
    'js' => array('/(^|,)([^,\\/]+)/', '$1$2/$2-min.js'),
    'jsraw' => array('/(^|,)([^,\\/]+)/', '$1$2/$2.js'),
    'css' => array('/(^|,)([^,\\/]+)/', '$1$2/$2-min.css'),
    'cssraw' => array('/(^|,)([^,\\/]+)/', '$1$2/$2.css'),
    'any' => array('/(^|,)([^,\\/]+)\\.(js|css)/', '$1$2/$2-min.$3'),
    'anyraw' => array('/(^|,)([^,\\/]+)\\.(js|css)/', '$1$2/$2.$3')
);
