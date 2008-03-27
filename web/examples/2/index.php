<?php 
require '../../config.php';
require '_groupsSources.php';

require 'Minify/Build.php';
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
<code>Minify::useServerCache()</code>. For the examples this can be set in 
<code>config.php</code>. Notice that minifying jQuery takes several seconds!.</p>
<?php endif; ?>

<h1>Minify Example 2</h1>

<p>This is an example using Minify_Build and the Groups controller to 
automatically create versioned minify URLs</p>

<ul>
    <li id="cssFail"><span>FAIL</span>PASS</li>
    <li id="jsFail1">FAIL</li>
    <li id="jsFail2">FAIL</li>
</ul>

<p><a href="">Link to this page (F5 can trigger no-cache headers)</a></p>

<script type="text/javascript" src="<?php echo $jsBuild->uri('m.php/js'); ?>"></script>
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