<?php
/**
 * Class Minify_Processor  
 * @package Minify
 */

/**
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Processor {
    
    /**
     * @throws Exception
     * @param Minify_Controller_Base $controller
     * @param array $options
     * @param string $importWarning
     * @return string
     */
    public static function process(Minify_Controller_Base $controller, array $options, $importWarning)
    {
        $type = $options['contentType']; // ease readability
        
        // when combining scripts, make sure all statements separated and
        // trailing single line comment is terminated
        $implodeSeparator = ($type === Minify::TYPE_JS)
            ? "\n;"
            : '';
        // allow the user to pass a particular array of options to each
        // minifier (designated by type). source objects may still override
        // these
        $defaultOptions = isset($options['minifierOptions'][$type])
            ? $options['minifierOptions'][$type]
            : array();
        // if minifier not set, default is no minification. source objects
        // may still override this
        $defaultMinifier = isset($options['minifiers'][$type])
            ? $options['minifiers'][$type]
            : false;

        // process groups of sources with identical minifiers/options
        $content = array();
        $i = 0;
        $l = count($controller->sources);
        $groupToProcess = array();
        $bytesInGroup = - strlen($implodeSeparator);
        $lastMinifier = null;
        $maxBytes = null; // maximum bytes processable by the minifier
        $lastMinifierOptions = null;
        do {
            // get next source
            $source = null;
            if ($i < $l) {
                $source = $controller->sources[$i];
                /* @var Minify_Source $source */
                $sourceContent = $source->getContent();

                // allow the source to override our minifier and options
                $minifier = (null !== $source->minifier)
                    ? $source->minifier
                    : $defaultMinifier;
                $minifierOptions = (null !== $source->minifyOptions)
                    ? array_merge($defaultOptions, $source->minifyOptions)
                    : $defaultOptions;
                $bytesIfAdded = $bytesInGroup + strlen($sourceContent) + strlen($implodeSeparator);
            }
            // do we need to process our group right now?
            if ($i > 0                               // yes, we have at least the first group populated
                && (
                    ! $source                        // yes, we ran out of sources
                    || $type === Minify::TYPE_CSS      // yes, to process CSS individually (avoiding PCRE bugs/limits)
                    || $minifier !== $lastMinifier   // yes, minifier changed
                    || $minifierOptions !== $lastMinifierOptions     // yes, options changed
                    || ($maxBytes && ($bytesIfAdded > $maxBytes)) // yes, source's content would push us over limit
                   )
                )
            {
                // minify previous sources with last settings
                $imploded = implode($implodeSeparator, $groupToProcess);
                $groupToProcess = array();
                $bytesInGroup = - strlen($implodeSeparator);
                $maxBytes = null;
                if ($lastMinifier) {
                    try {
                        $content[] = call_user_func($lastMinifier, $imploded, $lastMinifierOptions);
                    } catch (Exception $e) {
                        throw new Exception("Exception in minifier: " . $e->getMessage());
                    }
                } else {
                    $content[] = $imploded;
                }
            }
            // add content to the group
            if ($source) {
                $groupToProcess[] = $sourceContent;
                $bytesInGroup = $bytesIfAdded;
                $lastMinifier = $minifier;
                $lastMinifierOptions = $minifierOptions;
                if ($maxBytes === null) {
                    $maxBytes = 0;
                    if ($minifier) {
                        // this sucks, but since minifiers are likely callbacks, this is the only way to
                        // sniff them for the getMaxBytes function
                        $controller->loadMinifier($minifier);
                        $obj = '';
                        if (is_array($minifier) && count($minifier) === 2) {
                            $obj = $minifier[0];
                        } elseif (is_string($minifier) && false !== strpos($minifier, '::')) {
                            list ($obj) = explode('::', $minifier);
                        }
                        // thankfully whether $obj is class name or object, this code works fine
                        if ($obj && method_exists($obj, 'getMaxBytes')) {
                            $maxBytes = call_user_func(array($obj, 'getMaxBytes'));
                        }
                    }
                }
            }
            $i++;
        } while ($source);

        $content = implode($implodeSeparator, $content);
        
        if ($type === Minify::TYPE_CSS
            && false !== strpos($content, '@import')
            ) {
            $content = self::_handleCssImports($content, $options, $importWarning);
        }
        
        // do any post-processing (esp. for editing build URIs)
        if ($options['postprocessorRequire']) {
            require_once $options['postprocessorRequire'];
        }
        if ($options['postprocessor']) {
            $content = call_user_func($options['postprocessor'], $content, $type);
        }
        return $content;
    }

    /**
     * Bubble CSS @imports to the top or prepend a warning if an @import is detected not at the top.
     * @param string $css
     * @param array $options
     * @param string $importWarning
     * @return string
     */
    protected static function _handleCssImports($css, $options, $importWarning)
    {
        if ($options['bubbleCssImports']) {
            // bubble CSS imports
            preg_match_all('/@import.*?;/', $css, $imports);
            $css = implode('', $imports[0]) . preg_replace('/@import.*?;/', '', $css);
        } else if ('' !== $importWarning) {
            // remove comments so we don't mistake { in a comment as a block
            $noCommentCss = preg_replace('@/\\*[\\s\\S]*?\\*/@', '', $css);
            $lastImportPos = strrpos($noCommentCss, '@import');
            $firstBlockPos = strpos($noCommentCss, '{');
            if (false !== $lastImportPos
                && false !== $firstBlockPos
                && $firstBlockPos < $lastImportPos
            ) {
                // { appears before @import : prepend warning
                $css = $importWarning . $css;
            }
        }
        return $css;
    }
}
