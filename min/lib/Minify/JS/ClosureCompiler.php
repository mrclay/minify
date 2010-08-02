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
 */
class Minify_JS_ClosureCompiler {
    const URL = 'http://closure-compiler.appspot.com/compile';

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
     * fallbackFunc : default array($this, 'fallback');
     */
    public function __construct(array $options = array())
    {
        $this->_fallbackFunc = isset($options['fallbackMinifier'])
            ? $options['fallbackMinifier']
            : array($this, '_fallback');
    }

    public function min($js)
    {
        $content = $this->_getPostContent($js);
        $bytes = (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2))
            ? mb_strlen($content, '8bit')
            : strlen($content);
        if ($bytes > 200000) {
            throw new Minify_JS_ClosureCompiler_Exception(
                'POST content larger than 200000 bytes'
            );
        }
        $response = $this->_getResponse($content);
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
            $errors = $this->_getResponse($this->_getPostContent($js, true));
            throw new Minify_JS_ClosureCompiler_Exception($errors);
        }
        return $response;
    }
    
    protected $_fallbackFunc = null;

    protected function _getResponse($content)
    {
        $contents = file_get_contents(self::URL, false, stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $content,
                'max_redirects' => 0,
                'timeout' => 15,
            )
        )));
        if (false === $contents) {
            throw new Minify_JS_ClosureCompiler_Exception(
               "No HTTP response from server"
            );
        }
        return trim($contents);
    }

    protected function _getPostContent($js, $returnErrors = false)
    {
        return http_build_query(array(
            'js_code' => $js,
            'output_info' => ($returnErrors ? 'errors' : 'compiled_code'),
            'output_format' => 'text',
            'compilation_level' => 'SIMPLE_OPTIMIZATIONS'
        ));
    }

    protected function _fallback($js)
    {
        require_once 'JSMin.php';
        return JSMin::minify($js);
    }
}

class Minify_JS_ClosureCompiler_Exception extends Exception {}
