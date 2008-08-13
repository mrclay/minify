<?php
/**
 * Class Minify_HTML  
 * @package Minify
 */

/**
 * Compress HTML
 *
 * This is a heavy regex-based removal of whitespace, unnecessary comments and 
 * tokens. IE conditional comments are preserved. There are also options to have
 * STYLE and SCRIPT blocks compressed by callback functions. 
 * 
 * A test suite is available.
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_HTML {
    
    /**
     * "Minify" an HTML page
     *
     * @param string $html
     * @param array $options
     * @return string
     */
    public static function minify($html, $options = array()) {
        
        if (isset($options['cssMinifier'])) {
            self::$_cssMinifier = $options['cssMinifier'];
        }
        if (isset($options['jsMinifier'])) {
            self::$_jsMinifier = $options['jsMinifier'];
        }
        
        $html = trim($html);
        
        self::$_isXhtml = (false !== strpos($html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));
        
        self::$_replacementHash = 'MINIFYHTML' . md5(time());
        
        // replace SCRIPTs (and minify) with placeholders
        $html = preg_replace_callback(
            '/\\s*(<script\\b[^>]*?>)([\\s\\S]*?)<\\/script>\\s*/i'
            ,array('Minify_HTML', '_removeScriptCB')
            ,$html);
        
        // replace STYLEs (and minify) with placeholders
        $html = preg_replace_callback(
            '/\\s*(<style\\b[^>]*?>)([\\s\\S]*?)<\\/style>\\s*/i'
            ,array('Minify_HTML', '_removeStyleCB')
            ,$html);
        
        // remove HTML comments (but not IE conditional comments).
        $html = preg_replace('/<!--[^\\[][\\s\\S]*?-->/', '', $html);
        
        // replace PREs with placeholders
        $html = preg_replace_callback('/\\s*(<pre\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i'
            ,array('Minify_HTML', '_removePreCB')
            , $html);
        
        // replace TEXTAREAs with placeholders
        $html = preg_replace_callback(
            '/\\s*(<textarea\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i'
            ,array('Minify_HTML', '_removeTaCB')
            , $html);
        
        // trim each line.
        // @todo take into account attribute values that span multiple lines.
        $html = preg_replace('/^\\s+|\\s+$/m', '', $html);
        
        // remove ws around block/undisplayed elements
        $html = preg_replace('/\\s+(<\\/?(?:area|base(?:font)?|blockquote|body'
            .'|caption|center|cite|col(?:group)?|dd|dir|div|dl|dt|fieldset|form'
            .'|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta'
            .'|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)'
            .'|ul)\\b[^>]*>)/i', '$1', $html);
        
        // remove ws outside of all elements
        $html = preg_replace_callback(
            '/>([^<]+)</'
            ,array('Minify_HTML', '_outsideTagCB')
            ,$html);
        
        // fill placeholders
        self::_fillPlaceholders($html, self::$_pres, 'PRE');
        self::_fillPlaceholders($html, self::$_tas, 'TEXTAREA');
        self::_fillPlaceholders($html, self::$_scripts, 'SCRIPT');
        self::_fillPlaceholders($html, self::$_styles, 'STYLE');
        
        self::$_cssMinifier = self::$_jsMinifier = null;
        return $html;
    }
    
    protected static function _fillPlaceholders(&$html, &$placeholderArray, $id)
    {
        $i = count($placeholderArray);
        while ($i) {
            $html = str_replace(
                self::$_replacementHash . $id . $i
                ,array_pop($placeholderArray)
                ,$html);
            $i--;
        }
    }

    protected static $_isXhtml = false;
    protected static $_replacementHash = null;
    protected static $_pres = array();
    protected static $_tas = array(); // textareas
    protected static $_scripts = array();
    protected static $_styles = array();
    protected static $_cssMinifier = null;
    protected static $_jsMinifier = null;

    protected static function _outsideTagCB($m)
    {
        return '>' . preg_replace('/^\\s+|\\s+$/', ' ', $m[1]) . '<';
    }
    
    protected static function _removePreCB($m)
    {
        self::$_pres[] = $m[1];
        return self::$_replacementHash . 'PRE' . count(self::$_pres);
    }
    
    protected static function _removeTaCB($m)
    {
        self::$_tas[] = $m[1];
        return self::$_replacementHash . 'TEXTAREA' . count(self::$_tas);
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
