In `groupsConfig.php`, usually you specify source file paths using strings like `/path/to/file.js` or `//js/file1.js` (Minify expands this to `"{$_SERVER['DOCUMENT_ROOT']}/js/file1.js"` ).

Instead of a string, you may substitute a "source object" (an instance of class [Minify\_Source](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify/Source.php)). This allows you to customize how minification is applied, and/or pull content from a non-file location (e.g. a URL).

### Example: filepath

In the `$spec` array, set the key `filepath` to produce a source based on a file path:
```
$src1 = new Minify_Source(array(
    'filepath' => '//js/file1.js',
));
$src2 = new Minify_Source(array(
    'filepath' => '//js/file2.js',
));

return array(
    'js' => array($src1, $src2)
);
```
Note the above is functionally identical to:
```
return array(
    'js' => array('//js/file1.js', '//js/file2.js')
);
```

### Example: Specify a different minifier or none at all

To change minifier, set `minifier` to a [callback](http://php.net/manual/en/language.pseudo-types.php)`*` or the empty string (for none):

**`*`Prepare for `groupsConfig.php` to be executed more than once.** (This is likely if you're using the functions in `/min/utils.php`.) In practice this just means making sure functions are conditionally defined if they don't already exist, etc.

```
if (! function_exists('myMin')) {
    function myMin($js) {
        require_once '/path/to/jsminplus.php';
        return JSMinPlus::minify($js);
    }
}

$src1 = new Minify_Source(array(
    'filepath' => '//js/file1.js',
    'minifier' => 'myMin',
));
$src2 = new Minify_Source(array(
    'filepath' => '//js/file2.js',
    'minifier' => '', // don't compress
));
```
In the above, `myMin()` is only called when rebuilding the cache. This way `JSMinPlus.php` is only loaded when needed.

**`*`Do _not_ use [create\_function](http://php.net/manual/en/function.create-function.php) or anonymous functions for the minifier.** The internal names of these function tend to vary, causing Minify to create lots of [duplicate cache files](http://code.google.com/p/minify/issues/detail?id=138) (and perform poorly).

## Non-File Sources

You're not limited to flat js/css files, but without `filepath`, the `$spec` array must contain these keys:

  * **`id `** a unique string id for this source. (e.g. `'my source'`)
  * **`getContentFunc `** a [callback](http://php.net/manual/en/language.pseudo-types.php) that returns the content. The function is only called when the cache is rebuilt.
  * **`contentType `** `Minify::TYPE_JS` or `Minify::TYPE_CSS`
  * **`lastModified `** a timestamp indicating when the content last changed. (If you can't determine this quickly, you can "fake" it using a step function, causing the cache to be periodically rebuilt.)

### Example: Content from a URL

Here we want to fetch javascript from a URL. We don't know when it will change, so we use a stepping expression to re-fetch it every midnight:
```
if (! function_exists('src1_fetch')) {
    function src1_fetch() {
        return file_get_contents('http://example.org/javascript.php');
    }
}
$src1 = new Minify_Source(array(
    'id' => 'source1',
    'getContentFunc' => 'src1_fetch',
    'contentType' => Minify::TYPE_JS,    
    'lastModified' => ($_SERVER['REQUEST_TIME'] - $_SERVER['REQUEST_TIME'] % 86400),
));
```

If you know that the URL content only depends on a few local files, you can use the maximum of their `mtime`s as the `lastModified` key:
```
...
$src1 = new Minify_Source(array(
    'id' => 'source1',
    'getContentFunc' => 'src1_fetch',
    'contentType' => Minify::TYPE_JS,
    'lastModified' => max(
        filemtime('/path/to/javascript.php')
        ,filemtime('/path/to/javascript_input.css')
    ),
));
```

## Performance Considerations

Be aware that all the code you put in groupsConfig.php will be evaluated upon every request like `/min/g=...`, so make it as light as possible.

If you wish to keep groupsConfig.php "clean", you can alternately create a separate PHP script that manually sets up sources, caching, options, and calls Minify::serve().
```
<?php // myServer.php
/**
 * This script implements a Minify server for a single set of sources.
 * If you don't want '.php' in the URL, use mod_rewrite...
 */

// setup Minify
set_include_path('path/to/min/lib' . PATH_SEPARATOR . get_include_path());
require 'Minify.php';
require 'Minify/Cache/File.php';
Minify::setCache(new Minify_Cache_File()); // guesses a temp directory

function src1_fetch() {
    return file_get_contents('http://example.org/javascript.php');
}

// setup sources
$sources = array();
$sources[] = new Minify_Source(array(
    'id' => 'source1'
    ,'getContentFunc' => 'src1_fetch'
    ,'contentType' => Minify::TYPE_JS    
    ,'lastModified' => max(
        filemtime('/path/to/javascript.php')
        ,filemtime('/path/to/javascript_input.js')
    )
));
$sources[] = '//file2.js';
$sources[] = '//file3.js';

// setup serve options
$options = array(
    'files' => $sources,
    'maxAge' => 86400,
);

// handle request
Minify::serve('Files', $options);
```