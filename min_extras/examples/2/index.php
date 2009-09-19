<?php 
require '../../config.php';
require '_groupsSources.php';

$jsBuild = new Minify_Build($groupsSources['js']);
$cssBuild = new Minify_Build($groupsSources['css']);

ob_start(); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Minify Example 2</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $cssBuild->uri('m.php/css'); ?>" />
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
<code>Minify::setCache()</code>. For the examples this can be set in 
<code>config.php</code>.</p>
<?php endif; ?>

<h1>Minify Example 2: Minifying <em>Everything</em></h1>

<p>In this example, external Javascript and CSS minification is identical to
example 1, but here Minify is also used to minify and serve the HTML, including
the contents of all <code>&lt;style&gt;</code> and <code>&lt;script&gt;</code> 
elements.</p>

<p>As the document is XHTML, Minify_HTML places the 2nd <code>&lt;script&gt;</code>
element in a CDATA section because it contains "&lt;". The output is valid XHTML.</p>

<h2><a href="http://validator.w3.org/check/referer" 
onclick="this.target='_blank'">Validate XHTML</a></h2>

<h2>Minify tests</h2>
<ul>
    <li id="cssFail"><span>FAIL</span>PASS</li>
    <li id="jsFail1">FAIL</li>
    <li id="jsFail2">FAIL</li>
</ul>

<h2>Test client cache</h2>
<p>When you <a href="">click here</a> to reload the page, your browser should 
not have to re-download any files.</p>

<p style='text-align:right'><a href="../../">extras index &raquo;</a></p>

<script type="text/javascript" src="<?php echo $jsBuild->uri('m.php/js'); ?>"></script>
<script type="text/javascript">

var wo = window.onload;
window.onload = function () {
    wo && wo();
    if ( 1 < 2 ) {
        html('jsFail2', 'PASS');
    }
};

</script>
</body>
</html>
<?php
$content = ob_get_clean();

if ($minifyCachePath) {
    Minify::useServerCache($minifyCachePath);
}

$pageLastUpdate = max(
    filemtime(__FILE__)
    ,$jsBuild->lastModified
    ,$cssBuild->lastModified
);

Minify::serve('Page', array(
    'content' => $content
    ,'id' => __FILE__
    ,'lastModifiedTime' => $pageLastUpdate
    // also minify the CSS/JS inside the HTML 
    ,'minifyAll' => true
));