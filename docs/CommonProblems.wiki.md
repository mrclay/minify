If this page doesn't help, please post a question on our [Google group](http://groups.google.com/group/minify).

## URIs are re-written incorrectly in CSS output

See [UriRewriting](UriRewriting.wiki.md).

## Builder Fails / 400 Errors

This is usually due to an unusual server setup. You can see the cause of 400 responses using FirePHP (See [Debugging](Debugging.wiki.md)).

## Long URL parameters are ignored

Some server setups will refuse to populate very long `$_GET` params. Use [groups](UserGuide.wiki.md#using-groups-for-nicer-urls) to shorten the URLs.

## PHP/Apache crashes

[PCRE (which provides regular expressions) commonly crashes PHP](https://www.google.com/search?q=pcre+php+crash) and this is nearly impossible to solve in PHP code. Things to try:

  * Raise Apache's [ThreadStackSize](http://stackoverflow.com/a/7597506/3779)
  * In [php.ini](http://php.net/manual/en/pcre.configuration.php) raise `pcre.backtrack_limit` and `pcre.recursion_limit` to 1000000. These will allow processing longer strings, but also require a larger stack size.
  * Use YUICompressor instead of PHP-based CSS compressors

## Dealing with Javascript errors

Short answer: **use Minify 2.1.4+, use a pre-compressed version of your file, and rename it `*.min.js` or `*-min.js`**. By default Minify won't try to minify these files (but will still gzip them). The [Compressor Rater](http://compressorrater.thruhere.net/) is handy for compressing files individually.

If the error is in your code, enable [debug mode](Debugging.wiki.md) while debugging your code in Firebug or your favorite browser's Javascript debugger. This will insert comments to allow you to keep track of the individual source locations in the combined file.

If you have Java on your web host, you can use the [wrapper for YUI Compressor](../lib/Minify/YUICompressor.php) instead of JSMin. [This thread](http://groups.google.com/group/minify/browse_thread/thread/f12f25f27e1256fe) shows how a user has done this.

## Javascript isn't being minified

If the filename ends with **`-min.js`** or **`.min.js`**, Minify will assume the file is already compressed and just combine it with any other files.

### Scriptaculous

Scriptaculous 1.8.2 (and probably all 1.x) has an [autoloader script](http://github.com/madrobby/scriptaculous/blob/4b49fd8884920d4ee760b0194431f4f433f878df/src/scriptaculous.js#L54) that requires files to be in a particular place on disk. To serve Scriptaculous modules with Minify, just serve `prototype.js` and the individual support files (e.g. `dragdrop.js`, `effects.js`) and the library should work fine. E.g.:

```
<script src="/min/f=scriptaculous/lib/prototype.js" type="text/javascript"></script>
<script src="/min/b=scriptaculous/src&amp;f=effects.js,dragdrop.js" type="text/javascript"></script>
<script type="text/javascript">
/* DragDrop and Effects modules can be used here. */
</script>
```

## Server cache files won't update

If you upload files using [Coda or Transmit](http://groups.google.com/group/coda-users/browse_thread/thread/572d2dc315ec02e7/) or from a Windows PC to a non-Windows server, your new files may end up with the wrong `mtime` (timestamp) on the server, confusing the cache system.

Setting the [$min\_uploaderHoursBehind option](../config.php#L171) in `config.php` can compensate for this.

WinSCP has a [Daylight Saving Time option](http://winscp.net/eng/docs/ui_login_environment#daylight_saving_time) that can prevent this issue.

This can also occur if your files are changed, and the `mtime` is set in the past (e.g. via a `git checkout` operation). If so you'll have to `touch` the changed files or use some other method to make the `mtime` current.

## Can't see changes in browser

Generally changes aren't seen because a) the browser is refusing to send a new request, or b) the server doesn't recognize that your source files have been modified after the server cache was created.

First, place the Minify URL directly in the address bar and refresh.

If a change is not seen, verify that the server cache file is being updated.

## Disable Caching

If you'd like to temporarily disable the cache without using [debug mode](Debugging.wiki.md), place these settings at the end of `config.php`:
```php
// disable server caching
$min_cachePath = null;
// prevent client caching
$min_serveOptions['maxAge'] = 0;
$min_serveOptions['lastModifiedTime'] = $_SERVER['REQUEST_TIME'];
```
**Don't do this on a production server!** Minify will have to combine, minify, and gzencode on every request.

## Character Encodings

_Please_ use UTF-8. The string processing may work on encodings like Windows-1251 but will certainly fail on radically different ones like UTF-16.

If you consistently use a different encoding, in `config.php` set `$min_serveOptions['contentTypeCharset']` to this encoding to send it in the Content-Type header.

Otherwise, set it to `false` to remove it altogether. You can still, in CSS, use the [@charset](http://www.w3.org/TR/CSS2/syndata.html#x50) directive to tell the browser the encoding, but (a) it must appear first and (b) shouldn't appear later in the output (and Minify won't enforce this).

Moral? To minimize problems, use UTF-8 and remove any `@charset` directives from your CSS.

## @imports can appear in invalid locations in combined CSS files

If you combine CSS files, @import declarations can appear after CSS rules, invalidating the stylesheet. As of version 2.1.2, if Minify detects this, it will prepend a warning to the output CSS file. To resolve this, you can either move your @import statements within your files, or enable the option 'bubbleCssImports'.

## Debug mode can cause a Javascript error

This issue was resolved in version 2.1.2.

Debug mode adds line numbers in comments. Unfortunately, in versions <= 2.1.1, if the source file had a string or regex containing (what looks like) a C-style comment token, the algorithm was confused and the injected comments caused a syntax error.

## Minification can cause a Javascript error

This issue was resolved in version 2.1.2.

In rare instances the [JSMin](https://github.com/mrclay/jsmin-php/blob/master/src/JSMin/JSMin.php) algorithm in versions <= 2.1.1 could be confused by regexes in certain contexts and throw an exception. The workaround was to simply wrap the expression in parenthesis. E.g.
```js
// in 2.1.1 and previous
return /'/;   // JSMin throws error
return (/'/); // no error
```

## Output is distorted/random chars

What you're seeing is a mismatch between the content encoding the browser expects and what it receives.

The usual problem is that a global PHP or web server configuration is causing the output of PHP scripts to be automatically gzipped. Since Minify already outputs gzipped content, the browser receives "double encoded" content which it interprets as noise. The Builder app in 2.1.4 sometimes can tell you which component is causing the auto-encoding.

## Can't specify more than 10 files via URL

Use Minify 2.1.4+. Before there was a setting to adjust the maximum allowed.

## Directory Listing Denied

This may also appear as "Virtual Directory does not allow contents to be listed". Minify requires that the URI `/min/` (a request for a directory listing) result in the execution of `/min/index.php`. On Apache, you would make sure `index.php` is listed in the [DirectoryIndex directive](http://httpd.apache.org/docs/2.0/mod/mod_dir.html#directoryindex). IIS calls this the [Default Document](http://www.iis.net/ConfigReference/system.webServer/defaultDocument).

## "WARN: environment : Local HTTP request failed. Testing cannot continue."

The `test_environment.php` unit test makes a few local HTTP requests to sniff for `zlib.output_compression` and other auto-encoding behavior, which may break Minify's output. This warning will appear if `allow_url_fopen` is disabled in php.ini, but **does not** necessarily mean there is a problem.

If Minify seems to work fine, ignore the warning. If Minify produces garbled output, enable `allow_url_fopen` in php.ini and re-run the tests. The tests may be able to tell you if PHP or your server is automatically encoding output.

Unless you need it in other scripts, disable `allow_url_fopen` once the issue is resolved. Minify does not need it.

## See Also

  * [Debugging](Debugging.wiki.md)
