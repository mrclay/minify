<?php

/**
 * Encode and send gzipped/deflated content
 *
 * <code>
 * // Send a CSS file, compressed if possible
 * $he = new HTTP_Encoder(array(
 *     'content' => file_get_contents($cssFile)
 *     ,'type' => 'text/css'
 * ));
 * $he->encode();
 * $he->sendAll();
 * </code>
 *
 * <code>
 * // Just sniff for the accepted encoding
 * $encoding = HTTP_Encoder::getAcceptedEncoding();
 * </code>
 *
 * For more control over headers, use getHeaders() and getData() and send your
 * own output.
 */
class HTTP_Encoder {

    public static $compressionLevel = 6;
    private static $clientEncodeMethod = null;

    private $content = '';
    private $headers = array();

    private $encodeMethod = array('', '');

    public function __construct($spec) {
        if (isset($spec['content'])) {
            $this->content = $spec['content'];
        }
        $this->headers['Content-Length'] = strlen($this->content);
        if (isset($spec['type'])) {
            $this->headers['Content-Type'] = $spec['type'];
        }
        if (self::$clientEncodeMethod === null) {
            self::$clientEncodeMethod = self::getAcceptedEncoding();
        }
        if (isset($spec['method'])
            && in_array($spec['method'], array('gzip', 'deflate', 'compress', '')))
        {
            $this->encodeMethod = array($spec['method'], $spec['method']);
        } else {
            $this->encodeMethod = self::$clientEncodeMethod;
        }
    }

    public function getContent() {
        return $this->content;
    }
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Send the file and headers (encoded or not)
     *
     * You must call this before headers are sent and it probably cannot be
     * used in conjunction with zlib output buffering / mod_gzip. Errors are
     * not handled purposefully.
     */
    public function sendAll() {
        $this->sendHeaders();
        echo $this->content;
    }

    /**
     * Send just the headers
     */
    public function sendHeaders() {
        foreach ($this->headers as $name => $val) {
            header($name . ': ' . $val);
        }
    }

    // returns array(encoding, encoding to use in Content-Encoding header)
    // eg. array('gzip', 'x-gzip')
    public static function getAcceptedEncoding() {
        if (self::$clientEncodeMethod !== null) {
            return self::$clientEncodeMethod;
        }
        if (! isset($_SERVER['HTTP_ACCEPT_ENCODING'])
            || self::isBuggyIe())
        {
            return array('', '');
        }
        // test for (x-)gzip, if q is specified, can't be "0"
        if (preg_match('@(?:^|,)\s*((?:x-)?gzip)\s*(?:$|,|;\s*q=(?:0\.|1))@', $_SERVER['HTTP_ACCEPT_ENCODING'], $m)) {
            return array('gzip', $m[1]);
        }
        if (preg_match('@(?:^|,)\s*deflate\s*(?:$|,|;\s*q=(?:0\.|1))@', $_SERVER['HTTP_ACCEPT_ENCODING'])) {
            return array('deflate', 'deflate');
        }
        if (preg_match('@(?:^|,)\s*((?:x-)?compress)\s*(?:$|,|;\s*q=(?:0\.|1))@', $_SERVER['HTTP_ACCEPT_ENCODING'], $m)) {
            return array('compress', $m[1]);
        }
        return array('', '');
    }

    /**
     * If conditionsEncode the content
     * @return bool success
     */
    public function encode($compressionLevel = null) {
        if (null === $compressionLevel) {
            $compressionLevel = self::$compressionLevel;
        }
        if ('' === $this->encodeMethod[0]
            || ($compressionLevel == 0)
            || !extension_loaded('zlib'))
        {
            return false;
        }
        if ($this->encodeMethod[0] === 'gzip') {
            $encoded = gzencode($this->content, $compressionLevel);
        } elseif ($this->encodeMethod[0] === 'deflate') {
            $encoded = gzdeflate($this->content, $compressionLevel);
        } else {
            $encoded = gzcompress($this->content, $compressionLevel);
        }
        if (false === $encoded) {
            return false;
        }
        $this->headers['Content-Length'] = strlen($encoded);
        $this->headers['Content-Encoding'] = $this->encodeMethod[1];
        $this->headers['Vary'] = 'Accept-Encoding';
        $this->content = $encoded;
        return true;
    }

    private static function isBuggyIe()
    {
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')
            || !preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $m))
        {
            return false;
        }
        $version = floatval($m[1]);
        if ($version < 6) return true;
        if ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'SV1')) {
            return true;
        }
        return false;
    }
}
