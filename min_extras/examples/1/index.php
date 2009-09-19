<?php 
require '../../config.php';
require '_groupsSources.php';

$jsBuild = new Minify_Build($groupsSources['js']);
$cssBuild = new Minify_Build($groupsSources['css']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Minify Example 1</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $cssBuild->uri('m.php/css'); ?>" />
     <style type="text/css">#cssFail {width:2.8em; overflow:hidden;}</style>
</head>
<body>

<?php if (! $minifyCachePath): ?>
<p><strong>Note:</strong> You should <em>always</em> enable caching using 
<code>Minify::setCache()</code>. For the examples this can be set in 
<code>config.php</code>.</p>
<?php endif; ?>

<h1>Minify Example 1 : Groups controller + Far-off Expires header</h1>

<p>In this example, we use a single config file <code>_groupsSources.php</code> 
to specify files for minification. During HTML generation, 
<code>Minify_Build</code> is used 
to stamp the latest modification times onto the minify URLs. Our minify server, 
<code>m.php</code>, then sends the content with far-off Expires headers.</p>

<p>If one of our sources is modified, its URL (particularly the query string) is
changed in the HTML document, causing the browser to request a new version.</p>

<h2>Minify tests</h2>
<ul>
    <li id="cssFail"><span>FAIL</span>PASS</li>
    <li id="jsFail1">FAIL</li>
</ul>

<h2>Test client cache</h2>
<p>When you <a href="">click here</a> to reload the page, your browser should 
not have to re-download the minified files.</p>

<p style='text-align:right'><a href="../2/">example 2 &raquo;</a></p>

<script type="text/javascript" src="<?php echo $jsBuild->uri('m.php/js'); ?>"></script>
</body>
</html>