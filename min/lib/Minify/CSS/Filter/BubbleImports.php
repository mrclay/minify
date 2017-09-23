<?php
/**
 * Class Minify_CSS_Filter_BubbleImports
 * @package Minify
 */

/**
 * CSS filter that moves @import statements to the top of the CSS
 * 
 * @package Minify
 */
class Minify_CSS_Filter_BubbleImports {

    /**
     * @param string $css
     * @return string
     */
    public static function filter($css) {

		$importStatements = array();

		$collect = function($matches) use (&$importStatements) {
			$importStatements[] = $matches[0];
			$media = '';
			if (trim($matches[2])) {
				$media = ' for media:' . $matches[2];
			}
			return '/* replaced import "'.$matches[1].'"'.$media.' */';
		};

		$css = preg_replace_callback(Minify_ImportProcessor::IMPORT_STATEMENT_REGEX, $collect, $css);

		if ($importStatements) {
			$css = join("\n", $importStatements) . "\n" . $css;
		}

		return $css;
	}
}