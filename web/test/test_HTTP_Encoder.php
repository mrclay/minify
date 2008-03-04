<?php
require_once '_inc.php';

require_once 'HTTP/Encoder.php';

function test_HTTP_Encoder()
{
    global $thisDir;
    
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
            ,'exp' => array('deflate', 'deflate')
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
            ,'exp' => array('deflate', 'deflate')
            ,'desc' => 'Opera identifying as IE6'
        )
    );
    
    foreach ($methodTests as $test) {
        $_SERVER['HTTP_USER_AGENT'] = $test['ua'];
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $test['ae'];
        $exp = $test['exp'];
        $ret = HTTP_Encoder::getAcceptedEncoding();
        $passed = assertTrue($exp == $ret, 'HTTP_Encoder : ' . $test['desc']);
        
        if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
            echo "\n--- AE | UA = {$test['ae']} | {$test['ua']}\n";
            echo "Expected = " . preg_replace('/\\s+/', ' ', var_export($exp, 1)) . "\n";
            echo "Returned = " . preg_replace('/\\s+/', ' ', var_export($ret, 1)) . "\n\n";
        }
    }
    
    $variedContent = file_get_contents($thisDir . '/_test_files/html/before.html')
        . file_get_contents($thisDir . '/_test_files/css/subsilver.css')
        . file_get_contents($thisDir . '/../examples/1/jquery-1.2.3.js');
    
    $encodingTests = array(
        array('method' => 'gzip', 'exp' => 32174)
        ,array('method' => 'deflate', 'exp' => 32156)
        ,array('method' => 'compress', 'exp' => 32210)
    );
    
    foreach ($encodingTests as $test) {
        $e = new HTTP_Encoder(array(
            'content' => $variedContent
            ,'method' => $test['method']
        ));
        $e->encode(9);
        $ret = strlen($e->getContent());
        
        $passed = assertTrue($ret == $test['exp']
            ,"HTTP_Encoder : {$test['method']} compression");
        
        if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
            echo "\n--- {$test['method']}: expected bytes: "
                , "{$test['exp']}. Returned: {$ret}\n\n";
        }
    }
}

test_HTTP_Encoder();