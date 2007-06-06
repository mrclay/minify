<?php
class HTMLMin {
  // -- Public Static Methods --------------------------------------------------
  public static function minify($string) {
    $htmlmin = new HTMLMin($string);
    return $htmlmin->getMinifiedHtml();
  }

  // -- Private Instance Variables ---------------------------------------------
  private $input;

  // -- Private Instance Methods -----------------------------------------------
  private function replaceCSS($matches) {
    // Remove HTML comment markers from the CSS (they shouldn't be there
    // anyway).
    $css = preg_replace('/<!--([\s\S]*?)-->/', "$1", $matches[2]);
    
    return '<style'.$matches[1].'>'.trim(Minify::min($css, Minify::TYPE_CSS)).
        '</style>';
  }

  private function replaceJavaScript($matches) {
    // Remove HTML comment markers from the JS (they shouldn't be there anyway).
    $js = preg_replace('/<!--([\s\S]*?)-->/', "$1", $matches[2]);

    return '<script'.$matches[1].'>'.trim(Minify::min($js, Minify::TYPE_JS)).
        '</script>';
  }

  // -- Public Instance Methods ------------------------------------------------
  public function __construct($input = '') {
    $this->setInput($input);
  }

  public function getInput() {
    return $this->input;
  }

  public function getMinifiedHtml() {
    $html = trim($this->input);

    // Run JavaScript blocks through JSMin.
    $html = preg_replace_callback('/<script(\s+[\s\S]*?)?>([\s\S]*?)<\/script>/i',
        array($this, 'replaceJavaScript'), $html);

    // Run CSS blocks through Minify's CSS minifier.
    $html = preg_replace_callback('/<style(\s+[\s\S]*?)?>([\s\S]*?)<\/style>/i',
        array($this, 'replaceCSS'), $html);

    // Remove HTML comments (but not IE conditional comments).
    $html = preg_replace('/<!--[^[][\s\S]*?-->/', '', $html);

    // Remove leading and trailing whitespace from each line.
    // FIXME: This needs to take into account attribute values that span multiple lines.
    $html = preg_replace('/^\s*(.*?)\s*$/m', "$1", $html);
    
    // Remove unnecessary whitespace between and inside elements.
    $html = preg_replace('/>\s+(\S[\s\S]*?)?</', "> $1<", $html);
    $html = preg_replace('/>(\S[\s\S]*?)?\s+</', ">$1 <", $html);
    $html = preg_replace('/>\s+</', "> <", $html);
    
    return $html;
  }

  public function setInput($input) {
    $this->input = $input;
  }
}
?>