<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

require dirname(__FILE__) . '/../config.php';

set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());

// set cache path and doc root if configured
$minifyCachePath = isset($min_cachePath) 
    ? $min_cachePath 
    : '';

if ($min_documentRoot) {
    $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
} elseif (0 === stripos(PHP_OS, 'win')) {
    require_once 'Minify.php';
    Minify::setDocRoot(); // IIS may need help
}
$_SERVER['DOCUMENT_ROOT'] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

// default log to FirePHP
require_once 'Minify/Logger.php';
require_once 'FirePHP.php';
Minify_Logger::setLogger(FirePHP::getInstance(true));

if (! isset($min_cachePath)) {
    $min_cachePath = '';
}
// code in Minify::setCache()
if (is_string($min_cachePath)) {
    require_once 'Minify/Cache/File.php';
    $cache = new Minify_Cache_File($min_cachePath, $min_cacheFileLocking);
} else {
    $cache = $min_cachePath;
}


/**
 * pTest - PHP Unit Tester
 * @param mixed $test Condition to test, evaluated as boolean
 * @param string $html Descriptive message to output upon test
 * @url http://www.sitepoint.com/blogs/2007/08/13/ptest-php-unit-tester-in-9-lines-of-code/
 * @return bool success
 */
function assertTrue($test, $html)
{
	$outMode = $test ? 'PASS' : '!FAIL';
    $class = $test ? 'assert pass' : 'assert fail';
	printf("<p class='%s'><strong>%s</strong>: %s</p>", $class, $outMode, $html);	
	return (bool)$test;
}

function h($txt)
{
    return htmlspecialchars($txt);
}

function e($value)
{
    return htmlspecialchars(var_export($value, 1));
}

function _gzdecode($data)
{
    $filename = $error = '';
    return _phpman_gzdecode($data, $filename, $error);
}

// http://www.php.net/manual/en/function.gzdecode.php#82930
function _phpman_gzdecode($data, &$filename='', &$error='', $maxlength=null)
{
    $len = strlen($data);
    if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
        $error = "Not in GZIP format.";
        return null;  // Not GZIP format (See RFC 1952)
    }
    $method = ord(substr($data,2,1));  // Compression method
    $flags  = ord(substr($data,3,1));  // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        return null;
    }
    // NOTE: $mtime may be negative (PHP integer limitations)
    $mtime = unpack("V", substr($data,4,4));
    $mtime = $mtime[1];
    $xfl   = substr($data,8,1);
    $os    = substr($data,8,1);
    $headerlen = 10;
    $extralen  = 0;
    $extra     = "";
    if ($flags & 4) {
        // 2-byte length prefixed EXTRA data in header
        if ($len - $headerlen - 2 < 8) {
            return false;  // invalid
        }
        $extralen = unpack("v",substr($data,8,2));
        $extralen = $extralen[1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            return false;  // invalid
        }
        $extra = substr($data,10,$extralen);
        $headerlen += 2 + $extralen;
    }
    $filenamelen = 0;
    $filename = "";
    if ($flags & 8) {
        // C-style string
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $filenamelen = strpos(substr($data,$headerlen),chr(0));
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            return false; // invalid
        }
        $filename = substr($data,$headerlen,$filenamelen);
        $headerlen += $filenamelen + 1;
    }
    $commentlen = 0;
    $comment = "";
    if ($flags & 16) {
        // C-style string COMMENT data in header
        if ($len - $headerlen - 1 < 8) {
            return false;    // invalid
        }
        $commentlen = strpos(substr($data,$headerlen),chr(0));
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            return false;    // Invalid header format
        }
        $comment = substr($data,$headerlen,$commentlen);
        $headerlen += $commentlen + 1;
    }
    $headercrc = "";
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            return false;    // invalid
        }
        $calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
        $headercrc = unpack("v", substr($data,$headerlen,2));
        $headercrc = $headercrc[1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            return false;    // Bad header CRC
        }
        $headerlen += 2;
    }
    // GZIP FOOTER
    $datacrc = unpack("V",substr($data,-8,4));
    $datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
    $isize = unpack("V",substr($data,-4));
    $isize = $isize[1];
    // decompression:
    $bodylen = $len-$headerlen-8;
    if ($bodylen < 1) {
        // IMPLEMENTATION BUG!
        return null;
    }
    $body = substr($data,$headerlen,$bodylen);
    $data = "";
    if ($bodylen > 0) {
        switch ($method) {
        case 8:
            // Currently the only supported compression method:
            $data = gzinflate($body,$maxlength);
            break;
        default:
            $error = "Unknown compression method.";
            return false;
        }
    }  // zero-byte body content is allowed
    // Verifiy CRC32
    $crc   = sprintf("%u",crc32($data));
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen($data);
    if (!$lenOK || !$crcOK) {
        $error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
        return false;
    }
    return $data;
}