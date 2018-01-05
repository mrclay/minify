<?php
/**
 * Class Minify_Lines
 * @package Minify
 */

/**
 * Add line numbers in C-style comments for easier debugging of combined content
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 * @author Adam Pedersen (Issue 55 fix)
 */
class Minify_Lines
{

    /**
     * Add line numbers in C-style comments
     *
     * This uses a very basic parser easily fooled by comment tokens inside
     * strings or regexes, but, otherwise, generally clean code will not be
     * mangled. URI rewriting can also be performed.
     *
     * @param string $content
     *
     * @param array $options available options:
     *
     * 'id': (optional) string to identify file. E.g. file name/path
     *
     * 'currentDir': (default null) if given, this is assumed to be the
     * directory of the current CSS file. Using this, minify will rewrite
     * all relative URIs in import/url declarations to correctly point to
     * the desired files, and prepend a comment with debugging information about
     * this process.
     *
     * @return string
     */
    public static function minify($content, $options = array())
    {
        $id = (isset($options['id']) && $options['id']) ? $options['id'] : '';
        $content = str_replace("\r\n", "\n", $content);

        $lines = explode("\n", $content);
        $numLines = count($lines);
        // determine left padding
        $padTo = strlen((string) $numLines); // e.g. 103 lines = 3 digits
        $inComment = false;
        $i = 0;
        $newLines = array();

        while (null !== ($line = array_shift($lines))) {
            if (('' !== $id) && (0 === $i % 50)) {
                if ($inComment) {
                    array_push($newLines, '', "/* {$id} *|", '');
                } else {
                    array_push($newLines, '', "/* {$id} */", '');
                }
            }

            ++$i;
            $newLines[] = self::_addNote($line, $i, $inComment, $padTo);
            $inComment = self::_eolInComment($line, $inComment);
        }

        $content = implode("\n", $newLines) . "\n";

        // check for desired URI rewriting
        if (isset($options['currentDir'])) {
            Minify_CSS_UriRewriter::$debugText = '';
            $docRoot = isset($options['docRoot']) ? $options['docRoot'] : $_SERVER['DOCUMENT_ROOT'];
            $symlinks = isset($options['symlinks']) ? $options['symlinks'] : array();

            $content = Minify_CSS_UriRewriter::rewrite($content, $options['currentDir'], $docRoot, $symlinks);

            $content = "/* Minify_CSS_UriRewriter::\$debugText\n\n"
                 . Minify_CSS_UriRewriter::$debugText . "*/\n"
                 . $content;
        }

        return $content;
    }

    /**
     * Is the parser within a C-style comment at the end of this line?
     *
     * @param string $line current line of code
     *
     * @param bool $inComment was the parser in a C-style comment at the
     * beginning of the previous line?
     *
     * @return bool
     */
    private static function _eolInComment($line, $inComment)
    {
        while (strlen($line)) {
            if ($inComment) {
                // only "*/" can end the comment
                $index = self::_find($line, '*/');
                if ($index === false) {
                    return true;
                }

                // stop comment and keep walking line
                $inComment = false;
                @$line = (string)substr($line, $index + 2);
                continue;
            }

            // look for "//" and "/*"
            $single = self::_find($line, '//');
            $multi = self::_find($line, '/*');
            if ($multi === false) {
                return false;
            }

            if ($single === false || $multi < $single) {
                // start comment and keep walking line
                $inComment = true;
                @$line = (string)substr($line, $multi + 2);
                continue;
            }

            // a single-line comment preceeded it
            return false;
        }

        return $inComment;
    }

    /**
     * Prepend a comment (or note) to the given line
     *
     * @param string $line current line of code
     *
     * @param string $note content of note/comment
     *
     * @param bool $inComment was the parser in a comment at the
     * beginning of the line?
     *
     * @param int $padTo minimum width of comment
     *
     * @return string
     */
    private static function _addNote($line, $note, $inComment, $padTo)
    {
        if ($inComment) {
            $line = '/* ' . str_pad($note, $padTo, ' ', STR_PAD_RIGHT) . ' *| ' . $line;
        } else {
            $line = '/* ' . str_pad($note, $padTo, ' ', STR_PAD_RIGHT) . ' */ ' . $line;
        }

        return rtrim($line);
    }

    /**
     * Find a token trying to avoid false positives
     *
     * @param string $str   String containing the token
     * @param string $token Token being checked
     * @return bool
     */
    private static function _find($str, $token)
    {
        switch ($token) {
            case '//':
                $fakes = array(
                    '://' => 1,
                    '"//' => 1,
                    '\'//' => 1,
                    '".//' => 2,
                    '\'.//' => 2,
                );
                break;
            case '/*':
                $fakes = array(
                    '"/*' => 1,
                    '\'/*' => 1,
                    '"//*' => 2,
                    '\'//*' => 2,
                    '".//*' => 3,
                    '\'.//*' => 3,
                    '*/*' => 1,
                    '\\/*' => 1,
                );
                break;
            default:
                $fakes = array();
        }

        $index = strpos($str, $token);
        $offset = 0;

        while ($index !== false) {
            foreach ($fakes as $fake => $skip) {
                $check = substr($str, $index - $skip, strlen($fake));
                if ($check === $fake) {
                    // move offset and scan again
                    $offset += $index + strlen($token);
                    $index = strpos($str, $token, $offset);
                    break;
                }
            }
            // legitimate find
            return $index;
        }

        return $index;
    }
}
