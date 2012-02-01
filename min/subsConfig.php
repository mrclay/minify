<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 *
 * See http://code.google.com/p/minify/wiki/CustomSource for other ideas
 **/

return array(
	'js' => array('/([^,]+)/', '$1/$1-min.js'),
	'jsraw' => array('/([^,]+)/', '$1/$1.js'),
	'css' => array('/([^,]+)/', '$1/$1-min.css'),
	'cssraw' => array('/([^,]+)/', '$1/$1.css'),
	'any' => array('/([^,]+)\\.(js|css)/', '$1/$1-min.$2'),
	'anyraw' => array('/([^,]+)\\.(js|css)/', '$1/$1.$2')
);
