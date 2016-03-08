Unless mentioned, all the following snippets go in `min/config.php`.

## Faster Cache Performance

By default, Minify uses [Minify\_Cache\_File](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify/Cache/File.php). It uses `readfile`/`fpassthru` to improve performance over most file-based systems, but it's still file IO. I haven't done comparative benchmarks on all three, but APC/Memcache _should_ be faster. In all cases, Minify cache ids begin with `"minify_"`.

### APC

```
require 'lib/Minify/Cache/APC.php';
$min_cachePath = new Minify_Cache_APC();
```

### Memcache

You must create and connect your Memcache object then pass it to `Minify_Cache_Memcache`'s constructor.
```
require 'lib/Minify/Cache/Memcache.php';
$memcache = new Memcache;
$memcache->connect('localhost', 11211);
$min_cachePath = new Minify_Cache_Memcache($memcache);
```

### Zend Platform

Patrick van Dissel has contributed a [cache adapter for Zend Platform](http://code.google.com/p/minify/issues/detail?id=167).

## Closure Compiler API Wrapper

An [experimental wrapper for Google's closure compiler API](https://github.com/mrclay/minify/blob/master/min/lib/Minify/JS/ClosureCompiler.php) is available for compressing Javascript. If the API fails for any reason, JSMin is used as the default backup minifier.
```
$min_serveOptions['minifiers']['application/x-javascript'] = array('Minify_JS_ClosureCompiler', 'minify');
```

## YUICompressor

If your host can execute Java, you can use Minify's YUI Compressor wrapper. You'll need the latest [yuicompressor-x.x.x.jar](http://yuilibrary.com/downloads/#yuicompressor) and a temp directory. Place the .jar in `min/lib`, then:
```
function yuiJs($js) {
    Minify_YUICompressor::$jarFile = __DIR__ . '/lib/yuicompressor-x.x.x.jar'; 
    Minify_YUICompressor::$tempDir = '/tmp'; 
    return Minify_YUICompressor::minifyJs($js); 
}
$min_serveOptions['minifiers']['application/x-javascript'] = 'yuiJs';
```

To use YUIC for CSS with fixed URIs:

```
function yuiCss($css, $options) {
    Minify_YUICompressor::$jarFile = __DIR__ . '/lib/yuicompressor-x.x.x.jar';
    Minify_YUICompressor::$tempDir = '/tmp';
    $css = Minify_YUICompressor::minifyCss($css);
    
    $css = Minify_CSS_UriRewriter::rewrite(
        $css
        ,$options['currentDir']
        ,isset($options['docRoot']) ? $options['docRoot'] : $_SERVER['DOCUMENT_ROOT']
        ,isset($options['symlinks']) ? $options['symlinks'] : array()
    );
    return $css;
}
$min_serveOptions['minifiers']['text/css'] = 'yuiCss';
```

### CSSmin PHP port

Minify has added Túbal Martín's [PHP port](https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port/blob/master/cssmin.php) of the YUI Compressor's CSSmin. While it is not completely integrated yet, you may try it out:

```
function yuiCssPort($css, $options) {
    $compressor = new CSSmin();
    $css = $compressor->run($css, 9999999);
    
    $css = Minify_CSS_UriRewriter::rewrite(
        $css,
        $options['currentDir'],
        isset($options['docRoot']) ? $options['docRoot'] : $_SERVER['DOCUMENT_ROOT'],
        isset($options['symlinks']) ? $options['symlinks'] : array()
    );
    return $css;
}
$min_serveOptions['minifiers']['text/css'] = 'yuiCssPort';
```

As of commit [218f37](https://github.com/mrclay/minify/commit/218f37fb44f9be2ea138cf9efb8b7f6dc84bad7f), this is easier:

```
$min_serveOptions['minifiers']['text/css'] = array('Minify_CSSmin', 'minify');
```

## JSMin+

Tino Zijdel's [JSMin+](http://crisp.tweakblogs.net/blog/6861/jsmin%2B-version-14.html) has resulted in memory usage problems for many users and will be removed from the Minify codebase in 3.0. If you wish to use it, you should download it outside the Minify directory and link to it:

```
require '/path/to/jsminplus.php';
$min_serveOptions['minifiers']['application/x-javascript'] = array('JSMinPlus', 'minify');
```

## Server-specific Options

You may need to have different options depending on what server you're on. You can do this just how you'd expect:
```
if ($_SERVER['SERVER_NAME'] == 'myTestingWorkstation') {
    // testing
    $min_allowDebugFlag = true;
    $min_errorLogger    = true;
    $min_enableBuilder  = true;
    $min_cachePath      = 'c:\\WINDOWS\\Temp';
    $min_serveOptions['maxAge'] = 0; // see changes immediately
} else {
    // production
    $min_allowDebugFlag = false;
    $min_errorLogger    = false;
    $min_enableBuilder  = false;
    $min_cachePath      = '/tmp';
    $min_serveOptions['maxAge'] = 86400;
}
```

## Site in a Subdirectory

If you test/develop sites in a subdirectory (e.g. `http://localhost/siteA/`), see AlternateFileLayouts.

## Group-specific Options

In "group" requests, `$_GET['g']` holds the group key, so you can code based on it:
```
if (isset($_GET['g'])) {
    switch ($_GET['g']) {
    case 'js' : $min_serveOptions['maxAge'] = 86400 * 7;
                break;
    case 'css': $min_serveOptions['contentTypeCharset'] = 'iso-8859-1';
                break;
    }
}
```

## File/Source-specific Options

See CustomSource.

## Processing Output After Minification

If `$min_serveOptions['postprocessor']` is set to a callback, Minify will pass the minified content to this function with type as the second argument. This allows you to apply changes to your minified content without making your own custom minifier. E.g.:
```
function postProcess($content, $type) {
    if ($type === Minify::TYPE_CSS) {    
        require_once 'CssColorReplacer.php';
        return CssColorReplacer::process($content);
    }
    return $content;
}
$min_serveOptions['postprocessor'] = 'postProcess';
```
This function is only called once immediately after minification and its output is stored in the cache.