<?php

/**
 * Detect whether request should be debugged
 */
class Minify_DebugDetector
{
    /**
     * @param Minify_Env $env
     *
     * @return bool
     */
    public static function shouldDebugRequest(Minify_Env $env)
    {
        if ($env->get('debug') !== null) {
            return true;
        }

        $cookieValue = $env->cookie('minifyDebug');
        if ($cookieValue) {
            $cookieValueTmp = \preg_split('/\\s+/', $cookieValue);
            if ($cookieValueTmp !== false) {
                foreach ($cookieValueTmp as $debugUri) {
                    $pattern = '@' . \preg_quote($debugUri, '@') . '@i';
                    $pattern = \str_replace(array('\\*', '\\?'), array('.*', '.'), $pattern);
                    if (\preg_match($pattern, $env->getRequestUri())) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
