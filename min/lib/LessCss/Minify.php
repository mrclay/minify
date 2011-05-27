<?php
/**
 * Class LessCss_Minify
 * @package LessCss
 */

require_once 'Minify/CSS.php';

/**
 * Minify CSS
 *
 * This class uses Minify_CSS and lessphp to compress LESS sources
 *
 * @package LessCss
 * @author Marco Pivetta <ocramius@gmail.com>
 * @author http://marco-pivetta.com/
 */
class LessCss_Minify extends Minify_CSS {

    /**
     * Minify a LESS string
     *
     * @param string $less
     *
     * @param array $options available options:
     *
     * @inheritdoc
     *
     * @return string
     */
    public static function minify($less, $options = array())
    {
        require_once 'lessphp/lessc.inc.php';
        $lessc = new lessc();
        return parent::minify($lessc->parse($less), $options);
    }
}
