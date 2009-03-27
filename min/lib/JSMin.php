<?php
/**
 * jsmin.php - PHP implementation of Douglas Crockford's JSMin.
 *
 * This is a direct port of jsmin.c to PHP with a few PHP performance tweaks and
 * modifications to preserve some comments (see below). Also, rather than using
 * stdin/stdout, JSMin::minify() accepts a string as input and returns another
 * string as output.
 * 
 * Comments containing IE conditional compilation are preserved, as are multi-line
 * comments that begin with "/*!" (for documentation purposes). In the latter case
 * newlines are inserted around the comment to enhance readability.
 *
 * PHP 5 or higher is required.
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.c, which has the following license:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * --
 *
 * @package JSMin
 * @author Ryan Grove <ryan@wonko.com> (PHP port)
 * @author Steve Clay <steve@mrclay.org> (modifications + cleanup)
 * @author Andrea Giammarchi <http://www.3site.eu> (spaceBeforeRegExp)
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @link http://code.google.com/p/jsmin-php/
 */

class JSMin {
    const ORD_LF    = 10;
    const ORD_SPACE = 32;
    
    protected $a           = "\n";
    protected $b           = '';
    protected $input       = '';
    protected $inputIndex  = 0;
    protected $inputLength = 0;
    protected $lookAhead   = null;
    protected $output      = '';
    
    /**
     * Minify Javascript
     *
     * @param string $js Javascript to be minified
     * @return string
     */
    public static function minify($js)
    {
        $jsmin = new JSMin($js);
        return $jsmin->min();
    }
    
    /**
     * Setup process
     */
    public function __construct($input)
    {
        $this->input       = str_replace("\r\n", "\n", $input);
        $this->inputLength = strlen($this->input);
    }
    
    /**
     * Perform minification, return result
     */
    public function min()
    {
        if ($this->output !== '') {
            // min already run
            return $this->output;
        }
        $this->action(3);
        
        while ($this->a !== null) {
            // determine next action
            if ($this->a === ' ') {
                $act = $this->isAlphaNum($this->b) ? 1 : 2;
            } elseif ($this->a === "\n") {
                if ($this->b === ' ') {
                    $act = 3;
                } elseif (false !== strpos('{[(+-', $this->b)) {
                    $act = 1;
                } else {
                    $act = $this->isAlphaNum($this->b) ? 1 : 2;
                }
            } else {
                if ($this->b === ' ') {
                    $act = $this->isAlphaNum($this->a) ? 1 : 3;
                } elseif ($this->b === "\n") {
                    if (false !== strpos('}])+-"\'', $this->a)) {
                        $act = 1;
                    } else {
                        $act = $this->isAlphaNum($this->a) ? 1 : 3;
                    }
                } else {
                    $act = 1;
                }
            }
            $this->action($act);
        }
        return $this->output;
    }
    
    /**
     * 1 = Output A. Copy B to A. Get the next B.
     * 2 = Copy B to A. Get the next B. (Delete A).
     * 3 = Get the next B. (Delete B).
     */
    protected function action($d)
    {
        switch ($d) {
            case 1:
                $this->output .= $this->a;
                // fallthrough
            case 2:
                $this->a = $this->b;
                if ($this->a === "'" || $this->a === '"') {
                    // string literal
                    $str = ''; // in case needed for exception
                    while (true) {
                        $this->output .= $this->a;
                        $this->a       = $this->get();
                        if ($this->a === $this->b) {
                            // end quote
                            break;
                        }
                        if (ord($this->a) <= self::ORD_LF) {
                            throw new JSMin_UnterminatedStringException('Contents: ' . $str);
                        }
                        $str .= $this->a;
                        if ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a       = $this->get();
                            $str .= $this->a;
                        }
                    }
                }
                // fallthrough
            case 3:
                $this->b = $this->next();
                if ($this->b === '/' && $this->isRegexpLiteral()) {
                    // RegExp literal
                    $this->output .= $this->a . $this->b;
                    $pattern = '/'; // in case needed for exception
                    while (true) {
                        $this->a = $this->get();
                        $pattern .= $this->a;
                        if ($this->a === '/') {
                            // end pattern
                            break; // while (true)
                        } elseif ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a       = $this->get();
                            $pattern      .= $this->a;
                        } elseif (ord($this->a) <= self::ORD_LF) {
                            throw new JSMin_UnterminatedRegExpException('Contents: '. $pattern);
                        }
                        $this->output .= $this->a;
                    }
                    $this->b = $this->next();
                }
                break; // switch ($d)
            // end case 3
        }
    }
    
    protected function isRegexpLiteral()
    {
        if (false !== strpos("\n{;(,=:[!&|?", $this->a)) {
            return true;
        }
        if (' ' === $this->a) {
            // see if preceeded by keyword
            $length = strlen($this->output);
            if ($length < 2) {
                return true;
            }
            if (preg_match('/(?:case|else|in|return|typeof)$/', $this->output, $m)) {
                if ($this->output === $m[0]) {
                    return true;
                }
                $charBeforeKeyword = substr($this->output, $length - strlen($m[0]) - 1, 1);
                if (! $this->isAlphaNum($charBeforeKeyword)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Get next char. Convert ctrl char to space.
     */
    protected function get()
    {
        $c = $this->lookAhead;
        $this->lookAhead = null;
        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = $this->input[$this->inputIndex];
                $this->inputIndex += 1;
            } else {
                return null;
            }
        }
        if ($c === "\r" || $c === "\n") {
            return "\n";
        }
        if (ord($c) < self::ORD_SPACE) {
            // control char
            return ' ';
        }
        return $c;
    }
    
    /**
     * Get next char. If is ctrl character, translate to a space or newline.
     */
    protected function peek()
    {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }
    
    /**
     * Is $c a letter, digit, underscore, dollar sign, escape, or non-ASCII?
     */
    protected function isAlphaNum($c)
    {
        return (preg_match('/^[0-9a-zA-Z_\\$\\\\]$/', $c) || ord($c) > 126);
    }
    
    protected function singleLineComment()
    {
        $comment = '';
        while (true) {
            $get = $this->get();
            $comment .= $get;
            if (ord($get) <= self::ORD_LF) {
                // EOL reached
                if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                    // conditional comment, preserve it
                    return "/{$comment}";
                }
                return $get;
            }
        }
    }
    
    protected function multipleLineComment()
    {
        $this->get();
        $comment = '';
        while (true) {
            $get = $this->get();
            if ($get === '*') {
                if ($this->peek() === '/') {
                    // end of comment reached
                    $this->get();
                    if (0 === strpos($comment, '!')) {
                        // is YUI Compressor style, keep it
                        return "\n/*" . substr($comment, 1) . "*/\n";
                    }
                    if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                        // is IE conditional, keep it
                        return "/*{$comment}*/";
                    }
                    return ' ';
                }
            } elseif ($get === null) {
                throw new JSMin_UnterminatedCommentException('Contents: ' . $comment);
            }
            $comment .= $get;
        }
    }
    
    /**
     * Get the next character, skipping over comments.
     * Some comments may be preserved.
     */
    protected function next()
    {
        $get = $this->get();
        if ($get !== '/') {
            return $get;
        }
        switch ($this->peek()) {
            case '/': return $this->singleLineComment();
            case '*': return $this->multipleLineComment();
            default: return $get;
        }
    }
}

class JSMin_UnterminatedStringException extends Exception {}
class JSMin_UnterminatedCommentException extends Exception {}
class JSMin_UnterminatedRegExpException extends Exception {}
