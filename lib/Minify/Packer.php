<?php

/**
 * Minify Javascript using Dean Edward's Packer
 */
class Minify_Packer
{
    /**
     * @param string $code
     * @param array  $options
     *
     * @return string
     */
    public static function minify($code, $options = array())
    {
        // @TODO: set encoding options based on $options :)
        $packer = new JavaScriptPacker($code, 'Normal', true, false);

        return \trim($packer->pack());
    }
}
