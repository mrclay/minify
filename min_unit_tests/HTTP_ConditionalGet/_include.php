<?php

function send_slowly($content)
{
    $half = ceil(strlen($content) / 2);
    $content = str_split($content, $half);
    while ($chunk = array_shift($content)) {
        sleep(1);
        echo $chunk;
        ob_get_level() && ob_flush();
        flush();
    }
}

function get_content($data)
{
    ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>HTTP_ConditionalGet : <?php echo $data['title']; ?></title>
</head>
<body>
<h1>HTTP_ConditionalGet</h1>
<h2><?php echo $data['title']; ?></h2>
<?php echo $data['explain']; ?>
<ul>
	<li><a href="./">Last-Modified is known : simple usage</a></li>
	<li><a href="2.php">Last-Modified is known : add Content-Length</a></li>
	<li><a href="3.php">Last-Modified is unknown : use hash of content for ETag</a></li>
	<li><a href="4.php">ConditionalGet + Encoder</a></li>
	<li><a href="5.php">Last-Modified + Expires</a></li>
</ul>
<h2>Notes</h2>
<h3>How to distinguish 200 and 304 responses</h3>
<p>For these pages all 200 responses are sent in chunks a second apart, so you
should notice that 304 responses are quicker. You can also use HTTP sniffers
like <a href="http://www.fiddlertool.com/">Fiddler (win)</a> and
<a href="http://livehttpheaders.mozdev.org/">LiveHTTPHeaders (Firefox add-on)</a>
to verify headers and content being sent.</p>
<h3>Browser notes</h3>
<dl>
	<dt>Opera</dt>
	<dd>Opera has a couple behaviors against the HTTP spec: Manual refreshes (F5)
    prevents the ETag/If-Modified-Since headers from being sent; it only sends
    them when following a link or bookmark. Also, Opera will not honor the
    <code>must-revalidate</code> Cache-Control value unless <code>max-age</code>
    is set. To get Opera to follow the spec, ConditionalGet will send Opera max-age=0
    (if one is not already set).</dd>
	<dt>Safari</dt>
	<dd>ETag validation is unsupported, but Safari supports HTTP/1.0 validation via
		 If-Modified-Since headers as long as the cache is explicitly marked
		"public" or "private" ("private" is default in ConditionalGet).</dd>
</dl>
</body>
</html>
<?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

