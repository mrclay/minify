<?php
/**
* JSMin_lib.php (for PHP 4, 5)
*
* PHP adaptation of JSMin, published by Douglas Crockford as jsmin.c, also based
* on its Java translation by John Reilly.
*
* Permission is hereby granted to use the PHP version under the same conditions
* as jsmin.c, which has the following notice :
*
* ----------------------------------------------------------------------------
*
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
*
* ----------------------------------------------------------------------------
*
* @copyright   No new copyright ; please keep above and following information.
* @author      David Holmes <dholmes@cfdsoftware.net> of CFD Labs, France
* @author      Gaetano Giunta
* @version     $Id: $
*
* Please note, this is a brutal and simple conversion : it could undoubtedly be
* improved, as a PHP implementation, by applying more PHP-specific programming
* features.
*
* Exceptions and all PHP 5 - only features ahve been removed for compat with PHP 4
*
* Note : whereas jsmin.c works specifically with the standard input and output
* streams, this implementation only falls back on them if file pathnames are
* not provided to the JSMin() constructor.
*
* Examples comparing with the application compiled from jsmin.c :
*
* jsmin < orig.js > mini.js        JSMin.php orig.js mini.js
*                                  JSMin.php orig.js > mini.js
*                                  JSMin.php - mini.js < orig.js
* jsmin < orig.js                  JSMin.php orig.js
*                                  JSMin.php orig.js -
* jsmin > mini.js                  JSMin.php - mini.js
*                                  JSMin.php > mini.js
* jsmin comm1 comm2 < a.js > b.js  JSMin.php a.js b.js comm1 comm2
*                                  JSMin.php a.js b.js -c comm1 comm2
*                                  JSMin.php a.js --comm comm1 comm2 > b.js
*                                  JSMin.php -c comm1 comm2 < a.js > b.js
* (etc...)
*
* See JSMin.php -h (or --help) for command-line documentation.
*
* NEW AND IMPROVED in version 0.2:
* to take advantage of this file in your own code, you can do the following:
*
* define('JSMIN_AS_LIB', true); // prevents auto-run on include
* include('jsmin.php');
* // the JSMin class now works on php strings, too
* $jsMin = new JSMin(file_get_contents('e:/htdocs/awstats_misc_tracker.js'), false);
* // in that case, the modifies string is returned by minify():
* $out = $jsMin->minify();
*
*/

/**
* Version of this PHP translation.
*/

define('JSMIN_VERSION', '0.2');

/**
* How fgetc() reports an End Of File.
* N.B. : use === and not == to test the result of fgetc() ! (see manual)
*/

define('EOF', FALSE);

/**
* Some ASCII character ordinals.
* N.B. : PHP identifiers are case-insensitive !
*/

define('ORD_NL', ord("\n"));
define('ORD_space', ord(' '));
define('ORD_cA', ord('A'));
define('ORD_cZ', ord('Z'));
define('ORD_a', ord('a'));
define('ORD_z', ord('z'));
define('ORD_0', ord('0'));
define('ORD_9', ord('9'));

/**
* Generic exception class related to JSMin.
*/
/*
class JSMinException extends Exception {
}
*/
class JSMinException {
}

/**
* A JSMin exception indicating that a file provided for input or output could not be properly opened.
*/

class FileOpenFailedJSMinException extends JSMinException {
}

/**
* A JSMin exception indicating that an unterminated comment was encountered in input.
*/

class UnterminatedCommentJSMinException extends JSMinException {
}

/**
* A JSMin exception indicatig that an unterminated string literal was encountered in input.
*/

class UnterminatedStringLiteralJSMinException extends JSMinException {
}

/**
* A JSMin exception indicatig that an unterminated regular expression lieteral was encountered in input.
*/

class UnterminatedRegExpLiteralJSMinException extends JSMinException {
}

/**
 * Constant describing an {@link action()} : Output A. Copy B to A. Get the next B.
 */

define ('JSMIN_ACT_FULL', 1);

/**
 * Constant describing an {@link action()} : Copy B to A. Get the next B. (Delete A).
 */

define ('JSMIN_ACT_BUF', 2);

/**
 * Constant describing an {@link action()} : Get the next B. (Delete B).
 */

define ('JSMIN_ACT_IMM', 3);

/**
* Main JSMin application class.
*
* Example of use :
*
* $jsMin = new JSMin(...input..., ...output...);
* $jsMin->minify();
*
* Do not specify input and/or output (or default to '-') to use stdin and/or stdout.
*/

class JSMin {

    /**
     * The input stream, from which to read a JS file to minimize. Obtained by fopen().
     * NB: might be a string instead of a stream
     * @var SplFileObject | string
     */
    var $in;

    /**
     * The output stream, in which to write the minimized JS file. Obtained by fopen().
     * NB: might be a string instead of a stream
     * @var SplFileObject | string
     */
    var $out;

    /**
     * Temporary I/O character (A).
     * @var string
     */
    var $theA;

    /**
     * Temporary I/O character (B).
     * @var string
     */
    var $theB;

	/** variables used for string-based parsing **/
	var $inLength = 0;
	var $inPos = 0;
	var $isString = false;

    /**
     * Indicates whether a character is alphanumeric or _, $, \ or non-ASCII.
     *
     * @param   string      $c  The single character to test.
     * @return  boolean     Whether the char is a letter, digit, underscore, dollar, backslash, or non-ASCII.
     */
    function isAlphaNum($c) {

        // Get ASCII value of character for C-like comparisons

        $a = ord($c);

        // Compare using defined character ordinals, or between PHP strings
        // Note : === is micro-faster than == when types are known to be the same

        return
            ($a >= ORD_a && $a <= ORD_z) ||
            ($a >= ORD_0 && $a <= ORD_9) ||
            ($a >= ORD_cA && $a <= ORD_cZ) ||
            $c === '_' || $c === '$' || $c === '\\' || $a > 126
        ;
    }

    /**
     * Get the next character from the input stream.
     *
     * If said character is a control character, translate it to a space or linefeed.
     *
     * @return  string      The next character from the specified input stream.
     * @see     $in
     * @see     peek()
     */
    function get() {

        // Get next input character and advance position in file

		if ($this->isString) {
			if ($this->inPos < $this->inLength) {
				$c = $this->in[$this->inPos];
				++$this->inPos;
			}
			else {
				return EOF;
			}
		}
		else
	        $c = $this->in->fgetc();

        // Test for non-problematic characters

        if ($c === "\n" || $c === EOF || ord($c) >= ORD_space) {
            return $c;
        }

        // else
        // Make linefeeds into newlines

        if ($c === "\r") {
            return "\n";
        }

        // else
        // Consider space

        return ' ';
    }

    /**
     * Get the next character from the input stream, without gettng it.
     *
     * @return  string      The next character from the specified input stream, without advancing the position
     *                      in the underlying file.
     * @see     $in
     * @see     get()
     */
    function peek() {

		if ($this->isString) {
			if ($this->inPos < $this->inLength) {
				$c = $this->in[$this->inPos];
			}
			else {
				return EOF;
			}
		}
		else {
	        // Get next input character

	        $c = $this->in->fgetc();

	        // Regress position in file

	        $this->in->fseek(-1, SEEK_CUR);

	        // Return character obtained
	    }

        return $c;
    }

	/**
	 * Adds a char to the output steram / string
	 * @see $out
	 */
	function put($c)
	{
		if ($this->isString) {
			$this->out .= $c;
		}
		else {
			$this->out->fwrite($c);
		}
	}

    /**
     * Get the next character from the input stream, excluding comments.
     *
     * {@link peek()} is used to see if a '/' is followed by a '*' or '/'.
     * Multiline comments are actually returned as a single space.
     *
     * @return  string  The next character from the specified input stream, skipping comments.
     * @see     $in
     */
    function next() {

        // Get next char from input, translated if necessary

        $c = $this->get();

        // Check comment possibility

        if ($c == '/') {

            // Look ahead : a comment is two slashes or slashes followed by asterisk (to be closed)

            switch ($this->peek()) {

                case '/' :

                    // Comment is up to the end of the line
                    // TOTEST : simple $this->in->fgets()

                    while (true) {

                        $c = $this->get();

                        if (ord($c) <= ORD_NL) {
                            return $c;
                        }
                    }

                case '*' :

                    // Comment is up to comment close.
                    // Might not be terminated, if we hit the end of file.

                    while (true) {

                        // N.B. not using switch() because of having to test EOF with ===

                        $c = $this->get();

                        if ($c == '*') {

                            // Comment termination if the char ahead is a slash

                            if ($this->peek() == '/') {

                                // Advance again and make into a single space

                                $this->get();
                                return ' ';
                            }
                        }
                        else if ($c === EOF) {

                            // Whoopsie

                            //throw new UnterminatedCommentJSMinException();
                            trigger_error('UnterminatedComment', E_USER_ERROR);
                        }
                    }

                default :

                    // Not a comment after all

                    return $c;
            }
        }

        // No risk of a comment

        return $c;
    }

    /**
     * Do something !
     *
     * The action to perform is determined by the argument :
     *
     * JSMin::ACT_FULL : Output A. Copy B to A. Get the next B.
     * JSMin::ACT_BUF  : Copy B to A. Get the next B. (Delete A).
     * JSMin::ACT_IMM  : Get the next B. (Delete B).
     *
     * A string is treated as a single character. Also, regular expressions are recognized if preceded
     * by '(', ',' or '='.
     *
     * @param   int     $action     The action to perform : one of the JSMin::ACT_* constants.
     */
    function action($action) {

        // Choice of possible actions
        // Note the frequent fallthroughs : the actions are decrementally "long"

        switch ($action) {

            case JSMIN_ACT_FULL :

                // Write A to output, then fall through

                $this->put($this->theA);

            case JSMIN_ACT_BUF : // N.B. possible fallthrough from above

                // Copy B to A

                $tmpA = $this->theA = $this->theB;

                // Treating a string as a single char : outputting it whole
                // Note that the string-opening char (" or ') is memorized in B

                if ($tmpA == '\'' || $tmpA == '"') {

                    while (true) {

                        // Output string contents

                        $this->put($tmpA);

                        // Get next character, watching out for termination of the current string,
                        // new line & co (then the string is not terminated !), or a backslash
                        // (upon which the following char is directly output to serve the escape mechanism)

                        $tmpA = $this->theA = $this->get();

                        if ($tmpA == $this->theB) {

                            // String terminated

                            break; // from while(true)
                        }

                        // else

                        if (ord($tmpA) <= ORD_NL) {

                            // Whoopsie

                            //throw new UnterminatedStringLiteralJSMinException();
                            trigger_error('UnterminatedStringLiteral', E_USER_ERROR);
                        }

                        // else

                        if ($tmpA == '\\') {

                            // Escape next char immediately

                            $this->put($tmpA);
                            $tmpA = $this->theA = $this->get();
                        }
                    }
                }

            case JSMIN_ACT_IMM : // N.B. possible fallthrough from above

                // Get the next B

                $this->theB = $this->next();

                // Special case of recognising regular expressions (beginning with /) that are
                // preceded by '(', ',' or '='

                $tmpA = $this->theA;

                if ($this->theB == '/' && ($tmpA == '(' || $tmpA == ',' || $tmpA == '=')) {

                    // Output the two successive chars

                    $this->put($tmpA);
                    $this->put($this->theB);

                    // Look for the end of the RE literal, watching out for escaped chars or a control /
                    // end of line char (the RE literal then being unterminated !)

                    while (true) {

                        $tmpA = $this->theA = $this->get();

                        if ($tmpA == '/') {

                            // RE literal terminated

                            break; // from while(true)
                        }

                        // else

                        if ($tmpA == '\\') {

                            // Escape next char immediately

                            $this->put($tmpA);
                            $tmpA = $this->theA = $this->get();
                        }
                        else if (ord($tmpA) <= ORD_NL) {

                            // Whoopsie

                            //throw new UnterminatedRegExpLiteralJSMinException();
                            trigger_error('UnterminatedRegExpLiteral', E_USER_ERROR);
                        }

                        // Output RE characters

                        $this->put($tmpA);
                    }

                    // Move forward after the RE literal

                    $this->theB = $this->next();
                }

            break;
            default :
				//throw new JSMinException('Expected a JSMin::ACT_* constant in action().');
				trigger_error('Expected a JSMin::ACT_* constant in action()', E_USER_ERROR);
        }
    }

    /**
     * Run the JSMin application : minify some JS code.
     *
     * The code is read from the input stream, and its minified version is written to the output one.
     * In case input is a string, minified vesrions is also returned by this function as string.
     * That is : characters which are insignificant to JavaScript are removed, as well as comments ;
     * tabs are replaced with spaces ; carriage returns are replaced with linefeeds, and finally most
     * spaces and linefeeds are deleted.
     *
     * Note : name was changed from jsmin() because PHP identifiers are case-insensitive, and it is already
     * the name of this class.
     *
     * @see     JSMin()
     * @return null | string
     */
    function minify() {

        // Initialize A and run the first (minimal) action

        $this->theA = "\n";
        $this->action(JSMIN_ACT_IMM);

        // Proceed all the way to the end of the input file

        while ($this->theA !== EOF) {

            switch ($this->theA) {

                case ' ' :

                    if (JSMin::isAlphaNum($this->theB)) {
                        $this->action(JSMIN_ACT_FULL);
                    }
                    else {
                        $this->action(JSMIN_ACT_BUF);
                    }

                break;
                case "\n" :

                    switch ($this->theB) {

                        case '{' : case '[' : case '(' :
                        case '+' : case '-' :

                            $this->action(JSMIN_ACT_FULL);

                        break;
                        case ' ' :

                            $this->action(JSMIN_ACT_IMM);

                        break;
                        default :

                            if (JSMin::isAlphaNum($this->theB)) {
                                $this->action(JSMIN_ACT_FULL);
                            }
                            else {
                                $this->action(JSMIN_ACT_BUF);
                            }

                        break;
                    }

                break;
                default :

                    switch ($this->theB) {

                        case ' ' :

                            if (JSMin::isAlphaNum($this->theA)) {

                                $this->action(JSMIN_ACT_FULL);
                                break;
                            }

                            // else

                            $this->action(JSMIN_ACT_IMM);

                        break;
                        case "\n" :

                            switch ($this->theA) {

                                case '}' : case ']' : case ')' : case '+' :
                                case '-' : case '"' : case '\'' :

                                    $this->action(JSMIN_ACT_FULL);

                                break;
                                default :

                                    if (JSMin::isAlphaNum($this->theA)) {
                                        $this->action(JSMIN_ACT_FULL);
                                    }
                                    else {
                                        $this->action(JSMIN_ACT_IMM);
                                    }

                                break;
                            }

                        break;
                        default :

                            $this->action(JSMIN_ACT_FULL);

                        break;
                    }

                break;
            }
        }

	    if ($this->isString) {
		    return $this->out;

	    }
    }

    /**
     * Prepare a new JSMin application.
     *
     * The next step is to {@link minify()} the input into the output.
     *
     * @param   string  $inFileName     The pathname of the input (unminified JS) file. STDIN if '-' or absent.
     * @param   string  $outFileName    The pathname of the output (minified JS) file. STDOUT if '-' or absent.
     *                                  If outFileName === FALSE, we assume that inFileName is in fact the string to be minified!!!
     * @param   array   $comments       Optional lines to present as comments at the beginning of the output.
     */
	function JSMin($inFileName = '-', $outFileName = '-', $comments = NULL) {
		if ($outFileName === FALSE) {
			$this->JSMin_String($inFileName, $comments);
		}
		else {
			$this->JSMin_File($inFileName, $outFileName, $comments);
		}
	}

    function JSMin_File($inFileName = '-', $outFileName = '-', $comments = NULL) {

        // Recuperate input and output streams.
        // Use STDIN and STDOUT by default, if they are defined (CLI mode) and no file names are provided

            if ($inFileName == '-')  $inFileName  = 'php://stdin';
            if ($outFileName == '-') $outFileName = 'php://stdout';

            /*try {

                $this->in = new SplFileObject($inFileName, 'rb', TRUE);
            }
            catch (Exception $e) {

                throw new FileOpenFailedJSMinException(
                    'Failed to open "'.$inFileName.'" for reading only.'
                );
            }

            try {

                $this->out = new SplFileObject($outFileName, 'wb', TRUE);
            }
            catch (Exception $e) {

                throw new FileOpenFailedJSMinException(
                    'Failed to open "'.$outFileName.'" for writing only.'
                );
            }
			*/
			$this->in = fopen($inFileName, 'rb');
			if (!$this->in) {
				trigger_error('Failed to open "'.$inFileName, E_USER_ERROR);
			}
			$this->out = fopen($outFileName, 'wb');
			if (!$this->out) {
				trigger_error('Failed to open "'.$outFileName, E_USER_ERROR);
			}

            // Present possible initial comments

            if ($comments !== NULL) {
                foreach ($comments as $comm) {
                    $this->out->fwrite('// '.str_replace("\n", " ", $comm)."\n");
                }
            }

    }

    function JSMin_String($inString, $comments = NULL) {
        $this->in = $inString;
        $this->out = '';
		$this->inLength = strlen($inString);
		$this->inPos = 0;
		$this->isString = true;

        if ($comments !== NULL) {
            foreach ($comments as $comm) {
                $this->out .= '// '.str_replace("\n", " ", $comm)."\n";
            }
        }
	}
}

//
// OTHER FUNCTIONS
//

/**
* Displays inline help for the application.
*/

function printHelp() {

    // All the inline help

    echo "\n";
    echo "Usage : JSMin.php [inputFile] [outputFile] [[-c] comm1 comm2 ...]\n";
    echo "        JSMin.php [-v|-h]\n";
    echo "\n";
    echo "Minify JavaScript code using JSMin, the JavaScript Minifier.\n";
    echo "\n";
    echo "JSMin is a filter which removes comments and unnecessary whitespace\n";
    echo "from a script read in the inputFile (stdin by default), as well as\n";
    echo "omitting or modifying some characters, before writing the results to\n";
    echo "the outputFile (stdout by default).\n";
    echo "It does not change the behaviour of the program that is minifies.\n";
    echo "The result may be harder to debug. It will definitely be harder to\n";
    echo "read. It typically reduces filesize by half, resulting in faster\n";
    echo "downloads. It also encourages a more expressive programming style\n";
    echo "because it eliminates the download cost of clean, literate self-\n";
    echo "documentation.\n";
    echo "\n";
    echo "The '-' character can be used to explicitely specify a standard\n";
    echo "stream for input or output.\n";
    echo "\n";
    echo "With the optional -c (--comm) option, all following parameters will\n";
    echo "be listed at the beginning of the output as comments. This is a\n";
    echo "convenient way of replacing copyright messages and other info. The\n";
    echo "option is unnecessary if an input and output file are specified :\n";
    echo "following parameters will then automatically be treated thus.\n";
    echo "\n";
    echo "Options :\n";
    echo "\n";
    echo "  -c, --comm      Present following parameters as initial comments.\n";
    echo "  -h, --help      Display this information.\n";
    echo "  -v, --version   Display short version information.\n";
    echo "\n";
    echo "The JavaScript Minifier is copyright (c) 2002 by Douglas Crockford\n";
    echo "and available online at http://javascript.crockford.com/jsmin.html.\n";
    echo "This PHP translation is by David Holmes of CFD Labs, France, 2006.\n";
    echo "\n";
}

/**
* Displays version information for the application.
*/

function printVersion() {

    // Minimum info

    echo "JSMin, the JavaScript Minifier, copyright (c) 2002 by Douglas Crockford.\n";
    echo "PHP translation version ".JSMIN_VERSION." by David Holmes, CFD Labs.\n";
}

//
// ENTRY POINT
//

// Allow user to include this file without having it run atomatically, ie. as if it was a lib
if (!defined('JSMIN_AS_LIB')) {

define('EXIT_SUCCESS', 0);
define('EXIT_FAILURE', 1);

// Examine command-line parameters
// First shift off the first parameter, the executable's name

if (!isset($argv)) {
	die("Please run this utility from a command line, or set php.ini setting 'register_argc_argv' to true.\nTo use this file in your own code, define 'JSMIN_AS_LIB' before inclusion.");
}

array_shift($argv);

$inFileName = NULL;
$outFileName = NULL;

$haveCommentParams = FALSE;
$comments = array();

foreach ($argv as $arg) {

    // Bypass the rest if we are now considering initial comments

    if ($haveCommentParams) {

        $comments[] = $arg;
        continue;
    }

    // else
    // Look for an option (length > 1 because of '-' for indicating stdin or
    // stdout)

    if ($arg[0] == '-' && strlen($arg) > 1) {

        switch ($arg) {

            case '-c' : case '--comm' :

                // Following parameters will be initial comments

                $haveCommentParams = TRUE;

            break;
            case '-h' : case '--help' :

                // Display inline help and exit normally

                printHelp();
                exit(EXIT_SUCCESS);

            case '-v' : case '--version' :

                // Display short version information and exit normally

                printVersion();
                exit(EXIT_SUCCESS);

            default :

                // Reject any other (unknown) option

                echo "\n";
                echo "ERROR : unknown option \"$arg\", sorry.\n";

                printHelp();
                exit(EXIT_FAILURE);
        }

        continue;
    }

    // else
    // At this point, parameter is neither to be considered as an initial
    // comment, nor is it an option. It is an input or output file.

    if ($inFileName === NULL) {

        // No input file yet, this is it

        $inFileName = $arg;
    }
    else if ($outFileName === NULL) {

        // An input file but no output file yet, this is it

        $outFileName = $arg;
    }
    else {

        // Already have input and output file, this is a first initial comment

        $haveCommentParams = TRUE;
        $comments[] = $arg;
    }
}

if ($inFileName === NULL)  $inFileName  = '-';
if ($outFileName === NULL) $outFileName = '-';

// Prepare and run the JSMin application
// If pathnames are not provided or '-', standard input/output streams are used

$jsMin = new JSMin($inFileName, $outFileName, $comments);
$jsMin->minify();

}

?>