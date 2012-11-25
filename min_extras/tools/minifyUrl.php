<?php
/**
 * Fetch and minify a URL (auto-detect HTML/JS/CSS)
 */

function getPost($key) {
    if (! isset($_POST[$key])) {
        return null;
    }
    return get_magic_quotes_gpc()
        ? stripslashes($_POST[$key])
        : $_POST[$key];
}

function sniffType($headers) {
    $charset = 'utf-8';
    $type = null;
    $headers = "\n\n" . implode("\n\n", $headers) . "\n\n";
    if (preg_match(
            '@\\n\\nContent-Type: *([\\w/\\+-]+)( *; *charset *= *([\\w-]+))? *\\n\\n@i'
            ,$headers
            ,$m)) {
        $sentType = $m[1];
        if (isset($m[3])) {
            $charset = $m[3];
        }
        if (preg_match('@^(?:text|application)/(?:x-)?(?:java|ecma)script$@i', $sentType)) {
            $type = 'application/x-javascript';
        } elseif (preg_match('@^(?:text|application)/(?:html|xml|xhtml+xml)$@i', $sentType, $m)) {
            $type = 'text/html';
        } elseif ($sentType === 'text/css') {
            $type = $sentType;
        }
    }
    return array(
        'minify' => $type
        ,'sent' => $sentType
        ,'charset' => $charset
    );
}

if (isset($_POST['url'])) {
    
    require '../config.php';
    
    $url = trim(getPost('url'));
    $ua = trim(getPost('ua'));
    $cook = trim(getPost('cook'));
    
    if (! preg_match('@^https?://@', $url)) {
        die('HTTP(s) only.');
    }
    
    $httpOpts = array(
        'max_redirects' => 0
        ,'timeout' => 3
    );
    if ($ua !== '') {
        $httpOpts['user_agent'] = $ua;
    }
    if ($cook !== '') {
        $httpOpts['header'] = "Cookie: {$cook}\r\n";
    }
    $ctx = stream_context_create(array(
        'http' => $httpOpts
    ));
    
    // fetch
    if (! ($fp = @fopen($url, 'r', false, $ctx))) {
        die('Couldn\'t open URL.');
    }
    $meta = stream_get_meta_data($fp);
    $content = stream_get_contents($fp);
    fclose($fp);
    
    // get type info
    $type = sniffType($meta['wrapper_data']);
    if (! $type['minify']) {
        die('Unrecognized Content-Type: ' . $type['sent']);
    }
        
    if ($type['minify'] === 'text/html' 
        && isset($_POST['addBase'])
        && ! preg_match('@<base\\b@i', $content)) {
        $content = preg_replace(
            '@(<head\\b[^>]*>)@i'
            ,'$1<base href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />'
            ,$content
        );
    }
    
    $sourceSpec['content'] = $content;
    $sourceSpec['id'] = 'foo';
    
    if ($type['minify'] === 'text/html') {
        if (isset($_POST['minJs'])) {
            $sourceSpec['minifyOptions']['jsMinifier'] = array('JSMin', 'minify');
        }
        if (isset($_POST['minCss'])) {
            $sourceSpec['minifyOptions']['cssMinifier'] = array('Minify_CSS', 'minify');
        }
    }
       
    $source = new Minify_Source($sourceSpec);
    
    $sendType = 'text/plain';
    if ($type['minify'] === 'text/html' && ! isset($_POST['asText'])) {
        $sendType = $type['sent'];
    }
    if ($type['charset']) {
        $sendType .= ';charset=' . $type['charset'];
    }
    header('Content-Type: ' . $sendType);
    // using combine instead of serve because it allows us to specify a
    // Content-Type like application/xhtml+xml IF we need to
    try {
        echo Minify::combine(array($source), array(
            'contentType' => $type['minify']
        ));
    } catch (Exception $e) {
        header('Content-Type: text/html;charset=utf-8');
        echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
    exit();
}

header('Content-Type: text/html; charset=utf-8');

$ua = get_magic_quotes_gpc()
    ? stripslashes($_SERVER['HTTP_USER_AGENT']) 
    : $_SERVER['HTTP_USER_AGENT'];

?>
<!DOCTYPE html><head><title>Minify URL</title></head>

<p><strong>Warning! Please do not place this application on a public site.</strong> This should be used
only for testing.</p>

<h1>Fetch and Minify a URL</h1>
<p>This tool will retrieve the contents of a URL and minify it. 
The fetched resource Content-Type will determine the minifier used.</p>

<form action="?2" method="post">
<p><label>URL: <input type="text" name="url" size="60"></label></p>
<p><input type="submit" value="Fetch and minify"></p>

<fieldset><legend>HTML options</legend>
<p>If the resource above is sent with an (x)HTML Content-Type, the following options will apply:</p>
<ul>
    <li><label><input type="checkbox" name="asText" checked> Return plain text (o/w send the original content type)</label>
    <li><label><input type="checkbox" name="minCss" checked> Minify CSS</label>
    <li><label><input type="checkbox" name="minJs" checked> Minify JS</label>
    <li><label><input type="checkbox" name="addBase" checked> Add BASE element (if not present)</label>
</ul>
</fieldset>

<fieldset><legend>Retreival options</legend>
<ul>
    <li><label>User-Agent: <input type="text" name="ua" size="60" value="<?php echo htmlspecialchars($ua, ENT_QUOTES, 'UTF-8'); ?>"></label>
    <li><label>Cookie: <input type="text" name="cook" size="60"></label>
</ul>
</fieldset>

</form>
