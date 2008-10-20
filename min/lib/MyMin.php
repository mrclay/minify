<?php
/**
 * MyMin - JSMin like alternative parser for JavaScript
 *
 * This class is a jsmin alternative, based on same parser logic but modified
 * to mantain performances and to parse correctly JavaScript conditional comments too.
 * 
 * SERVER SIDE
 * PHP 5 or greater is required.
 * This code is compatible with every error_reporting level (E_ALL | E_STRICT)
 * The best practice to use this code is caching results without run-time
 * evaluation (your server should be stressed too much with big files)
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.php, which has the following license:
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
 * @class MyMin
 * @author	Andrea Giammarchi <http://www.3site.eu>
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2007 Ryan Grove <ryan@wonko.com> (PHP port)
 * @copyright 2007 Andrea Giammarchi (improvements + MyMinCompressor + MyMinCSS)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @version 1.0.1 (2007-10-05) - updated 2008-02-17
 */

// -- Class MyMin -------------------------------------------------------------- 
class MyMin {

	const			/* char */		LF	= "\n",
						SPACE	= ' ',
						EOS	= "\x00";

	protected		/* boolean */	$cc_on;

	protected		/* char */		$a,
						$ahead,
						$b;

	protected		/* int */		$index	= 0,
						$length;
	
	protected		/* string */	$input,
						$output = "";

	// -- Public Static Methods ----------------------------------------------------
    static	public	final	function	/* string */	parse(/* string */ $input, /* boolean */ $cc_on = true){
		return	"".(new MyMin($input, $cc_on));
	}
	
	// -- Public Instance Methods --------------------------------------------------
	public	final	function		/* object */	__construct(/* string */ $input, /* boolean */ $cc_on = true){
		$this->input = preg_replace("/(\r\n|\n\r|\r|\n)+/", self::LF, trim($input));
		$this->length = strlen($this->input);
		$this->cc_on = $cc_on;
		$this->b = $this->ahead = self::SPACE;
		$this->a = self::LF;
		$this->action(3);
		while($this->a !== self::EOS){
			switch($this->a){
				case	self::SPACE:
					$this->action($this->isAlNum($this->b) ? 1 : 2);
					break;
				case	self::LF:
					switch($this->b){
						case	'{':
						case	'[':
						case	'(':
						case	'+':
						case	'-':
							$this->action(1);
							break;
						case	self::SPACE:
							$this->action(3);
							break;
						default:
							$this->action($this->isAlNum($this->b) ? 1 : 2);
							break;
					}
					break;
				default:
					switch($this->b){
						case	self::SPACE:
							$this->action($this->isAlNum($this->a) ? 1 : 3);
							break;
						case	self::LF:
							switch($this->a){
								case	'}':
								case	']':
								case	')':
								case	'+':
								case	'-':
								case	'"':
								case	'\'':
									$this->action(1);
									break;
								default:
									$this->action($this->isAlNum($this->a) ? 1 : 3);
									break;
							}
							break;
						default:
							$this->action(1);
							break;
					}
					break;
			}
		}
	}

	public	final	function		/* string */	__toString(/* void */){
		return	str_replace("\n\n", "\n", ltrim($this->output));
	}
	
	// -- Protected Instance Methods -----------------------------------------------
	protected	function		/* void */		action(/* int */ $i){
		switch($i){
			case	1:
				$this->output .= $this->a;
			case	2:
				$this->a = $this->b;
				if($this->a === '\'' || $this->a === '"'){
					while(true){
						$this->output .= $this->a;
						if(!$this->nextCharNoSlash($this->b, "Unterminated string literal."))
							break;
					}
				}
			case	3:
				$this->b = $this->next();
				if($this->b === '/'){
					switch($this->a){
						case	self::LF:
						case	self::SPACE:
							if(!$this->spaceBeforeRegExp($this->output))
								break;
						
						case	'{':
						case	';':
					
						case	'(':
						case	',':
						case	'=':
						case	':':
						case	'[':
						case	'!':
						case	'&':
						case	'|':
						case	'?':
							$this->output .= $this->a.$this->b;
							while($this->nextCharNoSlash('/', "Unterminated regular expression literal."))
								$this->output .= $this->a;
							$this->b = $this->next();
							break;
					}
				}
				break;
		}
	}
	
	protected	function		/* void */		appendComment(/* int */ $pos, /* string */ $open, /* string */ $close) {
		$this->output .= $this->a.$open.(new MyMin(substr($this->input, $this->index, $pos - $this->index), $this->cc_on)).$close;
		$this->index = $pos;
		$this->a = self::LF;
	}

	protected	function		/* void */		conditionalComment(/* char */ $find) {
		$single = $find === self::LF;
		$pos = strpos($this->input, $find, $this->index);
		if($pos === false){
			if($single)
				$pos = $this->length;
			else
				throw new MyMinException("Unterminated comment.");
		}
		$this->appendComment($pos, $single ? "//" : "/*", $find);
	}
	
	protected	function		/* char */		get(/* void */) {
		$c = $this->ahead;
		$this->ahead = self::EOS;
		if($c === self::EOS && $this->index < $this->length)
			$c = $this->input{$this->index++};
		return	($c === self::EOS || $c === self::LF || $c >= self::SPACE) ? $c : self::SPACE;
	}
	
	protected	function		/* boolean */	isAlNum(/* char */ $c) {
		return	$c > 126 || $c === '\\' || preg_match('/^(\w|\$)$/', $c);
	}

	protected	function		/* char */		next(/* void */) {
		$c = $this->get();
		$loop = true;
		if($c === '/'){
			switch($this->ahead = $this->get()){
				case	'/':
					if($this->cc_on && $this->input{$this->index} === '@')
						$this->conditionalComment(self::LF);
					while($loop){
						$c = $this->get();
						if($c <= self::LF)
							$loop = false;
					}
					break;
				case '*':
					$this->get();
					if($this->cc_on && $this->input{$this->index} === '@')
						$this->conditionalComment("*/");
					while($loop){
						switch($this->get()){
							case	'*':
								if(($this->ahead = $this->get()) === '/'){
									$this->get();
									$c = self::SPACE;
									$loop = false;
								}
								break;
							case	self::EOS:
								throw new MyMinException("Unterminated comment.");
						}
					}
					break;
			}
		}
		return $c;
	}
	
	protected	function		/* boolean */	nextCharNoSlash(/* char */ $c, /* string */ $message) {
		$loop = true;
		$this->a = $this->get();
		if($this->a === $c)
			$loop = false;
		else{
			if($this->a === '\\'){
				$this->output .= $this->a;
				$this->a = $this->get();
			}
			if($this->a <= self::LF)
				throw new MyMinException($message);
		}
		return	$loop;
	}
	
	protected	function		/* boolean */	spaceBeforeRegExp(/* string */ $output){
		for(
			$i = 0,
			$length = strlen($output),
			$reserved = array("case", "else", "in", "return", "typeof"),
			$result = false,
			$tmp = "";
			$i < 5 && !$result;
			$i++
		){
			if($length === strlen($reserved[$i]))
				$result = $reserved[$i] === $output;
			else if($length > strlen($reserved[$i])){
				$tmp = substr($output, $length - strlen($reserved[$i]) - 1);
				$result = substr($tmp, 1) === $reserved[$i] && !$this->isAlNum($tmp{0});
			}
		};
		return	$length < 2 ? true : $result;
	}
}

// -- MyMin Exceptions ---------------------------------------------------------
class MyMinException extends Exception {}
