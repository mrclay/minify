<?php

/**
 * Linearize a CSS/JS file by including content specified by CSS import
 * declarations. In CSS files, relative URIs are fixed.
 *
 * @imports will be processed regardless of where they appear in the source
 * files; i.e. @imports commented out or in string content will still be
 * processed!
 *
 * This has a unit test but should be considered "experimental".
 */
class Minify_ImportProcessor
{
    /**
     * @var array
     */
    public static $filesIncluded = array();

    /**
     * Bom => Byte-Length
     *
     * INFO: https://en.wikipedia.org/wiki/Byte_order_mark
     *
     * @var array
     */
    private static $BOM = array(
        "\xef\xbb\xbf"     => 3, // UTF-8 BOM
        'ï»¿'              => 6, // UTF-8 BOM as "WINDOWS-1252" (one char has [maybe] more then one byte ...)
        "\x00\x00\xfe\xff" => 4, // UTF-32 (BE) BOM
        '  þÿ'             => 6, // UTF-32 (BE) BOM as "WINDOWS-1252"
        "\xff\xfe\x00\x00" => 4, // UTF-32 (LE) BOM
        'ÿþ  '             => 6, // UTF-32 (LE) BOM as "WINDOWS-1252"
        "\xfe\xff"         => 2, // UTF-16 (BE) BOM
        'þÿ'               => 4, // UTF-16 (BE) BOM as "WINDOWS-1252"
        "\xff\xfe"         => 2, // UTF-16 (LE) BOM
        'ÿþ'               => 4, // UTF-16 (LE) BOM as "WINDOWS-1252"
    );

    /**
     * @var bool
     */
    private static $_isCss;

    /**
     * allows callback funcs to know the current directory
     *
     * @var string
     */
    private $_currentDir;

    /**
     * allows callback funcs to know the directory of the file that inherits this one
     *
     * @var string
     */
    private $_previewsDir;

    /**
     * allows _importCB to write the fetched content back to the obj
     *
     * @var string
     */
    private $_importedContent = '';

    /**
     * @param string $currentDir
     * @param string $previewsDir Is only used internally
     */
    private function __construct($currentDir, $previewsDir = '')
    {
        $this->_currentDir = $currentDir;
        $this->_previewsDir = $previewsDir;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    public static function process($file)
    {
        self::$filesIncluded = array();
        self::$_isCss = (\strtolower(\substr($file, -4)) === '.css');
        $obj = new self(\dirname($file));

        return $obj->_getContent($file);
    }

    /**
     * Remove the BOM from UTF-8 / UTF-16 / UTF-32 strings.
     *
     * @param string $str <p>The input string.</p>
     *
     * @return string
     *                <p>A string without UTF-BOM.</p>
     */
    private static function remove_bom($str)
    {
        if ($str === '') {
            return '';
        }

        $str_length = \strlen($str);
        foreach (self::$BOM as $bom_string => $bom_byte_length) {
            if (\strpos($str, $bom_string) === 0) {
                /** @var false|string $str_tmp - needed for PhpStan (stubs error) */
                $str_tmp = \substr($str, $bom_byte_length, $str_length);
                if ($str_tmp === false) {
                    return '';
                }

                $str_length -= (int) $bom_byte_length;

                $str = (string) $str_tmp;
            }
        }

        return $str;
    }

    /**
     * @param string $file
     * @param bool   $is_imported
     *
     * @return string
     */
    private function _getContent($file, $is_imported = false)
    {
        $file = \realpath(
            (string) \preg_replace('~\\?.*~', '', $file)
        );

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        if (
            !$file
            ||
            \in_array($file, self::$filesIncluded, true)
            ||
            ($content = @\file_get_contents($file)) === false
        ) {
            // file missing, already included, or failed read
            return '';
        }

        self::$filesIncluded[] = $file;
        $this->_currentDir = \dirname($file);

        // remove BOM if present
        $content = self::remove_bom($content);

        // ensure uniform EOLs
        $content = \str_replace("\r\n", "\n", $content);

        if (\strpos($content, '@import') !== false) {
            // process @imports
            $pattern = '/
                @import\\s+
                (?:url\\(\\s*)?      # maybe url(
                [\'"]?               # maybe quote
                (.*?)                # 1 = URI
                [\'"]?               # maybe end quote
                (?:\\s*\\))?         # maybe )
                ([a-zA-Z,\\s]*)?     # 2 = media list
                ;                    # end token
            /x';
            $content = (string) \preg_replace_callback($pattern, array($this, '_importCB'), $content);
        }

        // You only need to rework the import-path if the script is imported.
        if (self::$_isCss && $is_imported) {
            // rewrite remaining relative URIs
            /** @noinspection NestedPositiveIfStatementsInspection */
            if (\strpos($content, 'url(') !== false) {
                $pattern = '/url\\(\\s*([^\\)\\s]+)\\s*\\)/';
                $content = (string) \preg_replace_callback($pattern, array($this, '_urlCB'), $content);
            }
        }

        return $this->_importedContent . $content;
    }

    /**
     * @param string[] $m
     *
     * @return string
     */
    private function _importCB($m)
    {
        $url = $m[1];
        $mediaList = \preg_replace('/\\s+/', '', $m[2]);

        if (\strpos($url, '://') > 0) {
            // protocol, leave in place for CSS, comment for JS
            return self::$_isCss
                ? (string) $m[0]
                : '/* Minify_ImportProcessor will not include remote content */';
        }

        if (\strpos($url, '/') === 0) {
            // protocol-relative or root path
            $url = \ltrim($url, '/');
            $file = \realpath($_SERVER['DOCUMENT_ROOT']) . \DIRECTORY_SEPARATOR . \str_replace('/', \DIRECTORY_SEPARATOR, $url);
        } else {
            // relative to current path
            $file = $this->_currentDir . \DIRECTORY_SEPARATOR . \str_replace('/', \DIRECTORY_SEPARATOR, $url);
        }

        $obj = new self(\dirname($file), $this->_currentDir);
        $content = $obj->_getContent($file, true);

        if ($content === '') {
            // failed. leave in place for CSS, comment for JS
            return self::$_isCss
                ? (string) $m[0]
                : "/* Minify_ImportProcessor could not fetch '{$file}' */";
        }

        return (
            !self::$_isCss
            ||
            (
                !$mediaList
                ||
                \preg_match('@(?:^$|\\ball\\b)@', $mediaList)
            )
        )
            ? $content
            : "@media {$mediaList} {\n{$content}\n}\n";
    }

    /**
     * @param string[] $m
     *
     * @return string
     */
    private function _urlCB($m)
    {
        // $m[1] is either quoted or not
        /** @noinspection SubStrUsedAsStrPosInspection */
        $quote = ($m[1][0] === "'" || $m[1][0] === '"') ? $m[1][0] : '';

        $url = ($quote === '') ? $m[1] : \substr($m[1], 1, -1);

        if ($url[0] !== '/') {
            /** @noinspection MissingOrEmptyGroupStatementInspection */
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            if (\strpos($url, '//') > 0) {
                // probably starts with protocol, do not alter
            } else {
                // prepend path with current dir separator (OS-independent)
                $path = $this->_currentDir . \DIRECTORY_SEPARATOR . \str_replace('/', \DIRECTORY_SEPARATOR, $url);
                // update the relative path by the directory of the file that imported this one
                $url = $this->getPathDiff((string) \realpath($this->_previewsDir), $path);
            }
        }

        return "url({$quote}{$url}{$quote})";
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $ps
     *
     * @return string
     */
    private function getPathDiff($from, $to, $ps = \DIRECTORY_SEPARATOR)
    {
        $realFrom = $this->truepath($from);
        $realTo = $this->truepath($to);

        $arFrom = \explode($ps, \rtrim($realFrom, $ps));
        \assert(\is_array($arFrom));
        $arTo = \explode($ps, \rtrim($realTo, $ps));
        \assert(\is_array($arTo));
        while (
            \count($arFrom)
            &&
            \count($arTo)
            &&
            $arFrom[0] === $arTo[0]
        ) {
            \array_shift($arFrom);
            \array_shift($arTo);
        }

        return \str_pad('', \count($arFrom) * 3, '..' . $ps) . \implode($ps, $arTo);
    }

    /**
     * This function is to replace PHP's extremely buggy realpath().
     *
     * @param string $path the original path, can be relative etc
     *
     * @return string the resolved path, it might not exist
     *
     * @see http://stackoverflow.com/questions/4049856/replace-phps-realpath
     */
    private function truepath($path)
    {
        // whether $path is unix or not
        $unipath = $path === '' || $path[0] !== '/';

        // attempts to detect if path is relative in which case, add cwd
        if (
            $unipath
            &&
            \strpos($path, ':') === false
        ) {
            $path = $this->_currentDir . \DIRECTORY_SEPARATOR . $path;
        }

        // resolve path parts (single dot, double dot and double delimiters)
        $path = \str_replace(array('/', '\\'), \DIRECTORY_SEPARATOR, $path);
        $parts = \array_filter(\explode(\DIRECTORY_SEPARATOR, $path), '\strlen');

        $absolutes = array();
        foreach ($parts as $part) {
            if ($part === '.') {
                continue;
            }
            if ($part === '..') {
                \array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        $path = \implode(\DIRECTORY_SEPARATOR, $absolutes);

        // resolve any symlinks
        if ($path && \file_exists($path) && \linkinfo($path) > 0) {
            $pathTmp = \readlink($path);
            if ($pathTmp !== false) {
                $path = (string) $pathTmp;
            }
        }

        // put initial separator that could have been lost
        return !$unipath ? '/' . $path : $path;
    }
}
