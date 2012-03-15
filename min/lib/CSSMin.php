<?php


/**
 * cssmin.php
 * Author: Tubal Martin - http://margenn.com/
 * This is a PHP port of the Javascript port ("cssmin.js" by Stoyan Stefanov from Yahoo!)
 * of CSS minification tool distributed with YUICompressor, itself a port 
 * of the cssmin utility by Isaac Schlueter - http://foohack.com/ 
 * Permission is hereby granted to use the PHP version under the same
 * conditions as the YUICompressor (original YUICompressor note below).
 * This port is based on the following rev:
 * https://github.com/yui/yuicompressor/blob/83b2be2d4e98834de96bf3ee268c79e61ad9afa3/ports/js/cssmin.js
 */
 
/*
 * YUI Compressor
 * Author: Julien Lecomte - http://www.julienlecomte.net/
 * Copyright (c) 2009 Yahoo! Inc. All rights reserved.
 * The copyrights embodied in the content of this file are licensed
 * by Yahoo! Inc. under the BSD (revised) open source license.
 */
 
class CSSmin
{
    
    private $comments          = array();
    private $preserved_tokens  = array();
    
    
    public function run($css, $linebreakpos = 100)
    {
        $startIndex = 0;
        $totallen = strlen($css);
        
        
        // collect all comment blocks...
        while (($startIndex = strpos($css, '/*', $startIndex)) !== FALSE && $startIndex >= 0) {
            $endIndex = strpos($css, '*/', $startIndex + 2);
            if ($endIndex === FALSE) {
                $endIndex = $totallen;
            }
            $this->comments[] = $this->str_slice($css, $startIndex + 2, $endIndex);
            $css = $this->str_slice($css, 0, $startIndex + 2) . '___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . (count($this->comments) - 1) . '___' . $this->str_slice($css, $endIndex);
            $startIndex += 2;
        }
         
        // preserve strings so their content doesn't get accidentally minified 
        $css = preg_replace_callback('/"(?:[^\\\\"]|\\\\.|\\\\)*"|\'(?:[^\\\\\']|\\\\.|\\\\)*\'/', array($this, 'callback_one'), $css);
        
        
        // Let's divide css code in chunks of 25.000 chars aprox.
        // Reason: PHP's PCRE functions like preg_replace have a "backtrack limit" of 100.000 chars by default
        // so if we're dealing with really long strings and a (sub)pattern matches a number of chars greater than
        // the backtrack limit number (i.e. /(.*)/s) PCRE functions may fail silently returning NULL and 
        // $css would be empty.
        
        $charset = '';
        $css_chunks = array();
        $css_chunk_length = 25000; // aprox size, not exact
        $startIndex = 0; 
        $i = $css_chunk_length; // save initial iterations
        $l = strlen($css);
        
        
        // if the number of characters is 25000 or less, do not chunk
        if ($l <= $css_chunk_length) {
            $css_chunks[] = $css;
        }
        else{
            // chunk css code securely
            while ($i < $l) {
                $i += 50; // save iterations. 500 checks for a closing curly brace }
                if ($l - $startIndex <= $css_chunk_length || $i >= $l) {
                    $css_chunks[] = $this->str_slice($css, $startIndex);
                    break;
                }
                if ($css[$i - 1] === '}' && $i - $startIndex > $css_chunk_length) {
                    // If there are two ending curly braces }} separated or not by spaces,
                    // join them in the same chunk (i.e. @media blocks)
                    if (preg_match('/^\s*\}/', substr($css, $i)) > 0) {
                        $i = $i + strpos(substr($css, $i), '}') + 1;
                    }
                    
                    $css_chunks[] = $this->str_slice($css, $startIndex, $i);
                    $startIndex = $i;
                }
            }
        }
        
        // Minify each chunk
        for ($i = 0, $n = count($css_chunks); $i < $n; $i++) {
            $css_chunks[$i] = $this->minify($css_chunks[$i], $linebreakpos);
            // If there is a @charset in a css chunk...
            if (preg_match('/@charset ["\'][^"\']*["\']\;/i', $css_chunks[$i], $matches) > 0) {
                // delete all of them no matter the chunk
                $css_chunks[$i] = preg_replace('/@charset ["\'][^"\']*["\']\;/i', '', $css_chunks[$i]);
                $charset = $matches[0];
            }   
        }
    
        // Update the first chunk and push the charset to the top of the file.
        $css_chunks[0] = $charset . $css_chunks[0];
        
        
        return implode('', $css_chunks);
    }
    
    
    
    
    private function minify($css, $linebreakpos)
    {
        
        // strings are safe, now wrestle the comments
        for ($i = 0, $max = count($this->comments); $i < $max; $i++) {
            
            $token = $this->comments[$i];
            $placeholder = '/___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . $i . '___/';
            
            // ! in the first position of the comment means preserve
            // so push to the preserved tokens keeping the !
            if (substr($token, 0, 1) === '!') {
                $this->preserved_tokens[] = $token;
                $css = preg_replace($placeholder, '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css);
                continue;
            }
            
            // \ in the last position looks like hack for Mac/IE5
            // shorten that to /*\*/ and the next one to /**/
            if (substr($token, (strlen($token) - 1), 1) === '\\') {
                $this->preserved_tokens[] = '\\';
                $css = preg_replace($placeholder,  '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css);
                $i = $i + 1; // attn: advancing the loop
                $this->preserved_tokens[] = '';
                $css = preg_replace('/___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . $i . '___/',  '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css);            
                continue;
            }
    
            // keep empty comments after child selectors (IE7 hack)
            // e.g. html >/**/ body
            if (strlen($token) === 0) {
                $startIndex = strpos($css, $this->str_slice($placeholder, 1, -1));
                if ($startIndex > 2) {
                    if (substr($css, $startIndex - 3, 1) === '>') {
                        $this->preserved_tokens[] = '';
                        $css = preg_replace($placeholder,  '___YUICSSMIN_PRESERVED_TOKEN_' . (count($this->preserved_tokens) - 1) . '___', $css);
                    }
                }
            }
                    
            // in all other cases kill the comment
            $css = preg_replace('/\/\*' . $this->str_slice($placeholder, 1, -1) . '\*\//', '', $css);
        }
        
        
        // Normalize all whitespace strings to single spaces. Easier to work with that way.
        $css = preg_replace('/\s+/', ' ', $css);
    
        // Remove the spaces before the things that should not have spaces before them.
        // But, be careful not to turn "p :link {...}" into "p:link{...}"
        // Swap out any pseudo-class colons with the token, and then swap back.
        $css = preg_replace_callback('/(^|\})(([^\{\:])+\:)+([^\{]*\{)/', array($this, 'callback_two'), $css);
        
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
        $css = preg_replace('/([\!\{\}\:\;\>\+\(\[,])\s+/', '$1', $css);
    
        // remove unnecessary semicolons
        $css = preg_replace('/\;+\}/', '}', $css);
    
        // Replace 0(px,em,%) with 0.
        $css = preg_replace('/([\s\:])(0)(px|em|%|in|cm|mm|pc|pt|ex)/i', '$1$2', $css);
    
        // Replace 0 0 0 0; with 0.
        $css = preg_replace('/\:0 0 0 0(\;|\})/', ':0$1', $css);
        $css = preg_replace('/\:0 0 0(\;|\})/', ':0$1', $css);
        $css = preg_replace('/\:0 0(\;|\})/', ':0$1', $css);
        
        // Replace background-position:0; with background-position:0 0;
        // same for transform-origin
        $css = preg_replace_callback('/(background\-position|transform\-origin|webkit\-transform\-origin|moz\-transform\-origin|o-transform\-origin|ms\-transform\-origin)\:0(\;|\})/i', array($this, 'callback_three'), $css);
    
        // Replace 0.6 to .6, but only when preceded by : or a white-space
        $css = preg_replace('/(\:|\s)0+\.(\d+)/', '$1.$2', $css);
    
        // Shorten colors from rgb(51,102,153) to #336699
        // This makes it more likely that it'll get further compressed in the next step.
        $css = preg_replace_callback('/rgb\s*\(\s*([0-9,\s]+)\s*\)/i', array($this, 'callback_four'), $css);

        // Shorten colors from #AABBCC to #ABC. Note that we want to make sure
        // the color is not preceded by either ", " or =. Indeed, the property
        //     filter: chroma(color="#FFFFFF");
        // would become
        //     filter: chroma(color="#FFF");
        // which makes the filter break in IE.
        $css = preg_replace_callback('/([^"\'\=\s])(\s*)#([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])/i', array($this, 'callback_five'), $css);
        
        // border: none -> border:0
        $css = preg_replace_callback('/(border|border\-top|border\-right|border\-bottom|border\-right|outline|background)\:none(\;|\})/i', array($this, 'callback_six'), $css);
        
        // shorter opacity IE filter
        $css = preg_replace('/progid\:DXImageTransform\.Microsoft\.Alpha\(Opacity\=/i', 'alpha(opacity=', $css);
    
        // Remove empty rules.
        $css = preg_replace('/[^\}\;\{\/]+\{\}/', '', $css);
        
        // Some source control tools don't like it when files containing lines longer
        // than, say 8000 characters, are checked in. The linebreak option is used in
        // that case to split long lines after a specific column.
        if ($linebreakpos >= 0) {
            $startIndex = 0; 
            $i = 0;
            while ($i < strlen($css)) {
                $i++;
                if ($css[$i - 1] === '}' && $i - $startIndex > $linebreakpos) {
                    $css = $this->str_slice($css, 0, $i) . "\n" . $this->str_slice($css, $i);
                    $startIndex = $i;
                }
            }
        }   

        // Replace multiple semi-colons in a row by a single one
        // See SF bug #1980989
        $css = preg_replace('/\;\;+/', ';', $css);
    
        // restore preserved comments and strings
        for ($i = 0, $max = count($this->preserved_tokens); $i < $max; $i++) {
            $css = preg_replace('/___YUICSSMIN_PRESERVED_TOKEN_' . $i . '___/', $this->preserved_tokens[$i], $css);
        }
        
        // Trim the final string (for any leading or trailing white spaces)
        $css = preg_replace('/^\s+|\s+$/', '', $css);
        
        
        
        return $css;
    }
    
    
    
    
    /* CALLBACKS
     * ---------------------------------------------------------------------------------------------
     */
    
    private function callback_one($matches)
    {
        $match = $matches[0];
        $quote = substr($match, 0, 1);
        $match = $this->str_slice($match, 1, -1);
            
        // maybe the string contains a comment-like substring?
        // one, maybe more? put'em back then
        if (($pos = strpos($match, '___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_')) !== FALSE && $pos >= 0) {
            for ($i = 0, $max = count($this->comments); $i < $max; $i++) {
                $match = preg_replace('/___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_' . $i . '___/', $this->comments[$i], $match);
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
        $group = $matches;
        if (
            strtolower($group[3]) === strtolower($group[4]) &&
            strtolower($group[5]) === strtolower($group[6]) &&
            strtolower($group[7]) === strtolower($group[8])
        ) {
            return strtolower($group[1] . $group[2] . '#' . $group[3] . $group[5] . $group[7]);
        } else {
            return strtolower($group[0]);
        }   
    }
    
    
    
    
    private function callback_six($matches)
    {
        return strtolower($matches[1]) . ':0' . $matches[2];
    }
    
    
    
    
    /* HELPERS
     * ---------------------------------------------------------------------------------------------
     */ 
     
    
    /** 
     * PHP port of Javascript's "slice" function
     * Author: Tubal Martin http://margenn.com
     * Tests: http://margenn.com/tubal/str_slice/
     *
     * @param string $str
     * @param int    $start index
     * @param int    $end index (optional)
     */
    private function str_slice($str, $start, $end = FALSE)
    {
        if ($start < 0 || $end <= 0) {
            
            if ($end === FALSE) {
                $slice = substr($str, $start);
                return ($slice === FALSE) ? '' : $slice;
            }
            
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
        
        $slice = substr($str, $start, $end - $start);
        return ($slice === FALSE) ? '' : $slice;
    }


}