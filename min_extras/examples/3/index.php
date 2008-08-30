<?php 
require '../../config.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Minify Example 3</title>
    <link rel="stylesheet" type="text/css" href="m.php?f=test.css" />
    <style type="text/css">#cssFail {width:2.8em; overflow:hidden;}</style>
</head>
<body>

<?php if (! $minifyCachePath): ?>
<p><strong>Note:</strong> You should <em>always</em> enable caching using 
<code>Minify::useServerCache()</code>. For the examples this can be set in 
<code>config.php</code>. Notice that minifying jQuery takes several seconds!.</p>
<?php endif; ?>

<h1>Minify Example 3: Files controller</h1>

<p>This is an example of Minify serving a directory of single css/js files. 
Each file is minified and sent with HTTP encoding (browser-permitting).</p>

<h2>Minify tests</h2>
<ul>
    <li id="cssFail"><span>FAIL</span>PASS</li>
    <li id="jsFail1">FAIL</li>
</ul>

<h2>Test client cache</h2>
<p><a href="">Reload page</a> <small>(F5 can trigger no-cache headers)</small></p>

<script type="text/javascript" src="m.php?f=jquery-1.2.3.js"></script>
<script type="text/javascript" src="m.php?f=test+space.js"></script>
</body>
</html>