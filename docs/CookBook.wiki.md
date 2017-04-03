Unless mentioned, all the following snippets go in `config.php`.

## Faster Cache Performance

By default, Minify uses `Minify_Cache_File`. It uses `readfile`/`fpassthru` to improve performance over most file-based systems, but it's still file IO, so the following caching options should be faster. In all cases, Minify cache ids begin with `"minify_"`.

### APC

```php
$min_cachePath = new Minify_Cache_APC();
```

### Memcache

You must create and connect your Memcache object then pass it to `Minify_Cache_Memcache`'s constructor.
```php
$memcache = new Memcache;
$memcache->connect('localhost', 11211);
$min_cachePath = new Minify_Cache_Memcache($memcache);
```

### Zend Platform

```php
$min_cachePath = new Minify_Cache_ZendPlatform();
```

### XCache

```php
$min_cachePath = new Minify_Cache_XCache();
```

### WinCache

```php
$min_cachePath = new Minify_Cache_WinCache();
```

## Closure Compiler API Wrapper

An [experimental wrapper for Google's closure compiler API](../lib/Minify/JS/ClosureCompiler.php) is available for compressing Javascript. If the API fails for any reason, JSMin is used as the default backup minifier.
```php
$min_serveOptions['minifiers'][Minify::TYPE_JS] = array('Minify_JS_ClosureCompiler', 'minify');
```

## YUICompressor

If your host can execute Java, you can use Minify's YUI Compressor wrapper. You'll need the latest [yuicompressor-x.x.x.jar](https://github.com/yui/yuicompressor/releases) and a temp directory. Place the .jar in `min/lib`, then:
```php
function yuiJs($js) {
    Minify_YUICompressor::$jarFile = __DIR__ . '/lib/yuicompressor-x.x.x.jar'; 
    Minify_YUICompressor::$tempDir = '/tmp'; 
    return Minify_YUICompressor::minifyJs($js); 
}
$min_serveOptions['minifiers'][Minify::TYPE_JS] = 'yuiJs';
```

To use YUIC for CSS with fixed URIs:

```php
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
$min_serveOptions['minifiers'][Minify::TYPE_CSS] = 'yuiCss';
```

## Legacy CSS compressor

In 3.x, Minify uses [CSSmin](https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port), a PHP port of the YUI CSS compressor. To use the compressor that came with Minify 2.x (not recommended), uncomment this line in your `config.php` file:

```php
//$min_serveOptions['minifiers'][Minify::TYPE_CSS] = array('Minify_CSS', 'minify');
```

## Server-specific Options

You may need to have different options depending on what server you're on. You can do this just how you'd expect:
```php
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
```php
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
```php
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