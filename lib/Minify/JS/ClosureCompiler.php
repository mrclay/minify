<?php

/**
 * Minify Javascript using Google's Closure Compiler API
 *
 * @see  http://code.google.com/closure/compiler/
 *
 * @TODO : can use a stream wrapper to unit test this?
 */
class Minify_JS_ClosureCompiler
{
    /**
     * @var int The default maximum POST byte size according to
     *      https://developers.google.com/closure/compiler/docs/api-ref
     */
    const DEFAULT_MAX_BYTES = 200000;

    /**
     * @var string The option key for additional params. @see __construct
     */
    const OPTION_ADDITIONAL_OPTIONS = 'additionalParams';

    /**
     * @var string The option key for the service URL
     */
    const OPTION_COMPILER_URL = 'compilerUrl';

    /**
     * @var string The option key for the fallback Minifier
     */
    const OPTION_FALLBACK_FUNCTION = 'fallbackFunc';

    /**
     * @var string The option key for the maximum POST byte size
     */
    const OPTION_MAX_BYTES = 'maxBytes';

    /**
     * @var string[] The default options to pass to the compiler service
     *
     * @note This would be a constant if PHP allowed it
     */
    private static $DEFAULT_OPTIONS = array(
        'output_format'     => 'text',
        'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
    );

    /**
     * @var string URL of compiler server. defaults to Google's
     */
    protected $serviceUrl = 'https://closure-compiler.appspot.com/compile';

    /**
     * @var int The maximum JS size that can be sent to the compiler server in bytes
     */
    protected $maxBytes = self::DEFAULT_MAX_BYTES;

    /**
     * @var string[] Additional options to pass to the compiler service
     */
    protected $additionalOptions = array();

    /**
     * @var callable Function to minify JS if service fails. Default is JSMin
     */
    protected $fallbackMinifier = array('JSMin\\JSMin', 'minify');

    /**
     * @param array $options Options with keys available below:
     *
     *  fallbackFunc     : (callable) function to minify if service unavailable. Default is JSMin.
     *
     *  compilerUrl      : (string) URL to closure compiler server
     *
     *  maxBytes         : (int) The maximum amount of bytes to be sent as js_code in the POST request.
     *                     Defaults to 200000.
     *
     *  additionalParams : (string[]) Additional parameters to pass to the compiler server. Can be anything named
     *                     in https://developers.google.com/closure/compiler/docs/api-ref except for js_code and
     *                     output_info
     */
    public function __construct(array $options = array())
    {
        if (isset($options[self::OPTION_FALLBACK_FUNCTION])) {
            $this->fallbackMinifier = $options[self::OPTION_FALLBACK_FUNCTION];
        }

        if (isset($options[self::OPTION_COMPILER_URL])) {
            $this->serviceUrl = $options[self::OPTION_COMPILER_URL];
        }

        if (
            isset($options[self::OPTION_ADDITIONAL_OPTIONS])
            &&
            \is_array($options[self::OPTION_ADDITIONAL_OPTIONS])
        ) {
            $this->additionalOptions = $options[self::OPTION_ADDITIONAL_OPTIONS];
        }

        if (isset($options[self::OPTION_MAX_BYTES])) {
            $this->maxBytes = (int) $options[self::OPTION_MAX_BYTES];
        }
    }

    /**
     * Minify JavaScript code via HTTP request to a Closure Compiler API
     *
     * @param string $js      input code
     * @param array  $options Options passed to __construct(). @see __construct
     *
     * @return string
     */
    public static function minify($js, array $options = array())
    {
        $obj = new self($options);

        return $obj->min($js);
    }

    /**
     * Call the service to perform the minification
     *
     * @param string $js JavaScript code
     *
     * @throws Minify_JS_ClosureCompiler_Exception
     *
     * @return string
     */
    public function min($js)
    {
        $postBody = $this->buildPostBody($js);

        if ($this->maxBytes > 0) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $bytes = (
                \function_exists('mb_strlen')
                &&
                (int) \ini_get('mbstring.func_overload') & 2
            )
                ? \mb_strlen($postBody, '8bit')
                : \strlen($postBody);

            if ($bytes > $this->maxBytes) {
                throw new Minify_JS_ClosureCompiler_Exception(
                    'POST content larger than ' . $this->maxBytes . ' bytes'
                );
            }
        }

        $response = $this->getResponse($postBody);

        if (\preg_match('/^Error\(\d\d?\):/', $response)) {
            if (\is_callable($this->fallbackMinifier)) {
                // use fallback
                $response = "/* Received errors from Closure Compiler API:\n${response}" . "\n(Using fallback minifier)\n*/\n";
                $response .= \call_user_func($this->fallbackMinifier, $js);
            } else {
                throw new Minify_JS_ClosureCompiler_Exception($response);
            }
        }

        if ($response === '') {
            $errors = $this->getResponse($this->buildPostBody($js, true));

            throw new Minify_JS_ClosureCompiler_Exception($errors);
        }

        return $response;
    }

    /**
     * Build a POST request body
     *
     * @param string $js JavaScript code
     * @param bool   $returnErrors
     *
     * @return string
     */
    protected function buildPostBody($js, $returnErrors = false)
    {
        return \http_build_query(
            \array_merge(
                self::$DEFAULT_OPTIONS,
                $this->additionalOptions,
                array(
                    'js_code'     => $js,
                    'output_info' => ($returnErrors ? 'errors' : 'compiled_code'),
                )
            ),
            '',
            '&'
        );
    }

    /**
     * Get the response for a given POST body
     *
     * @param string $postBody
     *
     * @throws Minify_JS_ClosureCompiler_Exception
     *
     * @return string
     */
    protected function getResponse($postBody)
    {
        $allowUrlFopen = \preg_match(
            '/1|yes|on|true/i',
            (string) \ini_get('allow_url_fopen')
        );

        if ($allowUrlFopen) {
            $contents = \file_get_contents(
                $this->serviceUrl,
                false,
                \stream_context_create(
                    array(
                        'http' => array(
                            'method'            => 'POST',
                            'compilation_level' => 'SIMPLE',
                            'output_format'     => 'text',
                            'output_info'       => 'compiled_code',
                            'header'            => "Content-type: application/x-www-form-urlencoded\r\nConnection: close\r\n",
                            'content'           => $postBody,
                            'max_redirects'     => 0,
                            'timeout'           => 15,
                        ),
                    )
                )
            );
        } elseif (\defined('CURLOPT_POST')) {
            $ch = \curl_init($this->serviceUrl);
            if ($ch === false) {
                throw new Minify_JS_ClosureCompiler_Exception(
                    'Could not make HTTP request: curl_init is false'
                );
            }
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $postBody);
            \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, false);
            \curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 15);
            $contents = \curl_exec($ch);
            \curl_close($ch);
        } else {
            throw new Minify_JS_ClosureCompiler_Exception(
                'Could not make HTTP request: allow_url_open is false and cURL not available'
            );
        }

        if ($contents === false) {
            throw new Minify_JS_ClosureCompiler_Exception(
                'No HTTP response from server'
            );
        }

        return \trim((string) $contents);
    }
}

class Minify_JS_ClosureCompiler_Exception extends Exception
{
}
