<?php

/**
 * Detect whether request should be debugged
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_DebugDetector
{
    public static function shouldDebugRequest(Minify_Env $env)
    {
        if ($env->get('debug') !== null) {
            return true;
        }

        $cookieValue = $env->cookie('minifyDebug');
        if ($cookieValue) {
            foreach (preg_split('/\\s+/', $cookieValue) as $debugUri) {
                $pattern = '@' . preg_quote($debugUri, '@') . '@i';
                $pattern = str_replace(array('\\*', '\\?'), array('.*', '.'), $pattern);
                if (preg_match($pattern, $env->getRequestUri())) {
                    return true;
                }
            }
        }

        return false;
    }
}
