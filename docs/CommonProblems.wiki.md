If this page doesn't help, please post a question on our [Google group](http://groups.google.com/group/minify).

## URIs are re-written incorrectly in CSS output

See UriRewriting.

## Files aren't cached in IE6

**Use Minify 2.1.4+**.

For Minify 2.1.3 and below:

  1. Open `/min/lib/HTTP/Encoder.php`
  1. On line [62](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/HTTP/Encoder.php#62), change `false` to `true`.

## Builder Fails / 400 Errors

**Use Minify 2.1.4+**, and you can see the cause of 400 responses using FirePHP (See [Debugging](Debugging.md)).

## PHP/Apache crashes

[PCRE (which provides regular expressions) commonly crashes PHP](https://www.google.com/search?q=pcre+php+crash) and this is nearly impossible to solve in PHP code. Things to try:

  * Raise Apache's [ThreadStackSize](http://stackoverflow.com/a/7597506/3779)
  * In [php.ini](http://php.net/manual/en/pcre.configuration.php) raise `pcre.backtrack_limit` and `pcre.recursion_limit` to 1000000. These will allow processing longer strings, but also require a larger stack size.
  * Try [this CSSmin configuration](http://code.google.com/p/minify/wiki/CookBook#CSSmin_PHP_port)

## Dealing with Javascript errors

Short answer: **use Minify 2.1.4+, use a pre-compressed version of your file, and rename it `*.min.js` or `*-min.js`**. By default Minify won't try to minify these files (but will still gzip them). The [Compressor Rater](http://compressorrater.thruhere.net/) is handy for compressing files individually.

If the error is in your code, enable [debug mode](Debugging.md) while debugging your code in Firebug or your favorite browser's Javascript debugger. This will insert comments to allow you to keep track of the individual source locations in the combined file.

If you have Java on your web host, you can use the [wrapper for YUI Compressor](http://code.google.com/p/minify/source/browse/min/lib/Minify/YUICompressor.php) instead of JSMin. [This thread](http://groups.google.com/group/minify/browse_thread/thread/f12f25f27e1256fe) shows how a user has done this.

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

Setting the [$min\_uploaderHoursBehind option](https://github.com/mrclay/minify/blob/master/min/config.php#L171) in `config.php` can compensate for this.

WinSCP has a [Daylight Saving Time option](http://winscp.net/eng/docs/ui_login_environment#daylight_saving_time) that can prevent this issue.

This can also occur if your files are changed, and the `mtime` is set in the past (e.g. via a `git checkout` operation). If so you'll have to `touch` the changed files or use some other method to make the `mtime` current.

## Can't see changes in browser

Generally changes aren't seen because a) the browser is refusing to send a new request, or b) the server doesn't recognize that your source files have been modified after the server cache was created.

First, place the Minify URL directly in the address bar and refresh.

If a change is not seen, verify that the server cache file is being updated.

## Disable Caching

If you'd like to temporarily disable the cache without using [debug mode](Debugging.md), place these settings at the end of `config.php`:
```
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

In rare instances the [JSMin](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/lib/JSMin.php#14) algorithm in versions <= 2.1.1 could be confused by regexes in certain contexts and throw an exception. The workaround was to simply wrap the expression in parenthesis. E.g.
```
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

## See Also

  * [Debugging](Debugging.md)
