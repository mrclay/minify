<?php
/**
 * Class Minify_JS_ClosureCompiler
 * @package Minify
 */

/**
 * Minify Javascript using Google's Closure Compiler API
 *
 * @link http://code.google.com/closure/compiler/
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 *
 * @todo can use a stream wrapper to unit test this?
 */
class Minify_JS_ClosureCompiler {

    /**
    * @var string the option for the maximum POST byte size
    */
    const OPTION_MAX_BYTES = 'maxBytes';

    /**
    * @var int the default maximum POST byte size according to https://developers.google.com/closure/compiler/docs/api-ref
    */
    const MAX_BYTES_DEFAULT = 200000;

    /**
     * @var $url URL to compiler server. defaults to google server
     */
    protected $url = 'http://closure-compiler.appspot.com/compile';

    /**
    * @var $maxBytes The maximum JS size that can be sent to the compiler server in bytes
    */
    protected $maxBytes = self::MAX_BYTES_DEFAULT;

    /**
    * @var $additionalOptions array additional options to pass to the compiler service
    */
    protected $additionalOptions = array();

    /**
    * @var $DEFAULT_OPTIONS array the default options to pass to the compiler service
    */
    private static $DEFAULT_OPTIONS = array(
                            'output_format' => 'text',
                            'compilation_level' => 'SIMPLE_OPTIMIZATIONS');

    /**
    * @var string the option for additional params.
    * Read more about additional params here: https://developers.google.com/closure/compiler/docs/api-ref
    * This also allows you to override the output_format or the compilation_level.
    * The parameters js_code and output_info can not be set in this way
    */
    const OPTION_ADDITIONAL_HTTP_PARAMS = 'additionalParams';

    /**
     * Minify Javascript code via HTTP request to the Closure Compiler API
     *
     * @param string $js input code
     * @param array $options unused at this point
     * @return string
     */
    public static function minify($js, array $options = array())
    {
        $obj = new self($options);
        return $obj->min($js);
    }

    /**
     *
     * @param array $options
     *
     * fallbackFunc : array default array($this, '_fallback');
     * compilerUrl : string URL to closure compiler server
     * maxBytes : int The maximum amount of bytes to be sent as js_code in the POST request. Defaults to 200000.
     * additionalParams: array The additional parameters to pass to the compiler server. Can be anything named in https://developers.google.com/closure/compiler/docs/api-ref except for js_code and output_info
     */
    public function __construct(array $options = array())
    {
        $this->_fallbackFunc = isset($options['fallbackMinifier'])
            ? $options['fallbackMinifier']
            : array($this, '_fallback');

        if (isset($options['compilerUrl'])) {
            $this->url = $options['compilerUrl'];
        }

        if (isset($options[self::OPTION_ADDITIONAL_HTTP_PARAMS]) && is_array($options[self::OPTION_ADDITIONAL_HTTP_PARAMS])) {
            $this->additionalOptions = $options[self::OPTION_ADDITIONAL_HTTP_PARAMS];
        }
        if (isset($options[self::OPTION_MAX_BYTES])) {
            $this->maxBytes = (int) $options[self::OPTION_MAX_BYTES];
        }
    }

    public function min($js)
    {
        $postBody = $this->_buildPostBody($js);
        if ($this->maxBytes > 0) {
            $bytes = (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2))
                ? mb_strlen($postBody, '8bit')
                : strlen($postBody);
            if ($bytes > $this->maxBytes) {
                throw new Minify_JS_ClosureCompiler_Exception(
                    'POST content larger than ' . $this->maxBytes . ' bytes'
                );
            }
        }
        $response = $this->_getResponse($postBody);
        if (preg_match('/^Error\(\d\d?\):/', $response)) {
            if (is_callable($this->_fallbackFunc)) {
                $response = "/* Received errors from Closure Compiler API:\n$response"
                          . "\n(Using fallback minifier)\n*/\n";
                $response .= call_user_func($this->_fallbackFunc, $js);
            } else {
                throw new Minify_JS_ClosureCompiler_Exception($response);
            }
        }
        if ($response === '') {
            $errors = $this->_getResponse($this->_buildPostBody($js, true));
            throw new Minify_JS_ClosureCompiler_Exception($errors);
        }
        return $response;
    }

    protected $_fallbackFunc = null;

    protected function _getResponse($postBody)
    {
        $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
        if ($allowUrlFopen) {
            $contents = file_get_contents($this->url, false, stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\nConnection: close\r\n",
                    'content' => $postBody,
                    'max_redirects' => 0,
                    'timeout' => 15,
                )
            )));
        } elseif (defined('CURLOPT_POST')) {
            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            $contents = curl_exec($ch);
            curl_close($ch);
        } else {
            throw new Minify_JS_ClosureCompiler_Exception(
               "Could not make HTTP request: allow_url_open is false and cURL not available"
            );
        }
        if (false === $contents) {
            throw new Minify_JS_ClosureCompiler_Exception(
               "No HTTP response from server"
            );
        }
        return trim($contents);
    }

    protected function _buildPostBody($js, $returnErrors = false)
    {
        return http_build_query(
            array_merge(
                self::$DEFAULT_OPTIONS,
                $this->additionalOptions,
                array(
                    'js_code' => $js,
                    'output_info' => ($returnErrors ? 'errors' : 'compiled_code')
                )
            ),
            null,
            '&'
        );
    }

    /**
     * Default fallback function if CC API fails
     * @param string $js
     * @return string
     */
    protected function _fallback($js)
    {
        return JSMin::minify($js);
    }
}

class Minify_JS_ClosureCompiler_Exception extends Exception {}
