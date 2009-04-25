<?php

$classes = array('Minify_HTML', 'Minify_CSS', 'JSMin', 'JSMinPlus');
header('Content-Type: text/html;charset=UTF-8');

if (isset($_POST['textIn']) && in_array($_POST['method'], $classes)) {
    require '../config.php';
    
    $textIn = get_magic_quotes_gpc()
        ? stripslashes($_POST['textIn'])
        : $_POST['textIn'];
    
    $textIn = str_replace("\r\n", "\n", $textIn);
    
    // easier to just require them all
    require 'Minify/HTML.php';
    require 'Minify/CSS.php';
    require 'JSMin.php';
    require 'JSMinPlus.php';

    $arg2 = null;
    if ($_POST['method'] === 'Minify_HTML') {
        $arg2 = array(
            'cssMinifier' => array('Minify_CSS', 'minify')
            ,'jsMinifier' => array('JSMin', 'minify')
        );
    }
    $func = array($_POST['method'], 'minify');
    $inOutBytes[0] = strlen($textIn);
    $textOut = call_user_func($func, $textIn, $arg2);
    $inOutBytes[1] = strlen($textOut);
}

if (isset($inOutBytes)) {
    echo "
<table>
    <tr><th>Bytes in</th><td>{$inOutBytes[0]} (after line endings normalized to <code>\\n</code>)</td></tr>
    <tr><th>Bytes out</th><td>{$inOutBytes[1]} (" . round(100 * $inOutBytes[1] / $inOutBytes[0]) . "%)</td></tr>
</table>
    ";
}

?>
<form action="" method="post">
<p><label>Content<br /><textarea name="textIn" cols="80" rows="35" style="width:99%"><?php
if (isset($textOut)) {
    echo htmlspecialchars($textOut);
}
?></textarea></label></p>
<p>Minify with: 
<?php foreach ($classes as $minClass): ?>
    <input type="submit" name="method" value="<?php echo $minClass; ?>" />
<?php endForEach; ?>
</p>
</form>
