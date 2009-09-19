<?php
header('Content-Type: text/html;charset=utf-8');

function h($str) { return htmlspecialchars($str, ENT_QUOTES); }

function getPost($name, $default = '') { return isset($_POST[$name]) ? $_POST[$name] : $default; }

function getInput($name, $default = '', $size = 50) {
    $val = h(isset($_POST[$name]) ? $_POST[$name] : $default);
    return "<input type='text' name='{$name}' value='{$val}' size='{$size}' />";
}

// validate user POST (no arrays and fix slashes)
if (! empty($_POST)) {
    foreach ($_POST as $name => $val) {
        if (! is_string($val)) {
            unset($_POST[$name]);
            continue;
        }
        if (get_magic_quotes_gpc()) {
            $_POST[$name] = stripslashes($val);
        }
    }
}

$defaultCurrentDir = dirname(__FILE__);
$defaultDocRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$defaultSymLink = '//symlinkPath';
$defaultSymTarget = ($defaultCurrentDir[0] === '/') ? '/tmp' : 'C:\\WINDOWS\\Temp';
$defaultCss = "url(hello.gif)\nurl(../hello.gif)\nurl(../../hello.gif)\nurl(up/hello.gif)";

$out = '';

if (isset($_POST['css'])) {
    require '../config.php';
    $symlinks = array();
    if ('' !== ($target = getPost('symTarget'))) {
        $symlinks[getPost('symLink')] = $target;
    }
    $css = Minify_CSS_UriRewriter::rewrite(
          getPost('css')
        , getPost('currentDir')
        , getPost('docRoot')
        , $symlinks
    );
    $out = "<hr /><pre><code>" . h($css) . '</code></pre>';
}

?>
<h1>Test <code>Minify_CSS_UriRewriter::rewrite()</code></h1>
<form action="" method="post">
<div><label>document root: <?php echo getInput('docRoot', $defaultDocRoot); ?></label></div>
<div><label>symlink: <?php echo getInput('symLink', $defaultSymLink); ?> => <?php echo getInput('symTarget', $defaultSymTarget); ?></label></div>
<div><label>current directory: <?php echo getInput('currentDir', $defaultCurrentDir); ?></label></div>
<p><label>input CSS: <textarea name="css" cols="80" rows="5"><?php echo h(getPost('css', $defaultCss)); ?></textarea></label></p>
<p><input type="submit" value="rewrite()" /></p>
</form>
<?php echo $out; ?>