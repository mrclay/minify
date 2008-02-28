<?php 
require '../config.php';
ob_start(); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Minify Example 1</title>
    <link rel="stylesheet" type="text/css" href="m.php?f=test.css&amp;v=3" />
     <style type="text/css">
#cssFail {
    width:2.8em; 
    overflow:hidden;
}
     </style>
</head>
<body>

<?php if (! $minifyCachePath): ?>
<p><strong>Note:</strong> You should <em>always</em> enable caching using 
<code>Minify::useServerCache()</code>. For the examples this can be set in 
<code>config.php</code>. Notice that minifying jquery.js takes several seconds!.</p>
<?php endif; ?>

<h1>Minify Example 1</h1>

<p>This is an example of Minify serving a directory of single css/js files. 
Each file is minified and sent with HTTP encoding (browser-permitting). </p>

<ul>
    <li id="cssFail"><span>FAIL</span>PASS</li>
    <li id="jsFail1">FAIL</li>
    <li id="jsFail2">FAIL</li>
</ul>

<p><a href="">Link to this page (F5 can trigger no-cache headers)</a></p>

<script type="text/javascript" src="m.php?f=jquery-1.2.3.js&amp;v=1"></script>
<script type="text/javascript" src="m.php?f=test+space.js"></script>
<script type="text/javascript">
$(function () {
    if ( 1 < 2 ) {
        $('#jsFail2').html('PASS');
    }
});
</script>
</body>
</html>
<?php
$content = ob_get_clean();

require 'Minify.php';

if ($minifyCachePath) {
    Minify::useServerCache($minifyCachePath);
}

Minify::serve('Page', array(
    'content' => $content
    ,'id' => __FILE__
    ,'lastModifiedTime' => filemtime(__FILE__)
    
    // also minify the CSS/JS inside the HTML 
    ,'minifyAll' => true
));