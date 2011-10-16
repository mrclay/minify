<?php
/**
 * Class MrClay_Pattern
 * @package Minify
 */

/**
 * Extremely incomplete port of java.util.regex.Pattern
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class MrClay_Pattern {
    public $pattern = '';
    public $capturingGroupCount = 0;

    public function __construct($pattern, $numGroups)
    {
        $this->pattern = $pattern;
        $this->capturingGroupCount = $numGroups;
    }

    public function matcher($input)
    {
        return new MrClay_Matcher($this, $input);
    }
}
