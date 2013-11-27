<?php

function getPost($key) {
    return get_magic_quotes_gpc()
        ? stripslashes($_POST[$key])
        : $_POST[$key];
}

function h($txt) {
    return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
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
            ,'$1<base href="' . h($base) . '" />'
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
        echo h($e->getMessage());
    }
    exit();
}

$tpl = array();
$tpl['classes'] = array('Minify_HTML', 'JSMin', 'Minify_CSS', 'Minify_CSSmin', 'JSMinPlus');

if (isset($_POST['method']) && in_array($_POST['method'], $tpl['classes'])) {

    $args = array($textIn);
    if ($_POST['method'] === 'Minify_HTML') {
        $args[] = array(
            'cssMinifier' => array('Minify_CSS', 'minify')
            ,'jsMinifier' => array('JSMin', 'minify')
        );
    }
    $func = array($_POST['method'], 'minify');
    $tpl['inBytes'] = strlen($textIn);
    $startTime = microtime(true);
    try {
        $tpl['output'] = call_user_func_array($func, $args);
    } catch (Exception $e) {
        $tpl['exceptionMsg'] = getExceptionMsg($e, $textIn);
        $tpl['output'] = $textIn;
        sendPage($tpl);
    }
    $tpl['time'] = microtime(true) - $startTime;
    $tpl['outBytes'] = strlen($tpl['output']);
}

sendPage($tpl);


/**
 * @param Exception $e
 * @param string $input
 * @return string HTML
 */
function getExceptionMsg(Exception $e, $input) {
    $msg = "<p>" . h($e->getMessage()) . "</p>";
    if (0 === strpos(get_class($e), 'JSMin_Unterminated')
            && preg_match('~byte (\d+)~', $e->getMessage(), $m)) {
        $msg .= "<pre>";
        if ($m[1] > 200) {
            $msg .= h(substr($input, ($m[1] - 200), 200));
        } else {
            $msg .= h(substr($input, 0, $m[1]));
        }
        $highlighted = isset($input[$m[1]]) ? h($input[$m[1]]) : '&#9220;';
        if ($highlighted === "\n") {
            $highlighted = "&#9166;\n";
        }
        $msg .= "<span style='background:#c00;color:#fff'>$highlighted</span>";
        $msg .= h(substr($input, $m[1] + 1, 200)) . "</span></pre>";
    }
    return $msg;
}

/**
 * Draw page
 *
 * @param array $vars
 */
function sendPage($vars) {
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html><head><title>minifyTextarea</title></head>
<?php
if (isset($vars['exceptionMsg'])) {
    echo $vars['exceptionMsg'];
}
if (isset($vars['time'])) {
    echo "
<table>
    <tr><th>Bytes in</th><td>{$vars['inBytes']} (after line endings normalized to <code>\\n</code>)</td></tr>
    <tr><th>Bytes out</th><td>{$vars['outBytes']} (reduced " . round(100 - (100 * $vars['outBytes'] / $vars['inBytes'])) . "%)</td></tr>
    <tr><th>Time (s)</th><td>" . round($vars['time'], 5) . "</td></tr>
</table>
    ";
}
?>
<form action="?2" method="post">
<p><label>Content<br><textarea name="textIn" cols="80" rows="35" style="width:99%"><?php
if (isset($vars['output'])) {
    echo h($vars['output']);
}
?></textarea></label></p>
<p>Minify with: 
<?php foreach ($vars['classes'] as $minClass): ?>
    <input type="submit" name="method" value="<?php echo $minClass; ?>">
<?php endForEach; ?>
</p>
<p>...or <input type="submit" name="method" value="Minify and serve"> this HTML to the browser. Also minify: 
<label>CSS <input type="checkbox" name="minCss" checked></label> : 
<label>JS <input type="checkbox" name="minJs" checked></label>. 
<label>Insert BASE element w/ href: <input type="text" name="base" size="20"></label>
</p>
</form>
<?php if (isset($vars['selectByte'])) { ?>
<script>
function selectText(el, begin, end) {
    var len = el.value.length;
    end = end || len;
    if (begin == null) {
        el.select();
    } else {
        if (el.setSelectionRange) {
            el.setSelectionRange(begin, end);
        } else {
            if (el.createTextRange) {
                var tr = el.createTextRange()
                    ,c = "character";
                tr.moveStart(c, begin);
                tr.moveEnd(c, end - len);
                tr.select();
            } else {
                el.select();
            }
        }
    }
    el.focus();
}
window.onload = function () {
    var ta = document.querySelector('textarea[name="textIn"]');
    selectText(ta, <?= $vars['selectByte'] ?>, <?= ($vars['selectByte'] + 1) ?>);
};
</script>
<?php }
    exit;
}
