<?php
/**
 * Class Minify\JS\JShrink
 *
 * @package Minify
 */

namespace Minify\JS;

/**
 * Wrapper to Javascript Minifier built in PHP http://www.tedivm.com
 *
 * @package Minify
 * @author  Elan Ruusamäe <glen@pld-linux.org>
 * @link    https://github.com/tedious/JShrink
 *
 */
class JShrink
{
    /**
     * Contains the default options for minification. This array is merged with
     * the one passed in by the user to create the request specific set of
     * options (stored in the $options attribute).
     *
     * @var string[]
     */
    protected static $defaultOptions = array('flaggedComments' => true);

    /**
     * Takes a string containing javascript and removes unneeded characters in
     * order to shrink the code without altering it's functionality.
     *
     * @param  string $js      The raw javascript to be minified
     * @param  array  $options Various runtime options in an associative array
     *
     * @see JShrink\Minifier::minify()
     * @return string
     */
    public static function minify($js, array $options = array())
    {
        $options = array_merge(
            self::$defaultOptions,
            $options
        );

        return \JShrink\Minifier::minify($js, $options);
    }
}
