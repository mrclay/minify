<?php

function getPost($key) {
    return get_magic_quotes_gpc()
        ? stripslashes($_POST[$key])
        : $_POST[$key];
}

if (isset($_POST['textIn'])) {
    require '../config.php';
    $textIn = str_replace("\r\n", "\n", getPost('textIn'));
}

if (isset($_POST['method']) && $_POST['method'] === 'Minify and serve') {
    
    $base = trim(getPost('base'));
    if ($base) {
        $textIn = preg_replace(
            '@(<head\\b[^>]*>)@i'
            ,'$1<base href="' . htmlspecialchars($base, ENT_QUOTES, 'UTF-8') . '" />'
            ,$textIn
        );
    }
    
    $sourceSpec['content'] = $textIn;
    $sourceSpec['id'] = 'foo';
    if (isset($_POST['minJs'])) {
        $sourceSpec['minifyOptions']['jsMinifier'] = array('JSMin', 'minify');
    }
    if (isset($_POST['minCss'])) {
        $sourceSpec['minifyOptions']['cssMinifier'] = array('Minify_CSS', 'minify');
    }
    $source = new Minify_Source($sourceSpec);
    Minify_Logger::setLogger(FirePHP::getInstance(true));
    try {
        Minify::serve('Files', array(
            'files' => $source
            ,'contentType' => Minify::TYPE_HTML
        ));
    } catch (Exception $e) {
        echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
    exit();
}

$classes = array('Minify_HTML', 'Minify_CSS', 'JSMin', 'JSMinPlus');

if (isset($_POST['method']) && in_array($_POST['method'], $classes)) {

    $args = array($textIn);
    if ($_POST['method'] === 'Minify_HTML') {
        $args[] = array(
            'cssMinifier' => array('Minify_CSS', 'minify')
            ,'jsMinifier' => array('JSMin', 'minify')
        );
    }
    $func = array($_POST['method'], 'minify');
    $inOutBytes[0] = strlen($textIn);
    $startTime = microtime(true);
    try {
        $textOut = call_user_func_array($func, $args);
    } catch (Exception $e) {
        echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
    $elapsedTime = microtime(true) - $startTime;
    $inOutBytes[1] = strlen($textOut);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html><head><title>minifyTextarea</title></head>
<?php
if (isset($inOutBytes)) {
    echo "
<table>
    <tr><th>Bytes in</th><td>{$inOutBytes[0]} (after line endings normalized to <code>\\n</code>)</td></tr>
    <tr><th>Bytes out</th><td>{$inOutBytes[1]} (reduced " . round(100 - (100 * $inOutBytes[1] / $inOutBytes[0])) . "%)</td></tr>
    <tr><th>Time (s)</th><td>" . round($elapsedTime, 5) . "</td></tr>
</table>
    ";
}
?>
<form action="?2" method="post">
<p><label>Content<br><textarea name="textIn" cols="80" rows="35" style="width:99%"><?php
if (isset($textOut)) {
    echo htmlspecialchars($textOut, ENT_QUOTES, 'UTF-8');
}
?></textarea></label></p>
<p>Minify with: 
<?php foreach ($classes as $minClass): ?>
    <input type="submit" name="method" value="<?php echo $minClass; ?>">
<?php endForEach; ?>
</p>
<p>...or <input type="submit" name="method" value="Minify and serve"> this HTML to the browser. Also minify: 
<label>CSS <input type="checkbox" name="minCss" checked></label> : 
<label>JS <input type="checkbox" name="minJs" checked></label>. 
<label>Insert BASE element w/ href: <input type="text" name="base" size="20"></label>
</p>
</form>
