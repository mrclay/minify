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

    /**
     * Default compression level for zlib operations
     * 
     * This level is used if encode() is not given a $compressionLevel
     * 
     * @var int
     */
    public static $compressionLevel = 6;

    /**
     * Get an HTTP Encoder object
     * 
     * @param array $spec options
     * 
     * 'content': (string required) content to be encoded
     * 
     * 'type': (string) if set, the Content-Type header will have this value.
     * 
     * 'method: (string) only set this if you are forcing a particular encoding
     * method. If not set, the best method will be chosen by getAcceptedEncoding()
     * The available methods are 'gzip', 'deflate', 'compress', and '' (no
     * encoding)
     * 
     * @return null
     */
    public function __construct($spec) {
        $this->_content = $spec['content'];
        $this->_headers['Content-Length'] = (string)strlen($this->_content);
        if (isset($spec['type'])) {
            $this->_headers['Content-Type'] = $spec['type'];
        }
        if (self::$_clientEncodeMethod === null) {
            self::$_clientEncodeMethod = self::getAcceptedEncoding();
        }
        if (isset($spec['method'])
            && in_array($spec['method'], array('gzip', 'deflate', 'compress', '')))
        {
            $this->_encodeMethod = array($spec['method'], $spec['method']);
        } else {
            $this->_encodeMethod = self::$_clientEncodeMethod;
        }
    }

    /**
     * Get content in current form
     * 
     * Call after encode() for encoded content.
     * 
     * return string
     */
    public function getContent() {
        return $this->_content;
    }
    
    /**
     * Get array of output headers to be sent
     * 
     * E.g.
     * <code>
     * array(
     *     'Content-Length' => '615'
     *     ,'Content-Encoding' => 'x-gzip'
     *     ,'Vary' => 'Accept-Encoding'
     * )
     * </code>
     *
     * @return array 
     */
    public function getHeaders() {
        return $this->_headers;
    }

    /**
     * Send output headers
     * 
     * You must call this before headers are sent and it probably cannot be
     * used in conjunction with zlib output buffering / mod_gzip. Errors are
     * not handled purposefully.
     * 
     * @see getHeaders()
     * 
     * @return null
     */
    public function sendHeaders() {
        foreach ($this->_headers as $name => $val) {
            header($name . ': ' . $val);
        }
    }
    
    /**
     * Send output headers and content
     * 
     * A shortcut for sendHeaders() and echo getContent()
     *
     * You must call this before headers are sent and it probably cannot be
     * used in conjunction with zlib output buffering / mod_gzip. Errors are
     * not handled purposefully.
     * 
     * @return null
     */
    public function sendAll() {
        $this->sendHeaders();
        echo $this->_content;
    }

    /**
     * Determine the client's best encoding method from the HTTP Accept-Encoding 
     * header.
     * 
     * If no Accept-Encoding header is set, or the browser is IE before v6 SP2,
     * this will return ('', ''), the "identity" encoding.
     * 
     * A syntax-aware scan is done of the Accept-Encoding, so the method must
     * be non 0. The methods are favored in order of gzip, deflate, then 
     * compress.
     * 
     * Note: this value is cached internally for the entire PHP execution
     * 
     * @return array two values, 1st is the actual encoding method, 2nd is the
     * alias of that method to use in the Content-Encoding header (some browsers
     * call gzip "x-gzip" etc.)
     */
    public static function getAcceptedEncoding() {
        if (self::$_clientEncodeMethod !== null) {
            return self::$_clientEncodeMethod;
        }
        if (! isset($_SERVER['HTTP_ACCEPT_ENCODING'])
            || self::_isBuggyIe())
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
     * Encode (compress) the content
     * 
     * If the encode method is '' (none) or compression level is 0, or the 'zlib'
     * extension isn't loaded, we return false.
     * 
     * Then the appropriate gz_* function is called to compress the content. If
     * this fails, false is returned.
     * 
     * If successful, the Content-Length header is updated, and Content-Encoding
     * and Vary headers are added.
     * 
     * @param int $compressionLevel given to zlib functions. If not given, the
     * class default will be used.
     * 
     * @return bool success true if the content was actually compressed
     */
    public function encode($compressionLevel = null) {
        if (null === $compressionLevel) {
            $compressionLevel = self::$compressionLevel;
        }
        if ('' === $this->_encodeMethod[0]
            || ($compressionLevel == 0)
            || !extension_loaded('zlib'))
        {
            return false;
        }
        if ($this->_encodeMethod[0] === 'gzip') {
            $encoded = gzencode($this->_content, $compressionLevel);
        } elseif ($this->_encodeMethod[0] === 'deflate') {
            $encoded = gzdeflate($this->_content, $compressionLevel);
        } else {
            $encoded = gzcompress($this->_content, $compressionLevel);
        }
        if (false === $encoded) {
            return false;
        }
        $this->_headers['Content-Length'] = strlen($encoded);
        $this->_headers['Content-Encoding'] = $this->_encodeMethod[1];
        $this->_headers['Vary'] = 'Accept-Encoding';
        $this->_content = $encoded;
        return true;
    }

    protected static $_clientEncodeMethod = null;
    protected $content = '';
    protected $headers = array();
    protected $encodeMethod = array('', '');

    protected static function _isBuggyIe()
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
