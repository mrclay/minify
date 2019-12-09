<?php

/**
 * Compress HTML
 *
 * This is a heavy regex-based removal of whitespace, unnecessary comments and
 * tokens. IE conditional comments are preserved. There are also options to have
 * STYLE and SCRIPT blocks compressed by callback functions.
 *
 * A test suite is available.
 */
class Minify_HTML
{
    /**
     * @var string
     */
    public $_html = '';

    /**
     * @var bool
     */
    protected $_jsCleanComments = true;

    /**
     * @var bool
     */
    protected $_isXhtml;

    /**
     * @var string|null
     */
    protected $_replacementHash;

    /**
     * @var string[]
     */
    protected $_placeholders = array();

    /**
     * @var callable|null
     */
    protected $_cssMinifier;

    /**
     * @var callable|null
     */
    protected $_jsMinifier;

    /**
     * Create a minifier object
     *
     * @param string $html
     * @param array  $options
     *
     * 'cssMinifier' : (optional) callback function to process content of STYLE
     * elements.
     *
     * 'jsMinifier' : (optional) callback function to process content of SCRIPT
     * elements. Note: the type attribute is ignored.
     *
     * 'jsCleanComments' : (optional) whether to remove HTML comments beginning and end of script block
     *
     * 'xhtml' : (optional boolean) should content be treated as XHTML1.0? If
     * unset, minify will sniff for an XHTML doctype.
     */
    public function __construct($html, $options = array())
    {
        $this->_html = \str_replace("\r\n", "\n", \trim($html));

        if (isset($options['xhtml'])) {
            $this->_isXhtml = (bool) $options['xhtml'];
        }

        if (isset($options['cssMinifier'])) {
            $this->_cssMinifier = $options['cssMinifier'];
        }

        if (isset($options['jsMinifier'])) {
            $this->_jsMinifier = $options['jsMinifier'];
        }

        if (isset($options['jsCleanComments'])) {
            $this->_jsCleanComments = (bool) $options['jsCleanComments'];
        }
    }

    /**
     * "Minify" an HTML page
     *
     * @param string $html
     * @param array  $options
     *
     * 'cssMinifier' : (optional) callback function to process content of STYLE
     * elements.
     *
     * 'jsMinifier' : (optional) callback function to process content of SCRIPT
     * elements. Note: the type attribute is ignored.
     *
     * 'xhtml' : (optional boolean) should content be treated as XHTML1.0? If
     * unset, minify will sniff for an XHTML doctype.
     *
     * @return string
     */
    public static function minify($html, $options = array())
    {
        $min = new self($html, $options);

        return $min->process();
    }

    /**
     * Minify the markeup given in the constructor
     *
     * @return string
     */
    public function process()
    {
        if ($this->_isXhtml === null) {
            $this->_isXhtml = (\strpos($this->_html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML') !== false);
        }

        $this->_replacementHash = 'MINIFYHTML' . \md5($_SERVER['REQUEST_TIME']);
        $this->_placeholders = array();

        if ($this->_html === '') {
            return '';
        }

        // replace SCRIPTs (and minify) with placeholders
        if (\strpos($this->_html, '<script') !== false) {
            $this->_html = (string) \preg_replace_callback(
                '/(\\s*)<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/iu',
                array($this, '_removeScriptCB'),
                $this->_html
            );
        }

        // replace STYLEs (and minify) with placeholders
        if (\strpos($this->_html, '<style') !== false) {
            $this->_html = (string) \preg_replace_callback(
                '/\\s*<style(\\b[^>]*>)([\\s\\S]*?)<\\/style>\\s*/iu',
                array($this, '_removeStyleCB'),
                $this->_html
            );
        }

        // remove HTML comments (not containing IE conditional comments).
        if (\strpos($this->_html, '<!--') !== false) {
            $this->_html = (string) \preg_replace_callback(
                '/<!--([\\s\\S]*?)-->/u',
                array($this, '_commentCB'),
                $this->_html
            );
        }

        // replace PREs with placeholders
        if (\strpos($this->_html, '<pre') !== false) {
            $this->_html = (string) \preg_replace_callback(
                '/\\s*<pre(\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/iu',
                array(
                    $this,
                    '_removePreCB',
                ),
                $this->_html
            );
        }

        // replace TEXTAREAs with placeholders
        if (\strpos($this->_html, '<textarea') !== false) {
            $this->_html = (string) \preg_replace_callback(
                '/\\s*<textarea(\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/iu',
                array($this, '_removeTextareaCB'),
                $this->_html
            );
        }

        // trim each line
        $this->_html = (string) \preg_replace('/^\\s+|\\s+$/mu', '', $this->_html);

        // remove ws around block/undisplayed elements
        $this->_html = (string) \preg_replace(
            '/\\s+(<\\/?(?:area|article|aside|base(?:font)?|blockquote|body'
            . '|canvas|caption|center|col(?:group)?|dd|dir|div|dl|dt|fieldset|figcaption|figure|footer|form'
            . '|frame(?:set)?|h[1-6]|head|header|hgroup|hr|html|legend|li|link|main|map|menu|meta|nav'
            . '|ol|opt(?:group|ion)|output|p|param|section|t(?:able|body|head|d|h|r|foot|itle)'
            . '|ul|video)\\b[^>]*>)/iu',
            '$1',
            $this->_html
        );

        // remove ws outside of all elements
        $this->_html = (string) \preg_replace(
            '/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</u',
            '>$1$2$3<',
            $this->_html
        );

        // use newlines before 1st attribute in open tags (to limit line lengths)
        $this->_html = (string) \preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/iu', "$1\n$2", $this->_html);

        // fill placeholders
        $this->_html = \str_replace(
            \array_keys($this->_placeholders),
            $this->_placeholders,
            $this->_html
        );
        // issue 229: multi-pass to catch scripts that didn't get replaced in textareas
        $this->_html = \str_replace(
            \array_keys($this->_placeholders),
            $this->_placeholders,
            $this->_html
        );

        return $this->_html;
    }

    /**
     * @param string[] $m
     *
     * @return string
     */
    protected function _commentCB($m)
    {
        return (\strpos($m[1], '[') === 0 || \strpos($m[1], '<![') !== false)
            ? $m[0]
            : '';
    }

    /**
     * @param string $str
     *
     * @return bool
     */
    protected function _needsCdata($str)
    {
        return $this->_isXhtml
               &&
               (
                   \strpos($str, '<') !== false
                   ||
                   \strpos($str, '&') !== false
                   ||
                   \strpos($str, '--') !== false
                   ||
                   \strpos($str, ']]>') !== false
               );
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function _removeCdata($str)
    {
        return (\strpos($str, '<![CDATA[') !== false)
            ? \str_replace(array('<![CDATA[', ']]>'), '', $str)
            : $str;
    }

    /**
     * @param string[] $m
     *
     * @return string
     */
    protected function _removePreCB($m)
    {
        return $this->_reservePlace("<pre{$m[1]}");
    }

    /**
     * @param string[] $m
     *
     * @return string
     */
    protected function _removeScriptCB($m)
    {
        $openScript = "<script{$m[2]}";
        $js = $m[3];

        // whitespace surrounding? preserve at least one space
        $ws1 = ($m[1] === '') ? '' : ' ';
        $ws2 = ($m[4] === '') ? '' : ' ';

        // remove HTML comments (and ending "//" if present)
        if ($this->_jsCleanComments) {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if (
                \strpos($this->_html, '<!--') !== false
                ||
                \strpos($this->_html, '-->') !== false
            ) {
                $js = (string) \preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/u', '', $js);
            }
        }

        // remove CDATA section markers
        $js = $this->_removeCdata($js);

        // minify
        $minifier = $this->_jsMinifier ?: 'trim';
        $js = \call_user_func($minifier, $js);

        return $this->_reservePlace(
            $this->_needsCdata($js)
                ? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}"
                : "{$ws1}{$openScript}{$js}</script>{$ws2}"
        );
    }

    /**
     * @param string[] $m
     *
     * @return string
     */
    protected function _removeStyleCB($m)
    {
        $openStyle = "<style{$m[1]}";
        $css = $m[2];
        // remove HTML comments
        if (
            \strpos($css, '<!--') !== false
            ||
            \strpos($css, '-->') !== false
        ) {
            $css = (string)\preg_replace('/(?:^\\s*<!--|-->\\s*$)/u', '', $css);
        }

        // remove CDATA section markers
        $css = $this->_removeCdata($css);

        // minify
        $minifier = $this->_cssMinifier ?: 'trim';
        $css = \call_user_func($minifier, $css);

        return $this->_reservePlace(
            $this->_needsCdata($css)
                ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
                : "{$openStyle}{$css}</style>"
        );
    }

    /**
     * @param string[] $m
     *
     * @return string
     */
    protected function _removeTextareaCB($m)
    {
        return $this->_reservePlace("<textarea{$m[1]}");
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function _reservePlace($content)
    {
        $placeholder = '%' . $this->_replacementHash . \count($this->_placeholders) . '%';
        $this->_placeholders[$placeholder] = $content;

        return $placeholder;
    }
}
