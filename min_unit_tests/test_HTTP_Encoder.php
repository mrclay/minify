<?php
require_once '_inc.php';

function test_HTTP_Encoder()
{
    global $thisDir;
    
    HTTP_Encoder::$encodeToIe6 = true;
    $methodTests = array(
        array(
            'ua' => 'Any browser'
            ,'ae' => 'compress, x-gzip'
            ,'exp' => array('gzip', 'x-gzip')
            ,'desc' => 'recognize "x-gzip" as gzip'
        )
        ,array(
            'ua' => 'Any browser'
            ,'ae' => 'compress, x-gzip;q=0.5'
            ,'exp' => array('gzip', 'x-gzip')
            ,'desc' => 'gzip w/ non-zero q'
        )
        ,array(
            'ua' => 'Any browser'
            ,'ae' => 'compress, x-gzip;q=0'
            ,'exp' => array('compress', 'compress')
            ,'desc' => 'gzip w/ zero q'
        )
        ,array(
            'ua' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'
            ,'ae' => 'gzip, deflate'
            ,'exp' => array('', '')
            ,'desc' => 'IE6 w/o "enhanced security"'
        )
        ,array(
            'ua' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)'
            ,'ae' => 'gzip, deflate'
            ,'exp' => array('gzip', 'gzip')
            ,'desc' => 'IE6 w/ "enhanced security"'
        )
        ,array(
            'ua' => 'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.01)'
            ,'ae' => 'gzip, deflate'
            ,'exp' => array('', '')
            ,'desc' => 'IE5.5'
        )
        ,array(
            'ua' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 9.25'
            ,'ae' => 'gzip,deflate'
            ,'exp' => array('gzip', 'gzip')
            ,'desc' => 'Opera identifying as IE6'
        )
    );
    foreach ($methodTests as $test) {
        $_SERVER['HTTP_USER_AGENT'] = $test['ua'];
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $test['ae'];
        $exp = $test['exp'];
        $ret = HTTP_Encoder::getAcceptedEncoding();
        $passed = assertTrue($exp == $ret, 'HTTP_Encoder : ' . $test['desc']);
        
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n--- AE | UA = {$test['ae']} | {$test['ua']}\n";
            echo "Expected = " . preg_replace('/\\s+/', ' ', var_export($exp, 1)) . "\n";
            echo "Returned = " . preg_replace('/\\s+/', ' ', var_export($ret, 1)) . "\n\n";
        }
    }
    HTTP_Encoder::$encodeToIe6 = false;
    $methodTests = array(
        array(
            'ua' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)'
            ,'ae' => 'gzip, deflate'
            ,'exp' => array('', '')
            ,'desc' => 'IE6 w/ "enhanced security"'
        )
    );
    foreach ($methodTests as $test) {
        $_SERVER['HTTP_USER_AGENT'] = $test['ua'];
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $test['ae'];
        $exp = $test['exp'];
        $ret = HTTP_Encoder::getAcceptedEncoding();
        $passed = assertTrue($exp == $ret, 'HTTP_Encoder : ' . $test['desc']);
        
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n--- AE | UA = {$test['ae']} | {$test['ua']}\n";
            echo "Expected = " . preg_replace('/\\s+/', ' ', var_export($exp, 1)) . "\n";
            echo "Returned = " . preg_replace('/\\s+/', ' ', var_export($ret, 1)) . "\n\n";
        }
    }
    
    if (! function_exists('gzdeflate')) {
        echo "!WARN: HTTP_Encoder : Zlib support is not present in PHP. Encoding cannot be performed/tested.\n";
        return;
    }
    
    // test compression of varied content (HTML,JS, & CSS)

    $variedContent = file_get_contents($thisDir . '/_test_files/html/before.html')
        . file_get_contents($thisDir . '/_test_files/css/subsilver.css')
        . file_get_contents($thisDir . '/_test_files/js/jquery-1.2.3.js');
    $variedLength = countBytes($variedContent);
    
    $encodingTests = array(
        array('method' => 'deflate', 'inv' => 'gzinflate', 'exp' => 32268)
        ,array('method' => 'gzip', 'inv' => '_gzdecode', 'exp' => 32286)
        ,array('method' => 'compress', 'inv' => 'gzuncompress', 'exp' => 32325)
    );
    
    foreach ($encodingTests as $test) {
        $e = new HTTP_Encoder(array(
            'content' => $variedContent
            ,'method' => $test['method']
        ));
        $e->encode(9);
        $ret = countBytes($e->getContent());
        
        // test uncompression
        $roundTrip = @call_user_func($test['inv'], $e->getContent());
        $desc = "HTTP_Encoder : {$test['method']} : uncompress possible";
        $passed = assertTrue($variedContent == $roundTrip, $desc);
        
        // test expected compressed size
        $desc = "HTTP_Encoder : {$test['method']} : compressed to "
            . sprintf('%4.2f%% of original', $ret/$variedLength*100);
        $passed = assertTrue(abs($ret - $test['exp']) < 100, $desc);
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
            echo "\n--- {$test['method']}: expected bytes: "
                , "{$test['exp']}. Returned: {$ret} "
                , "(off by ". abs($ret - $test['exp']) . " bytes)\n\n";
        }
    }

    HTTP_Encoder::$encodeToIe6 = true;
    $_SERVER['HTTP_ACCEPT_ENCODING'] = 'identity';
    $he = new HTTP_Encoder(array('content' => 'Hello'));
    $he->encode();
    $headers = $he->getHeaders();
    assertTrue(isset($headers['Vary']), 'HTTP_Encoder : Vary always sent to good browsers');

    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)';
    $he = new HTTP_Encoder(array('content' => 'Hello'));
    $he->encode();
    $headers = $he->getHeaders();
    assertTrue(! isset($headers['Vary']), 'HTTP_Encoder : Vary not sent to bad IE (Issue 126)');
}

test_HTTP_Encoder();

function _gzdecode($data)
{
    $filename = $error = '';
    return _phpman_gzdecode($data, $filename, $error);
}

// http://www.php.net/manual/en/function.gzdecode.php#82930
function _phpman_gzdecode($data, &$filename='', &$error='', $maxlength=null)
{
    $mbIntEnc = null;
    $hasMbOverload = (function_exists('mb_strlen')
                      && (ini_get('mbstring.func_overload') !== '')
                      && ((int)ini_get('mbstring.func_overload') & 2));
    if ($hasMbOverload) {
        $mbIntEnc = mb_internal_encoding();
        mb_internal_encoding('8bit');
    }


    $len = strlen($data);
    if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
        $error = "Not in GZIP format.";
        if ($mbIntEnc !== null) {
            mb_internal_encoding($mbIntEnc);
        }
        return null;  // Not GZIP format (See RFC 1952)
    }
    $method = ord(substr($data,2,1));  // Compression method
    $flags  = ord(substr($data,3,1));  // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        if ($mbIntEnc !== null) {
            mb_internal_encoding($mbIntEnc);
        }
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
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
            return false;  // invalid
        }
        $extralen = unpack("v",substr($data,8,2));
        $extralen = $extralen[1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
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
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
            return false; // invalid
        }
        $filenamelen = strpos(substr($data,$headerlen),chr(0));
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
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
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
            return false;    // invalid
        }
        $commentlen = strpos(substr($data,$headerlen),chr(0));
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
            return false;    // Invalid header format
        }
        $comment = substr($data,$headerlen,$commentlen);
        $headerlen += $commentlen + 1;
    }
    $headercrc = "";
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
            return false;    // invalid
        }
        $calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
        $headercrc = unpack("v", substr($data,$headerlen,2));
        $headercrc = $headercrc[1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
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
        if ($mbIntEnc !== null) {
            mb_internal_encoding($mbIntEnc);
        }
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
            if ($mbIntEnc !== null) {
                mb_internal_encoding($mbIntEnc);
            }
            return false;
        }
    }  // zero-byte body content is allowed
    // Verifiy CRC32
    $crc   = sprintf("%u",crc32($data));
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen($data);
    if (!$lenOK || !$crcOK) {
        $error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
        $ret = false;
    }
    $ret = $data;
    if ($mbIntEnc !== null) {
        mb_internal_encoding($mbIntEnc);
    }
    return $ret;
}