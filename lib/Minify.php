<?php
/**
 * Class Minify
 * @package Minify
 */

use Psr\Log\LoggerInterface;

/**
 * Minify - Combines, minifies, and caches JavaScript and CSS files on demand.
 *
 * See README for usage instructions (for now).
 *
 * This library was inspired by {@link mailto:flashkot@mail.ru jscsscomp by Maxim Martynyuk}
 * and by the article {@link http://www.hunlock.com/blogs/Supercharged_Javascript "Supercharged JavaScript" by Patrick Hunlock}.
 *
 * @package Minify
 * @author Ryan Grove <ryan@wonko.com>
 * @author Stephen Clay <steve@mrclay.org>
 * @copyright 2008 Ryan Grove, Stephen Clay. All rights reserved.
 * @license http://opensource.org/licenses/bsd-license.php  New BSD License
 * @link https://github.com/mrclay/minify
 */
class Minify
{

    /**
     * API version
     *
     * This is only bumped when API breaks are done and should follow the major version of the library
     *
     * @var int
     */
    const VERSION = 3;

    const TYPE_CSS = 'text/css';
    const TYPE_HTML = 'text/html';
    // there is some debate over the ideal JS Content-Type, but this is the
    // Apache default and what Yahoo! uses..
    const TYPE_JS = 'application/x-javascript';
    const URL_DEBUG = 'https://github.com/mrclay/minify/blob/master/docs/Debugging.wiki.md';

    /**
     * Any Minify_Cache_* object or null (i.e. no server cache is used)
     *
     * @var Minify_CacheInterface
     */
    private $cache;

    /**
     * Active controller for current request
     *
     * @var Minify_Controller_Base
     */
    protected $controller;

    /**
     * @var Minify_Env
     */
    protected $env;

    /**
     * @var Minify_SourceInterface[]
     */
    protected $sources;

    /**
     * @var string
     */
    protected $selectionId;

    /**
     * Options for current request
     *
     * @var array
     */
    protected $options;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @param Minify_CacheInterface $cache
     * @param LoggerInterface       $logger
     */
    public function __construct(Minify_CacheInterface $cache, LoggerInterface $logger = null)
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Get default Minify options.
     *
     * @return array options for Minify
     */
    public function getDefaultOptions()
    {
        return array(
            'isPublic' => true,
            'encodeOutput' => function_exists('gzdeflate'),
            'encodeMethod' => null, // determine later
            'encodeLevel' => 9,

            'minifiers' => array(
                Minify::TYPE_JS => array('JSMin\\JSMin', 'minify'),
                Minify::TYPE_CSS => array('Minify_CSSmin', 'minify'),
                Minify::TYPE_HTML => array('Minify_HTML', 'minify'),
            ),
            'minifierOptions' => array(), // no minifier options

            'contentTypeCharset' => 'utf-8',
            'maxAge' => 1800, // 30 minutes
            'rewriteCssUris' => true,
            'bubbleCssImports' => false,
            'quiet' => false, // serve() will send headers and output
            'debug' => false,
            'concatOnly' => false,

            // if you override these, the response codes MUST be directly after
            // the first space.
            'badRequestHeader' => 'HTTP/1.0 400 Bad Request',
            'errorHeader' => 'HTTP/1.0 500 Internal Server Error',

            // callback function to see/modify content of all sources
            'postprocessor' => null,
            // file to require to load preprocessor
            'postprocessorRequire' => null,

            /**
             * If this string is not empty AND the serve() option 'bubbleCssImports' is
             * NOT set, then serve() will check CSS files for @import declarations that
             * appear too late in the combined stylesheet. If found, serve() will prepend
             * the output with this warning.
             */
            'importWarning' => "/* See https://github.com/mrclay/minify/blob/master/docs/CommonProblems.wiki.md#imports-can-appear-in-invalid-locations-in-combined-css-files */\n"
        );
    }

    /**
     * Serve a request for a minified file.
     *
     * Here are the available options and defaults:
     *
     * 'isPublic' : send "public" instead of "private" in Cache-Control
     * headers, allowing shared caches to cache the output. (default true)
     *
     * 'quiet' : set to true to have serve() return an array rather than sending
     * any headers/output (default false)
     *
     * 'encodeOutput' : set to false to disable content encoding, and not send
     * the Vary header (default true)
     *
     * 'encodeMethod' : generally you should let this be determined by
     * HTTP_Encoder (leave null), but you can force a particular encoding
     * to be returned, by setting this to 'gzip' or '' (no encoding)
     *
     * 'encodeLevel' : level of encoding compression (0 to 9, default 9)
     *
     * 'contentTypeCharset' : appended to the Content-Type header sent. Set to a falsey
     * value to remove. (default 'utf-8')
     *
     * 'maxAge' : set this to the number of seconds the client should use its cache
     * before revalidating with the server. This sets Cache-Control: max-age and the
     * Expires header. Unlike the old 'setExpires' setting, this setting will NOT
     * prevent conditional GETs. Note this has nothing to do with server-side caching.
     *
     * 'rewriteCssUris' : If true, serve() will automatically set the 'currentDir'
     * minifier option to enable URI rewriting in CSS files (default true)
     *
     * 'bubbleCssImports' : If true, all @import declarations in combined CSS
     * files will be move to the top. Note this may alter effective CSS values
     * due to a change in order. (default false)
     *
     * 'debug' : set to true to minify all sources with the 'Lines' controller, which
     * eases the debugging of combined files. This also prevents 304 responses.
     * @see Minify_Lines::minify()
     *
     * 'concatOnly' : set to true to disable minification and simply concatenate the files.
     * For JS, no minifier will be used. For CSS, only URI rewriting is still performed.
     *
     * 'minifiers' : to override Minify's default choice of minifier function for
     * a particular content-type, specify your callback under the key of the
     * content-type:
     * <code>
     * // call customCssMinifier($css) for all CSS minification
     * $options['minifiers'][Minify::TYPE_CSS] = 'customCssMinifier';
     *
     * // don't minify Javascript at all
     * $options['minifiers'][Minify::TYPE_JS] = 'Minify::nullMinifier';
     * </code>
     *
     * 'minifierOptions' : to send options to the minifier function, specify your options
     * under the key of the content-type. E.g. To send the CSS minifier an option:
     * <code>
     * // give CSS minifier array('optionName' => 'optionValue') as 2nd argument
     * $options['minifierOptions'][Minify::TYPE_CSS]['optionName'] = 'optionValue';
     * </code>
     *
     * 'contentType' : (optional) this is only needed if your file extension is not
     * js/css/html. The given content-type will be sent regardless of source file
     * extension, so this should not be used in a Groups config with other
     * Javascript/CSS files.
     *
     * 'importWarning' : serve() will check CSS files for @import declarations that
     * appear too late in the combined stylesheet. If found, serve() will prepend
     * the output with this warning. To disable this, set this option to empty string.
     *
     * Any controller options are documented in that controller's createConfiguration() method.
     *
     * @param Minify_ControllerInterface $controller instance of subclass of Minify_Controller_Base
     *
     * @param array                      $options    controller/serve options
     *
     * @return null|array if the 'quiet' option is set to true, an array
     * with keys "success" (bool), "statusCode" (int), "content" (string), and
     * "headers" (array).
     *
     * @throws Exception
     */
    public function serve(Minify_ControllerInterface $controller, $options = array())
    {
        $this->env = $controller->getEnv();

        $options = array_merge($this->getDefaultOptions(), $options);

        $config = $controller->createConfiguration($options);

        $this->sources = $config->getSources();
        $this->selectionId = $config->getSelectionId();
        $this->options = $this->analyzeSources($config->getOptions());

        if (!$this->options['quiet'] && !headers_sent()) {
            ini_set('zlib.output_compression', '0');
        }

        // check request validity
        if (!$this->sources) {
            // invalid request!
            if (! $this->options['quiet']) {
                $this->errorExit($this->options['badRequestHeader'], self::URL_DEBUG);
            } else {
                list(,$statusCode) = explode(' ', $this->options['badRequestHeader']);

                return array(
                    'success' => false,
                    'statusCode' => (int)$statusCode,
                    'content' => '',
                    'headers' => array(),
                );
            }
        }

        $this->controller = $controller;

        if ($this->options['debug']) {
            $this->setupDebug();
            $this->options['maxAge'] = 0;
        }

        // determine encoding
        if ($this->options['encodeOutput']) {
            $sendVary = true;
            if ($this->options['encodeMethod'] !== null) {
                // controller specifically requested this
                $contentEncoding = $this->options['encodeMethod'];
            } else {
                // sniff request header
                // depending on what the client accepts, $contentEncoding may be
                // 'x-gzip' while our internal encodeMethod is 'gzip'. Calling
                // getAcceptedEncoding(false, false) leaves out compress and deflate as options.
                $list = HTTP_Encoder::getAcceptedEncoding(false, false);
                list($this->options['encodeMethod'], $contentEncoding) = $list;
                $sendVary = ! HTTP_Encoder::isBuggyIe();
            }
        } else {
            $this->options['encodeMethod'] = ''; // identity (no encoding)
        }

        // check client cache
        $cgOptions = array(
            'lastModifiedTime' => $this->options['lastModifiedTime'],
            'isPublic' => $this->options['isPublic'],
            'encoding' => $this->options['encodeMethod'],
        );

        if ($this->options['maxAge'] > 0) {
            $cgOptions['maxAge'] = $this->options['maxAge'];
        } elseif ($this->options['debug']) {
            $cgOptions['invalidate'] = true;
        }

        $cg = new HTTP_ConditionalGet($cgOptions);
        if ($cg->cacheIsValid) {
            // client's cache is valid
            if (! $this->options['quiet']) {
                $cg->sendHeaders();

                return;
            }

            return array(
                'success' => true,
                'statusCode' => 304,
                'content' => '',
                'headers' => $cg->getHeaders(),
            );
        }

        // client will need output
        $headers = $cg->getHeaders();
        unset($cg);

        if ($this->options['contentType'] === self::TYPE_CSS && $this->options['rewriteCssUris']) {
            $this->setupUriRewrites();
        }

        if ($this->options['concatOnly']) {
            $this->options['minifiers'][self::TYPE_JS] = false;
            foreach ($this->sources as $key => $source) {
                if ($this->options['contentType'] === self::TYPE_JS) {
                    $source->setMinifier('Minify::nullMinifier');
                } elseif ($this->options['contentType'] === self::TYPE_CSS) {
                    $source->setMinifier(array('Minify_CSSmin', 'minify'));
                    $sourceOpts = $source->getMinifierOptions();
                    $sourceOpts['compress'] = false;
                    $source->setMinifierOptions($sourceOpts);
                }
            }
        }

        // check server cache
        if (! $this->options['debug']) {
            // using cache
            // the goal is to use only the cache methods to sniff the length and
            // output the content, as they do not require ever loading the file into
            // memory.
            $cacheId = $this->_getCacheId();
            $fullCacheId = ($this->options['encodeMethod']) ? $cacheId . '.gz' : $cacheId;

            // check cache for valid entry
            $cacheIsReady = $this->cache->isValid($fullCacheId, $this->options['lastModifiedTime']);
            if ($cacheIsReady) {
                $cacheContentLength = $this->cache->getSize($fullCacheId);
            } else {
                // generate & cache content
                try {
                    $content = $this->combineMinify();
                } catch (Exception $e) {
                    $this->logger && $this->logger->critical($e->getMessage());
                    if (! $this->options['quiet']) {
                        $this->errorExit($this->options['errorHeader'], self::URL_DEBUG);
                    }
                    throw $e;
                }
                $this->cache->store($cacheId, $content);
                if (function_exists('gzencode') && $this->options['encodeMethod']) {
                    $this->cache->store($cacheId . '.gz', gzencode($content, $this->options['encodeLevel']));
                }
            }
        } else {
            // no cache
            $cacheIsReady = false;
            try {
                $content = $this->combineMinify();
            } catch (Exception $e) {
                $this->logger && $this->logger->critical($e->getMessage());
                if (! $this->options['quiet']) {
                    $this->errorExit($this->options['errorHeader'], self::URL_DEBUG);
                }
                throw $e;
            }
        }
        if (! $cacheIsReady && $this->options['encodeMethod']) {
            // still need to encode
            $content = gzencode($content, $this->options['encodeLevel']);
        }

        // add headers
        if ($cacheIsReady) {
            $headers['Content-Length'] = $cacheContentLength;
        } else {
            if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
                $headers['Content-Length'] = mb_strlen($content, '8bit');
            } else {
                $headers['Content-Length'] = strlen($content);
            }
        }

        $headers['Content-Type'] = $this->options['contentType'];
        if ($this->options['contentTypeCharset']) {
            $headers['Content-Type'] .= '; charset=' . $this->options['contentTypeCharset'];
        }

        if ($this->options['encodeMethod'] !== '') {
            $headers['Content-Encoding'] = $contentEncoding;
        }
        if ($this->options['encodeOutput'] && $sendVary) {
            $headers['Vary'] = 'Accept-Encoding';
        }

        if (! $this->options['quiet']) {
            // output headers & content
            foreach ($headers as $name => $val) {
                header($name . ': ' . $val);
            }
            if ($cacheIsReady) {
                $this->cache->display($fullCacheId);
            } else {
                echo $content;
            }
        } else {
            return array(
                'success' => true,
                'statusCode' => 200,
                'content' => $cacheIsReady ? $this->cache->fetch($fullCacheId) : $content,
                'headers' => $headers,
            );
        }
    }

    /**
     * Return combined minified content for a set of sources
     *
     * No internal caching will be used and the content will not be HTTP encoded.
     *
     * @param array $sources array of filepaths and/or Minify_Source objects
     *
     * @param array $options (optional) array of options for serve.
     *
     * @return string
     */
    public function combine($sources, $options = array())
    {
        $tmpCache = $this->cache;
        $this->cache = new Minify_Cache_Null();

        $env = new Minify_Env();
        $sourceFactory = new Minify_Source_Factory($env, array(
            'checkAllowDirs' => false,
        ), $this->cache);
        $controller = new Minify_Controller_Files($env, $sourceFactory, $this->logger);

        $options = array_merge($options, array(
            'files' => (array)$sources,
            'quiet' => true,
            'encodeMethod' => '',
            'lastModifiedTime' => 0,
        ));
        $out = $this->serve($controller, $options);

        $this->cache = $tmpCache;

        return $out['content'];
    }

    /**
     * Show an error page
     *
     * @param string $header  Full header. E.g. 'HTTP/1.0 500 Internal Server Error'
     * @param string $url     URL to direct the user to
     * @param string $msgHtml HTML message for the client
     *
     * @return void
     * @internal This is not part of the public API and is subject to change
     * @access private
     */
    public function errorExit($header, $url = '', $msgHtml = '')
    {
        $url = htmlspecialchars($url, ENT_QUOTES);
        list(,$h1) = explode(' ', $header, 2);
        $h1 = htmlspecialchars($h1);
        // FastCGI environments require 3rd arg to header() to be set
        list(, $code) = explode(' ', $header, 3);
        header($header, true, $code);
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>$h1</h1>";
        if ($msgHtml) {
            echo $msgHtml;
        }
        if ($url) {
            echo "<p>Please see <a href='$url'>$url</a>.</p>";
        }
        exit;
    }

    /**
     * Default minifier for .min or -min JS files.
     *
     * @param string $content
     * @return string
     */
    public static function nullMinifier($content)
    {
        if (isset($content[0]) && $content[0] === "\xef") {
            $content = substr($content, 3);
        }
        $content = str_replace("\r\n", "\n", $content);

        return trim($content);
    }

    /**
     * Setup CSS sources for URI rewriting
     */
    protected function setupUriRewrites()
    {
        foreach ($this->sources as $key => $source) {
            $file = $this->env->normalizePath($source->getFilePath());
            $minifyOptions = $source->getMinifierOptions();

            if ($file
                && !isset($minifyOptions['currentDir'])
                && !isset($minifyOptions['prependRelativePath'])) {
                $minifyOptions['currentDir'] = dirname($file);
                $source->setMinifierOptions($minifyOptions);
            }
        }
    }

    /**
     * Set up sources to use Minify_Lines
     */
    protected function setupDebug()
    {
        foreach ($this->sources as $source) {
            $source->setMinifier(array('Minify_Lines', 'minify'));
            $id = $source->getId();
            $source->setMinifierOptions(array(
                'id' => (is_file($id) ? basename($id) : $id),
            ));
        }
    }

    /**
     * Combines sources and minifies the result.
     *
     * @return string
     *
     * @throws Exception
     */
    protected function combineMinify()
    {
        $type = $this->options['contentType']; // ease readability

        // when combining scripts, make sure all statements separated and
        // trailing single line comment is terminated
        $implodeSeparator = ($type === self::TYPE_JS) ? "\n;" : '';

        // allow the user to pass a particular array of options to each
        // minifier (designated by type). source objects may still override
        // these
        if (isset($this->options['minifierOptions'][$type])) {
            $defaultOptions = $this->options['minifierOptions'][$type];
        } else {
            $defaultOptions = array();
        }

        // if minifier not set, default is no minification. source objects
        // may still override this
        if (isset($this->options['minifiers'][$type])) {
            $defaultMinifier = $this->options['minifiers'][$type];
        } else {
            $defaultMinifier = false;
        }

        // process groups of sources with identical minifiers/options
        $content = array();
        $i = 0;
        $l = count($this->sources);
        $groupToProcessTogether = array();
        $lastMinifier = null;
        $lastOptions = null;
        do {
            // get next source
            $source = null;
            if ($i < $l) {
                $source = $this->sources[$i];
                $sourceContent = $source->getContent();

                // allow the source to override our minifier and options
                $minifier = $source->getMinifier();
                if (!$minifier) {
                    $minifier = $defaultMinifier;
                }
                $options = array_merge($defaultOptions, $source->getMinifierOptions());
            }
            // do we need to process our group right now?
            if ($i > 0                               // yes, we have at least the first group populated
                && (
                    ! $source                        // yes, we ran out of sources
                    || $type === self::TYPE_CSS      // yes, to process CSS individually (avoiding PCRE bugs/limits)
                    || $minifier !== $lastMinifier   // yes, minifier changed
                    || $options !== $lastOptions)) { // yes, options changed
                // minify previous sources with last settings
                $imploded = implode($implodeSeparator, $groupToProcessTogether);
                $groupToProcessTogether = array();
                if ($lastMinifier) {
                    try {
                        $content[] = call_user_func($lastMinifier, $imploded, $lastOptions);
                    } catch (Exception $e) {
                        throw new Exception("Exception in minifier: " . $e->getMessage());
                    }
                } else {
                    $content[] = $imploded;
                }
            }
            // add content to the group
            if ($source) {
                $groupToProcessTogether[] = $sourceContent;
                $lastMinifier = $minifier;
                $lastOptions = $options;
            }
            $i++;
        } while ($source);

        $content = implode($implodeSeparator, $content);

        if ($type === self::TYPE_CSS && false !== strpos($content, '@import')) {
            $content = $this->handleCssImports($content);
        }

        // do any post-processing (esp. for editing build URIs)
        if ($this->options['postprocessorRequire']) {
            require_once $this->options['postprocessorRequire'];
        }
        if ($this->options['postprocessor']) {
            $content = call_user_func($this->options['postprocessor'], $content, $type);
        }

        return $content;
    }

    /**
     * Make a unique cache id for for this request.
     *
     * Any settings that could affect output are taken into consideration
     *
     * @param string $prefix
     *
     * @return string
     */
    protected function _getCacheId($prefix = 'minify')
    {
        $name = preg_replace('/[^a-zA-Z0-9\\.=_,]/', '', $this->selectionId);
        $name = preg_replace('/\\.+/', '.', $name);
        $name = substr($name, 0, 100 - 34 - strlen($prefix));
        $md5 = md5(serialize(array(
            Minify_SourceSet::getDigest($this->sources),
            $this->options['minifiers'],
            $this->options['minifierOptions'],
            $this->options['postprocessor'],
            $this->options['bubbleCssImports'],
            Minify::VERSION,
        )));

        return "{$prefix}_{$name}_{$md5}";
    }

    /**
     * Bubble CSS @imports to the top or prepend a warning if an import is detected not at the top.
     *
     * @param string $css
     *
     * @return string
     */
    protected function handleCssImports($css)
    {
        if ($this->options['bubbleCssImports']) {
            // bubble CSS imports
            preg_match_all('/@import.*?;/', $css, $imports);
            $css = implode('', $imports[0]) . preg_replace('/@import.*?;/', '', $css);

            return $css;
        }

        if ('' === $this->options['importWarning']) {
            return $css;
        }

        // remove comments so we don't mistake { in a comment as a block
        $noCommentCss = preg_replace('@/\\*[\\s\\S]*?\\*/@', '', $css);
        $lastImportPos = strrpos($noCommentCss, '@import');
        $firstBlockPos = strpos($noCommentCss, '{');
        if (false !== $lastImportPos
            && false !== $firstBlockPos
            && $firstBlockPos < $lastImportPos
        ) {
            // { appears before @import : prepend warning
            $css = $this->options['importWarning'] . $css;
        }

        return $css;
    }

    /**
     * Analyze sources (if there are any) and set $options 'contentType'
     * and 'lastModifiedTime' if they already aren't.
     *
     * @param array $options options for Minify
     *
     * @return array options for Minify
     */
    protected function analyzeSources($options = array())
    {
        if (!$this->sources) {
            return $options;
        }

        $type = null;
        foreach ($this->sources as $source) {
            $sourceType = $source->getContentType();

            if (!empty($options['contentType'])) {
                // just verify sources have null content type or match the options
                if ($sourceType !== null && $sourceType !== $options['contentType']) {
                    $this->logger && $this->logger->warning("ContentType mismatch: '{$sourceType}' != '{$options['contentType']}'");

                    $this->sources = array();

                    return $options;
                }

                continue;
            }

            if ($type === null) {
                $type = $sourceType;
            } elseif ($sourceType !== $type) {
                $this->logger && $this->logger->warning("ContentType mismatch: '{$sourceType}' != '{$type}'");

                $this->sources = array();

                return $options;
            }
        }

        if (empty($options['contentType'])) {
            if (null === $type) {
                $type = 'text/plain';
            }
            $options['contentType'] = $type;
        }

        // last modified is needed for caching, even if setExpires is set
        if (!isset($options['lastModifiedTime'])) {
            $max = 0;
            foreach ($this->sources as $source) {
                $max = max($source->getLastModified(), $max);
            }
            $options['lastModifiedTime'] = $max;
        }

        return $options;
    }
}
