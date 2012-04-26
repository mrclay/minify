<?php

/*!
 * cssmin.php rev 91c5ea5
 * Author: Tubal Martin - http://blog.margenn.com/
 * Repo: https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port
 *
 * This is a PHP port of the Javascript port of the CSS minification tool
 * distributed with YUICompressor, itself a port of the cssmin utility by
 * Isaac Schlueter - http://foohack.com/
 * Permission is hereby granted to use the PHP version under the same
 * conditions as the YUICompressor.
 */

/*!
 * YUI Compressor
 * http://developer.yahoo.com/yui/compressor/
 * Author: Julien Lecomte - http://www.julienlecomte.net/
 * Copyright (c) 2011 Yahoo! Inc. All rights reserved.
 * The copyrights embodied in the content of this file are licensed
 * by Yahoo! Inc. under the BSD (revised) open source license.
 */

class CSSmin
{
    private $comments;
    private $preserved_tokens;

    /**
     * @param bool $raisePhpSettingsLimits if true, raisePhpSettingLimits() will
     * be called.
     */
    public function __construct($raisePhpSettingsLimits = true)
    {
        if ($raisePhpSettingsLimits) {
            $this->raisePhpSettingLimits();
        }
    }

    /**
     * Minify a string of CSS
     * @param string $css
     * @param int|bool $linebreak_pos
     * @return string
     */
    public function run($css, $linebreak_pos = FALSE)
    {
        $this->comments = array();
        $this->preserved_tokens = array();

        $start_index = 0;
        $length = strlen($css);

        $css = $this->extract_data_urls($css);

        // collect all comment blocks...
        while (($start_index = $this->index_of($css, '/*', $start_index)) >= 0) {
            $end_index = $this->index_of($css, '*/', $start_index + 2);
            if ($end_index < 0) {
                $end_index = $length;
            }
            $this->comments[] = $this->str_slice($css, $start_index + 2, $end_index);
            $css = $this->str_slice($css, 0, $start_index + 2) . '___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . (count($this->comments) - 1) . '___' . $this->str_slice($css, $end_index);
            $start_index += 2;
        }

        // preserve strings so their content doesn't get accidentally minified
        $css = preg_replace_callback('/(?:"(?:[^\\\\"]|\\\\.|\\\\)*")|'."(?:'(?:[^\\\\']|\\\\.|\\\\)*')/", array($this, 'callback_one'), $css);


        // Let's divide css code in chunks of 25.000 chars aprox.
        // Reason: PHP's PCRE functions like preg_replace have a "backtrack limit" of 100.000 chars by default (php < 5.3.7)
        // so if we're dealing with really long strings and a (sub)pattern matches a number of chars greater than
        // the backtrack limit number (i.e. /(.*)/s) PCRE functions may fail silently returning NULL and
        // $css would be empty.
        $charset = '';
        $charset_regexp = '/@charset [^;]+;/i';
        $css_chunks = array();
        $css_chunk_length = 25000; // aprox size, not exact
        $start_index = 0;
        $i = $css_chunk_length; // save initial iterations
        $l = strlen($css);


        // if the number of characters is 25000 or less, do not chunk
        if ($l <= $css_chunk_length) {
            $css_chunks[] = $css;
        } else {
            // chunk css code securely
            while ($i < $l) {
                $i += 50; // save iterations. 500 checks for a closing curly brace }
                if ($l - $start_index <= $css_chunk_length || $i >= $l) {
                    $css_chunks[] = $this->str_slice($css, $start_index);
                    break;
                }
                if ($css[$i - 1] === '}' && $i - $start_index > $css_chunk_length) {
                    // If there are two ending curly braces }} separated or not by spaces,
                    // join them in the same chunk (i.e. @media blocks)
                    $next_chunk = substr($css, $i);
                    if (preg_match('/^\s*\}/', $next_chunk)) {
                        $i = $i + $this->index_of($next_chunk, '}') + 1;
                    }

                    $css_chunks[] = $this->str_slice($css, $start_index, $i);
                    $start_index = $i;
                }
            }
        }

        // Minify each chunk
        for ($i = 0, $n = count($css_chunks); $i < $n; $i++) {
            $css_chunks[$i] = $this->minify($css_chunks[$i], $linebreak_pos);
            // If there is a @charset in a css chunk...
            if (empty($charset) && preg_match($charset_regexp, $css_chunks[$i], $matches)) {
                // delete all of them no matter the chunk
                $css_chunks[$i] = preg_replace($charset_regexp, '', $css_chunks[$i]);
                $charset = $matches[0];
            }
        }

        // Update the first chunk and put the charset to the top of the file.
        $css_chunks[0] = $charset . $css_chunks[0];

        return implode('', $css_chunks);
    }

    /**
     * Get the minimum PHP setting values suggested for CSSmin
     * @return array
     */
    public function getSuggestedPhpLimits()
    {
        return array(
            'memory_limit' => '128M',
            'pcre.backtrack_limit' => 1000 * 1000,
            'pcre.recursion_limit' =>  500 * 1000,
        );
    }

    /**
     * Configure PHP to use at least the suggested minimum settings
     *
     * @todo Move this functionality to separate class.
     */
    public function raisePhpSettingLimits()
    {
        foreach ($this->getSuggestedPhpLimits() as $key => $val) {
            $current = $this->normalizeInt(ini_get($key));
            $suggested = $this->normalizeInt($val);
            if ($current < $suggested) {
                ini_set($key, $val);
            }
        }
    }

    /**
     * Does bulk of the minification
     * @param string $css
     * @param int|bool $linebreak_pos
     * @return string
     */
    private function minify($css, $linebreak_pos)
    {
        // strings are safe, now wrestle the comments
        for ($i = 0, $max = count($this->comments); $i < $max; $i++) {

            $token = $this->comments[$i];
            $placeholder = '/___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . $i . '___/';

            // ! in the first position of the comment means preserve
            // so push to the preserved tokens keeping the !
            if (substr($token, 0, 1) === '!') {
                $this->preserved_tokens[] = $token;
                $css = preg_replace($placeholder, '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css, 1);
                continue;
            }

            // \ in the last position looks like hack for Mac/IE5
            // shorten that to /*\*/ and the next one to /**/
            if (substr($token, (strlen($token) - 1), 1) === '\\') {
                $this->preserved_tokens[] = '\\';
                $css = preg_replace($placeholder,  '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css, 1);
                $i = $i + 1; // attn: advancing the loop
                $this->preserved_tokens[] = '';
                $css = preg_replace('/___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . $i . '___/',  '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css, 1);
                continue;
            }

            // keep empty comments after child selectors (IE7 hack)
            // e.g. html >/**/ body
            if (strlen($token) === 0) {
                $start_index = $this->index_of($css, $this->str_slice($placeholder, 1, -1));
                if ($start_index > 2) {
                    if (substr($css, $start_index - 3, 1) === '>') {
                        $this->preserved_tokens[] = '';
                        $css = preg_replace($placeholder,  '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css, 1);
                    }
                }
            }

            // in all other cases kill the comment
            $css = preg_replace('/\/\*' . $this->str_slice($placeholder, 1, -1) . '\*\//', '', $css, 1);
        }


        // Normalize all whitespace strings to single spaces. Easier to work with that way.
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove the spaces before the things that should not have spaces before them.
        // But, be careful not to turn "p :link {...}" into "p:link{...}"
        // Swap out any pseudo-class colons with the token, and then swap back.
        $css = preg_replace_callback('/(?:^|\})(?:(?:[^\{\:])+\:)+(?:[^\{]*\{)/', array($this, 'callback_two'), $css);

        $css = preg_replace('/\s+([\!\{\}\;\:\>\+\(\)\],])/', '$1', $css);
        $css = preg_replace('/___YUICSSMIN_PSEUDOCLASSCOLON___/', ':', $css);

        // retain space for special IE6 cases
        $css = preg_replace('/\:first\-(line|letter)(\{|,)/', ':first-$1 $2', $css);

        // no space after the end of a preserved comment
        $css = preg_replace('/\*\/ /', '*/', $css);

        // Put the space back in some cases, to support stuff like
        // @media screen and (-webkit-min-device-pixel-ratio:0){
        $css = preg_replace('/\band\(/i', 'and (', $css);

        // Remove the spaces after the things that should not have spaces after them.
        $css = preg_replace('/([\!\{\}\:;\>\+\(\[,])\s+/', '$1', $css);

        // remove unnecessary semicolons
        $css = preg_replace('/;+\}/', '}', $css);

        // Replace 0(px,em,%) with 0.
        $css = preg_replace('/([\s\:])(0)(?:px|em|%|in|cm|mm|pc|pt|ex)/i', '$1$2', $css);

        // Replace 0 0 0 0; with 0.
        $css = preg_replace('/\:0 0 0 0(;|\})/', ':0$1', $css);
        $css = preg_replace('/\:0 0 0(;|\})/', ':0$1', $css);
        $css = preg_replace('/\:0 0(;|\})/', ':0$1', $css);

        // Replace background-position:0; with background-position:0 0;
        // same for transform-origin
        $css = preg_replace_callback('/(background\-position|transform\-origin|webkit\-transform\-origin|moz\-transform\-origin|o-transform\-origin|ms\-transform\-origin)\:0(;|\})/i', array($this, 'callback_three'), $css);

        // Replace 0.6 to .6, but only when preceded by : or a white-space
        $css = preg_replace('/(\:|\s)0+\.(\d+)/', '$1.$2', $css);

        // Shorten colors from rgb(51,102,153) to #336699
        // This makes it more likely that it'll get further compressed in the next step.
        $css = preg_replace_callback('/rgb\s*\(\s*([0-9,\s]+)\s*\)/i', array($this, 'callback_four'), $css);

        // Shorten colors from #AABBCC to #ABC.
        $css = $this->compress_hex_colors($css);

        // border: none -> border:0
        $css = preg_replace_callback('/(border|border\-top|border\-right|border\-bottom|border\-right|outline|background)\:none(;|\})/i', array($this, 'callback_five'), $css);

        // shorter opacity IE filter
        $css = preg_replace('/progid\:DXImageTransform\.Microsoft\.Alpha\(Opacity\=/i', 'alpha(opacity=', $css);

        // Remove empty rules.
        $css = preg_replace('/[^\};\{\/]+\{\}/', '', $css);

        // Some source control tools don't like it when files containing lines longer
        // than, say 8000 characters, are checked in. The linebreak option is used in
        // that case to split long lines after a specific column.
        if ($linebreak_pos !== FALSE && (int) $linebreak_pos >= 0) {
            $linebreak_pos = (int) $linebreak_pos;
            $start_index = $i = 0;
            while ($i < strlen($css)) {
                $i++;
                if ($css[$i - 1] === '}' && $i - $start_index > $linebreak_pos) {
                    $css = $this->str_slice($css, 0, $i) . "\n" . $this->str_slice($css, $i);
                    $start_index = $i;
                }
            }
        }

        // Replace multiple semi-colons in a row by a single one
        // See SF bug #1980989
        $css = preg_replace('/;;+/', ';', $css);

        // restore preserved comments and strings
        for ($i = 0, $max = count($this->preserved_tokens); $i < $max; $i++) {
            $css = preg_replace('/___YUICSSMIN_PRESERVED_TOKEN_' . $i . '___/', $this->preserved_tokens[$i], $css, 1);
        }

        // Trim the final string (for any leading or trailing white spaces)
        $css = preg_replace('/^\s+|\s+$/', '', $css);

        return $css;
    }

    /**
     * Utility method to replace all data urls with tokens before we start
     * compressing, to avoid performance issues running some of the subsequent
     * regexes against large strings chunks.
     *
     * @param string $css
     * @return string
     */
    private function extract_data_urls($css)
    {
        // Leave data urls alone to increase parse performance.
        $max_index = strlen($css) - 1;
        $append_index = $index = $last_index = $offset = 0;
        $sb = array();
        $pattern = '/url\(\s*(["\']?)data\:/';

        // Since we need to account for non-base64 data urls, we need to handle
        // ' and ) being part of the data string. Hence switching to indexOf,
        // to determine whether or not we have matching string terminators and
        // handling sb appends directly, instead of using matcher.append* methods.

        while (preg_match($pattern, $css, $m, 0, $offset)) {
            $index = $this->index_of($css, $m[0], $offset);
            $last_index = $index + strlen($m[0]);
            $start_index = $index + 4; // "url(".length()
            $end_index = $last_index - 1;
            $terminator = $m[1]; // ', " or empty (not quoted)
            $found_terminator = FALSE;

            if (strlen($terminator) === 0) {
                $terminator = ')';
            }

            while ($found_terminator === FALSE && $end_index+1 <= $max_index) {
                $end_index = $this->index_of($css, $terminator, $end_index + 1);

                // endIndex == 0 doesn't really apply here
                if ($end_index > 0 && substr($css, $end_index - 1, 1) !== '\\') {
                    $found_terminator = TRUE;
                    if (')' != $terminator) {
                        $end_index = $this->index_of($css, ')', $end_index);
                    }
                }
            }

            // Enough searching, start moving stuff over to the buffer
            $sb[] = $this->substring($css, $append_index, $index);

            if ($found_terminator) {
                $token = $this->substring($css, $start_index, $end_index);
                $token = preg_replace('/\s+/', '', $token);
                $this->preserved_tokens[] = $token;

                $preserver = 'url(___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___)';
                $sb[] = $preserver;

                $append_index = $end_index + 1;
            } else {
                // No end terminator found, re-add the whole match. Should we throw/warn here?
                $sb[] = $this->substring($css, $index, $last_index);
                $append_index = $last_index;
            }

            $offset = $last_index;
        }

        $sb[] = $this->substring($css, $append_index);

        return implode('', $sb);
    }

    /**
     * Utility method to compress hex color values of the form #AABBCC to #ABC.
     *
     * DOES NOT compress CSS ID selectors which match the above pattern (which would break things).
     * e.g. #AddressForm { ... }
     *
     * DOES NOT compress IE filters, which have hex color values (which would break things).
     * e.g. filter: chroma(color="#FFFFFF");
     *
     * DOES NOT compress invalid hex values.
     * e.g. background-color: #aabbccdd
     *
     * @param string $css
     * @return string
     */
    private function compress_hex_colors($css)
    {
        // Look for hex colors inside { ... } (to avoid IDs) and which don't have a =, or a " in front of them (to avoid filters)
        $pattern = '/(\=\s*?["\']?)?#([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])(\}|[^0-9a-f{][^{]*?\})/i';
        $_index = $index = $last_index = $offset = 0;
        $sb = array();

        while (preg_match($pattern, $css, $m, 0, $offset)) {
            $index = $this->index_of($css, $m[0], $offset);
            $last_index = $index + strlen($m[0]);
            $is_filter = (bool) $m[1];

            $sb[] = $this->substring($css, $_index, $index);

            if ($is_filter) {
                // Restore, maintain case, otherwise filter will break
                $sb[] = $m[1] . '#' . $m[2] . $m[3] . $m[4] . $m[5] . $m[6] . $m[7];
            } else {
                if (strtolower($m[2]) == strtolower($m[3]) &&
                    strtolower($m[4]) == strtolower($m[5]) &&
                    strtolower($m[6]) == strtolower($m[7])) {
                    // Compress.
                    $sb[] = '#' . strtolower($m[3] . $m[5] . $m[7]);
                } else {
                    // Non compressible color, restore but lower case.
                    $sb[] = '#' . strtolower($m[2] . $m[3] . $m[4] . $m[5] . $m[6] . $m[7]);
                }
            }

            $_index = $offset = $last_index - strlen($m[8]);
        }

        $sb[] = $this->substring($css, $_index);

        return implode('', $sb);
    }

    /* CALLBACKS
     * ---------------------------------------------------------------------------------------------
     */

    private function callback_one($matches)
    {
        $match = $matches[0];
        $quote = substr($match, 0, 1);
        // Must use addcslashes in PHP to avoid parsing of backslashes
        $match = addcslashes($this->str_slice($match, 1, -1), '\\');

        // maybe the string contains a comment-like substring?
        // one, maybe more? put'em back then
        if (($pos = $this->index_of($match, '___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_')) >= 0) {
            for ($i = 0, $max = count($this->comments); $i < $max; $i++) {
                $match = preg_replace('/___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . $i . '___/', $this->comments[$i], $match, 1);
            }
        }

        // minify alpha opacity in filter strings
        $match = preg_replace('/progid\:DXImageTransform\.Microsoft\.Alpha\(Opacity\=/i', 'alpha(opacity=', $match);

        $this->preserved_tokens[] = $match;
        return $quote . '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___' . $quote;
    }

    private function callback_two($matches)
    {
        return preg_replace('/\:/', '___YUICSSMIN_PSEUDOCLASSCOLON___', $matches[0]);
    }

    private function callback_three($matches)
    {
        return strtolower($matches[1]) . ':0 0' . $matches[2];
    }

    private function callback_four($matches)
    {
        $rgbcolors = explode(',', $matches[1]);
        for ($i = 0; $i < count($rgbcolors); $i++) {
            $rgbcolors[$i] = base_convert(strval(intval($rgbcolors[$i], 10)), 10, 16);
            if (strlen($rgbcolors[$i]) === 1) {
                $rgbcolors[$i] = '0' . $rgbcolors[$i];
            }
        }
        return '#' . implode('', $rgbcolors);
    }

    private function callback_five($matches)
    {
        return strtolower($matches[1]) . ':0' . $matches[2];
    }

    /* HELPERS
     * ---------------------------------------------------------------------------------------------
     */

    /**
     * PHP port of Javascript's "indexOf" function for strings only
     * Author: Tubal Martin http://blog.margenn.com
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset index (optional)
     * @return int
     */
    private function index_of($haystack, $needle, $offset = 0)
    {
        $index = strpos($haystack, $needle, $offset);

        return ($index !== FALSE) ? $index : -1;
    }

    /**
     * PHP port of Javascript's "substring" function
     * Author: Tubal Martin http://blog.margenn.com
     * Tests: http://margenn.com/tubal/substring/
     *
     * @param string   $str
     * @param int      $from index
     * @param int|bool $to index (optional)
     * @return string
     */
    private function substring($str, $from = 0, $to = FALSE)
    {
        if ($to !== FALSE) {
            if ($from == $to || ($from <= 0 && $to < 0)) {
                return '';
            }

            if ($from > $to) {
                $from_copy = $from;
                $from = $to;
                $to = $from_copy;
            }
        }

        if ($from < 0) {
            $from = 0;
        }

        $substring = ($to === FALSE) ? substr($str, $from) : substr($str, $from, $to - $from);
        return ($substring === FALSE) ? '' : $substring;
    }


    /**
     * PHP port of Javascript's "slice" function for strings only
     * Author: Tubal Martin http://blog.margenn.com
     * Tests: http://margenn.com/tubal/str_slice/
     *
     * @param string   $str
     * @param int      $start index
     * @param int|bool $end index (optional)
     * @return string
     */
    private function str_slice($str, $start = 0, $end = FALSE)
    {
        if ($end !== FALSE && ($start < 0 || $end <= 0)) {
            $max = strlen($str);

            if ($start < 0) {
                if (($start = $max + $start) < 0) {
                    return '';
                }
            }

            if ($end < 0) {
                if (($end = $max + $end) < 0) {
                    return '';
                }
            }

            if ($end <= $start) {
                return '';
            }
        }

        $slice = ($end === FALSE) ? substr($str, $start) : substr($str, $start, $end - $start);
        return ($slice === FALSE) ? '' : $slice;
    }

    /**
     * Convert strings like "64M" to int values
     * @param mixed $size
     * @return int
     */
    private function normalizeInt($size)
    {
        if (is_string($size)) {
            switch (substr($size, -1)) {
                case 'M': case 'm': return (int)$size * 1048576;
                case 'K': case 'k': return (int)$size * 1024;
                case 'G': case 'g': return (int)$size * 1073741824;
                default: return (int) $size;
            }
        }
        return (int) $size;
    }
}