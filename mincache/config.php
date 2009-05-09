<?php
/**
 * Configuration for mincache application
 * @package Minify
 */

/**
 * Directory from which files will be combined for URIs with "/f".
 * E.g. by default, the URI "/mincache/f/one,two.js" will combine
 * "/mincache/src/one.js" and "/mincache/src/two.js"
 *
 * Keep in mind, if you move CSS files into this directory, you must
 * update any relative URIs within the files accordingly.
 */
$mincache_servePath = dirname(__FILE__) . '/src';
