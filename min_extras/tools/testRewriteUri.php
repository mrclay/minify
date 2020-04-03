<?php
die('Disabled: use this only for testing');

$app = (require __DIR__ . '/../../bootstrap.php');
/* @var \Minify\App $app */

$env = $app->env;

header('Content-Type: text/html;charset=utf-8');

function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

function getInput($name, $default = '', $size = 50)
{
    global $env;
    $val = $env->post($name, $default);
    return "<input type='text' name='{$name}' value='" . h($val) . "' size='{$size}' />";
}

$defaultCurrentDir = __DIR__;
$defaultDocRoot = realpath($env->getDocRoot());
$defaultSymLink = '//symlinkPath';
$defaultSymTarget = ($defaultCurrentDir[0] === '/') ? '/tmp' : 'C:\\WINDOWS\\Temp';
$defaultCss = "url(hello.gif)\nurl(../hello.gif)\nurl(../../hello.gif)\nurl(up/hello.gif)";

$out = '';

if ($env->post('css')) {
    $symlinks = array();
    if ('' !== ($target = $env->post('symTarget'))) {
        $symlinks[$env->post('symLink')] = $target;
    }
    $css = Minify_CSS_UriRewriter::rewrite(
        $env->post('css'),
        $env->post('currentDir'),
        $env->post('docRoot'),
        $symlinks
    );
    $out = "<hr /><pre><code>" . h($css) . '</code></pre>';
}

?>
<h1>Test <code>Minify_CSS_UriRewriter::rewrite()</code></h1>
<p><strong>Warning! Please do not place this application on a public site.</strong> This should be used only for testing.</p>
<form action="" method="post">
<div><label>document root: <?php echo getInput('docRoot', $defaultDocRoot); ?></label></div>
<div><label>symlink: <?php echo getInput('symLink', $defaultSymLink); ?> => <?php echo getInput('symTarget', $defaultSymTarget); ?></label></div>
<div><label>current directory: <?php echo getInput('currentDir', $defaultCurrentDir); ?></label></div>
<p><label>input CSS: <textarea name="css" cols="80" rows="5"><?php echo h($env->post('css', $defaultCss)); ?></textarea></label></p>
<p><input type="submit" value="rewrite()" /></p>
</form>
<?php echo $out; ?>
