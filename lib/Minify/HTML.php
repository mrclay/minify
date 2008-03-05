<?php
/**
 * Class Minify_HTML  
 * @package Minify
 */

/**
 * Compress HTML
 *
 * This is a heavy regex-based removal of whitespace, unnecessary
 * comments and tokens.
 * 
 * IE conditional comments are preserved. There are also options to have STYLE
 * and SCRIPT blocks compressed by callback functions. A test suite is available.
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_HTML {
    
    /**
     * "Minify" an HTML page
     *
     * @todo: To also minify embedded Javascript/CSS, you must...
     * 
     */
    public static function minify($string, $options = array()) {
        
        if (isset($options['cssMinifier'])) {
            self::$_cssMinifier = $options['cssMinifier'];
        }
        if (isset($options['jsMinifier'])) {
            self::$_jsMinifier = $options['jsMinifier'];
        }
        
        $html = trim($string);
        
        self::$_isXhtml = (false !== strpos($html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));
        
        self::$_replacementHash = 'MINIFYHTML' . md5(time());
        
        // remove SCRIPTs (and minify)
        $html = preg_replace_callback('/\\s*(<script\\b[^>]*?>)([\\s\\S]*?)<\\/script>\\s*/i',
            array('Minify_HTML', '_removeScriptCB'), $html);
        
        // remove STYLEs (and minify)
        $html = preg_replace_callback('/\\s*(<style\\b[^>]*?>)([\\s\\S]*?)<\\/style>\\s*/i',
            array('Minify_HTML', '_removeStyleCB'), $html);
        
        // remove HTML comments (but not IE conditional comments).
        $html = preg_replace('/<!--[^\\[][\\s\\S]*?-->/', '', $html);
        
        // replace PREs with token text
        self::$_pres = array();
        $html = preg_replace_callback('/\\s*(<pre\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i'
            ,array('Minify_HTML', '_removePreCB')
            , $html);
        
        // remove leading and trailing ws from each line.
        // @todo take into account attribute values that span multiple lines.
        $html = preg_replace('/^\\s*(.*?)\\s*$/m', "$1", $html);
        
        // remove ws around block/undisplayed elements
        $html = preg_replace('/\\s*(<\\/?(?:area|base(?:font)?|blockquote|body'
            .'|caption|center|cite|col(?:group)?|dd|dir|div|dl|dt|fieldset|form'
            .'|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta'
            .'|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot)|title'
            .'|ul)\\b[^>]*>)/i', '$1', $html);
        
        // remove ws between and inside elements.
        $html = preg_replace('/>\\s+(\\S[\\s\\S]*?)?</', "> $1<", $html);
        $html = preg_replace('/>(\\S[\\s\\S]*?)?\\s+</', ">$1 <", $html);
        $html = preg_replace('/>\\s+</', "> <", $html);
        
        // replace PREs
        $i = count(self::$_pres);
        while ($i > 0) {
            $rep = array_pop(self::$_pres);
            $html = str_replace(self::$_replacementHash . 'PRE' . $i, $rep, $html);
            $i--;
        }
        
        // replace SCRIPTs
        $i = count(self::$_scripts);
        while ($i > 0) {
            $rep = array_pop(self::$_scripts);
            $html = str_replace(self::$_replacementHash . 'SCRIPT' . $i, $rep, $html);
            $i--;
        }
        
        // replace STYLEs
        $i = count(self::$_styles);
        while ($i > 0) {
            $rep = array_pop(self::$_styles);
            $html = str_replace(self::$_replacementHash . 'STYLE' . $i, $rep, $html);
            $i--;
        }
        
        self::$_cssMinifier = self::$_jsMinifier = null;
        return $html;
    }

    protected static $_isXhtml = false;
    protected static $_replacementHash = null;
    protected static $_pres = array();
    protected static $_scripts = array();
    protected static $_styles = array();
    protected static $_cssMinifier = null;
    protected static $_jsMinifier = null;

    protected static function _removePreCB($m)
    {
        self::$_pres[] = $m[1];
        return self::$_replacementHash . 'PRE' . count(self::$_pres);
    }

    protected static function _removeStyleCB($m)
    {
        $openStyle = $m[1];
        $css = $m[2];
        // remove HTML comments
        $css = preg_replace('/(?:^\\s*<!--|-->\\s*$)/', '', $css);
        
        // remove CDATA section markers
        $css = self::_removeCdata($css);
        
        // minify
        $minifier = self::$_cssMinifier
            ? self::$_cssMinifier
            : 'trim';
        $css = call_user_func($minifier, $css);
        
        // store
        self::$_styles[] = self::_needsCdata($css)
            ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
            : "{$openStyle}{$css}</style>"; 
        
        
        return self::$_replacementHash . 'STYLE' . count(self::$_styles);
    }

    protected static function _removeScriptCB($m)
    {
        $openScript = $m[1];
        $js = $m[2];
        
        // remove HTML comments (and ending "//" if present)
        $js = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js);
            
        // remove CDATA section markers
        $js = self::_removeCdata($js);
        
        // minify
        $minifier = self::$_jsMinifier
            ? self::$_jsMinifier
            : 'trim'; 
        $js = call_user_func($minifier, $js);
        
        // store
        self::$_scripts[] = self::_needsCdata($js)
            ? "{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>"
            : "{$openScript}{$js}</script>";
        return self::$_replacementHash . 'SCRIPT' . count(self::$_scripts);
    }

    protected static function _removeCdata($str)
    {
        return (false !== strpos($str, '<![CDATA['))
            ? str_replace(array('<![CDATA[', ']]>'), '', $str)
            : $str;
    }
    
    protected static function _needsCdata($str)
    {
        return (self::$_isXhtml && preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str));
    }
}

