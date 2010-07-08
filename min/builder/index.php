<?php 

if (phpversion() < 5) {
    exit('Minify requires PHP5 or greater.');
}

// check for auto-encoding
$encodeOutput = (function_exists('gzdeflate')
                 && !ini_get('zlib.output_compression'));

// recommend $min_symlinks setting for Apache UserDir
$symlinkOption = '';
if (0 === strpos($_SERVER["SERVER_SOFTWARE"], 'Apache/')
    && preg_match('@^/\\~(\\w+)/@', $_SERVER['REQUEST_URI'], $m)
) {
    $userDir = DIRECTORY_SEPARATOR . $m[1] . DIRECTORY_SEPARATOR;
    if (false !== strpos(__FILE__, $userDir)) {
        $sm = array();
        $sm["//~{$m[1]}"] = dirname(dirname(__FILE__));
        $array = str_replace('array (', 'array(', var_export($sm, 1));
        $symlinkOption = "\$min_symlinks = $array;";
    }
}

require dirname(__FILE__) . '/../config.php';

if (! $min_enableBuilder) {
    header('Location: /');
    exit();
}

$setIncludeSuccess = set_include_path(dirname(__FILE__) . '/../lib' . PATH_SEPARATOR . get_include_path());
// we do it this way because we want the builder to work after the user corrects
// include_path. (set_include_path returning FALSE is OK).
try {
    require_once 'Solar/Dir.php';    
} catch (Exception $e) {
    if (! $setIncludeSuccess) {
        echo "Minify: set_include_path() failed. You may need to set your include_path "
            ."outside of PHP code, e.g., in php.ini.";    
    } else {
        echo $e->getMessage();
    }
    exit();
}
require 'Minify.php';

$cachePathCode = '';
if (! isset($min_cachePath)) {
    $detectedTmp = rtrim(Solar_Dir::tmp(), DIRECTORY_SEPARATOR);
    $cachePathCode = "\$min_cachePath = " . var_export($detectedTmp, 1) . ';';
}

ob_start();
?>
<!DOCTYPE HTML>
<title>Minify URI Builder</title>
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<style>
body {margin:1em 60px;}
h1, h2, h3 {margin-left:-25px; position:relative;}
h1 {margin-top:0;}
#sources {margin:0; padding:0;}
#sources li {margin:0 0 0 40px}
#sources li input {margin-left:2px}
#add {margin:5px 0 1em 40px}
.hide {display:none}
#uriTable {border-collapse:collapse;}
#uriTable td, #uriTable th {padding-top:10px;}
#uriTable th {padding-right:10px;}
#groupConfig {font-family:monospace;}
b {color:#c00}
.topNote {background: #ff9; display:inline-block; padding:.5em .6em; margin:0 0 1em;}
.topWarning {background:#c00; color:#fff; padding:.5em .6em; margin:0 0 1em;}
.topWarning a {color:#fff;}
</style>
<body>
<?php if ($symlinkOption): ?>
    <div class=topNote><strong>Note:</strong> It looks like you're running Minify in a user
 directory. You may need the following option in /min/config.php to have URIs
 correctly rewritten in CSS output:
 <br><textarea id=symlinkOpt rows=3 cols=80 readonly><?php echo htmlspecialchars($symlinkOption); ?></textarea>
</div>
<?php endif; ?>

<p class=topWarning id=jsDidntLoad><strong>Uh Oh.</strong> Minify was unable to
    serve Javascript for this app. To troubleshoot this,
    <a href="http://code.google.com/p/minify/wiki/Debugging">enable FirePHP debugging</a>
    and request the <a id=builderScriptSrc href=#>Minify URL</a> directly. Hopefully the
    FirePHP console will report the cause of the error.
</p>

<?php if ($cachePathCode): ?>
<p class=topNote><strong>Note:</strong> <code><?php echo
    htmlspecialchars($detectedTmp); ?></code> was discovered as a usable temp directory.<br>To
    slightly improve performance you can hardcode this in /min/config.php:
    <code><?php echo htmlspecialchars($cachePathCode); ?></code></p>
<?php endIf; ?>

<p id=minRewriteFailed class="hide"><strong>Note:</strong> Your webserver does not seem to
 support mod_rewrite (used in /min/.htaccess). Your Minify URIs will contain "?", which 
<a href="http://www.stevesouders.com/blog/2008/08/23/revving-filenames-dont-use-querystring/"
>may reduce the benefit of proxy cache servers</a>.</p>

<h1>Minify URI Builder</h1>

<noscript><p class="topNote">Javascript and a browser supported by jQuery 1.2.6 is required
for this application.</p></noscript>

<div id=app class=hide>

<p>Create a list of Javascript or CSS files (or 1 is fine) you'd like to combine
and click [Update].</p>

<ol id=sources><li></li></ol>
<div id=add><button>Add file +</button></div>

<div id=bmUris></div>

<p><button id=update class=hide>Update</button></p>

<div id=results class=hide>

<h2>Minify URI</h2>
<p>Place this URI in your HTML to serve the files above combined, minified, compressed and
with cache headers.</p>
<table id=uriTable>
    <tr><th>URI</th><td><a id=uriA class=ext>/min</a> <small>(opens in new window)</small></td></tr>
    <tr><th>HTML</th><td><input id=uriHtml type=text size=100 readonly></td></tr>
</table>

<h2>How to serve these files as a group</h2>
<p>For the best performance you can serve these files as a pre-defined group with a URI
like: <code><span class=minRoot>/min/?</span>g=keyName</code></p>
<p>To do this, add a line like this to /min/groupsConfig.php:</p>

<pre><code>return array(
    <span style="color:#666">... your existing groups here ...</span>
<input id=groupConfig size=100 type=text readonly>
);</code></pre>

<p><em>Make sure to replace <code>keyName</code> with a unique key for this group.</em></p>
</div>

<div id=getBm>
<h3>Find URIs on a Page</h3>
<p>You can use the bookmarklet below to fetch all CSS &amp; Javascript URIs from a page
on your site. When you active it, this page will open in a new window with a list of
available URIs to add.</p>

<p><a id=bm>Create Minify URIs</a> <small>(right-click, add to bookmarks)</small></p>
</div>

<h3>Combining CSS files that contain <code>@import</code></h3>
<p>If your CSS files contain <code>@import</code> declarations, Minify will not 
remove them. Therefore, you will want to remove those that point to files already
in your list, and move any others to the top of the first file in your list 
(imports below any styles will be ignored by browsers as invalid).</p>
<p>If you desire, you can use Minify URIs in imports and they will not be touched
by Minify. E.g. <code>@import "<span class=minRoot>/min/?</span>g=css2";</code></p>

<h3>Debug Mode</h3>
<p>When /min/config.php has <code>$min_allowDebugFlag = <strong>true</strong>;</code>
 you can get debug output by appending <code>&amp;debug</code> to a Minify URL, or
 by sending the cookie <code>minDebug=&lt;match&gt;</code>, where <code>&lt;match&gt;</code>
 should be a string in the Minify URIs you'd like to debug. This bookmarklet will allow you to
 set this cookie.</p>
<p><a id=bm2>Minify Debug</a> <small>(right-click, add to bookmarks)</small></p>

</div><!-- #app -->

<hr>
<p>Need help? Check the <a href="http://code.google.com/p/minify/w/list?can=3">wiki</a>,
 or post to the <a class=ext href="http://groups.google.com/group/minify">discussion
 list</a>.</p>
 <p><small>Powered by Minify <?php echo Minify::VERSION; ?></small></p>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
<script>
$(function () {
    // give Minify a few seconds to serve _index.js before showing scary red warning
    $('#jsDidntLoad').hide();
    setTimeout(function () {
        if (! window.MUB) {
            // Minify didn't load
            $('#jsDidntLoad').show();
        }
    }, 3000);

    // detection of double output encoding
    var msg = '<\p class=topWarning><\strong>Warning:<\/strong> ';
    var url = 'ocCheck.php?' + (new Date()).getTime();
    $.get(url, function (ocStatus) {
        $.get(url + '&hello=1', function (ocHello) {
            if (ocHello != 'World!') {
                msg += 'It appears output is being automatically compressed, interfering ' 
                     + ' with Minify\'s own compression. ';
                if (ocStatus == '1')
                    msg += 'The option "zlib.output_compression" is enabled in your PHP configuration. '
                         + 'Minify set this to "0", but it had no effect. This option must be disabled ' 
                         + 'in php.ini or .htaccess.';
                else
                    msg += 'The option "zlib.output_compression" is disabled in your PHP configuration '
                         + 'so this behavior is likely due to a server option.';
                $(document.body).prepend(msg + '<\/p>');
            } else
                if (ocStatus == '1')
                    $(document.body).prepend('<\p class=topNote><\strong>Note:</\strong> The option '
                        + '"zlib.output_compression" is enabled in your PHP configuration, but has been '
                        + 'successfully disabled via ini_set(). If you experience mangled output you '
                        + 'may want to consider disabling this option in your PHP configuration.<\/p>'
                    );
        });
    });
});
</script>
<script>
// workaround required to test when /min isn't child of web root
var src = location.pathname.replace(/\/[^\/]*$/, '/_index.js').substr(1);
src = "../?f=" + src;
document.write('<\script type="text/javascript" src="' + src + '"><\/script>');
$(function () {
    $('#builderScriptSrc')[0].href = src;
});
</script>
</body>
<?php
$content = ob_get_clean();

// setup Minify
if (0 === stripos(PHP_OS, 'win')) {
    Minify::setDocRoot(); // we may be on IIS
}
Minify::setCache(
    isset($min_cachePath) ? $min_cachePath : ''
    ,$min_cacheFileLocking
);
Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;

Minify::serve('Page', array(
    'content' => $content
    ,'id' => __FILE__
    ,'lastModifiedTime' => max(
        // regenerate cache if any of these change
        filemtime(__FILE__)
        ,filemtime(dirname(__FILE__) . '/../config.php')
        ,filemtime(dirname(__FILE__) . '/../lib/Minify.php')
    )
    ,'minifyAll' => true
    ,'encodeOutput' => $encodeOutput
));
