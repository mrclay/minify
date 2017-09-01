# HTTP Caching in Minify

## Conditional GET

Minify sends all files with Last-Modified and ETag headers. If the browser requests a file again it will send along If-Modified-Since and If-None-Match headers. Minify checks these headers and, if the browser has the latest version, sends back only a slim "304 Not Modified" response (no content), telling the browser to use its cached file.

## Expires Header

Minify also sends Expires and Cache-Control: max-age headers, indicating that the file should be considered valid for a period of time. In future page views the browser will not re-request the file (unless the user refreshes), and instead will use the cached version.

By default, Minify sends an Expires header for 1800 seconds (30 minutes) into the future (configurable via `$min_serveOptions['maxAge']`). This means your file changes may not be seen by users immediately after you make them. If your changes must be seen immediately, you should reduce max-age to 0, but note you will not get as much benefit, as browsers will still have to send requests **every time**.

## Far-off Expires

When pre-set groups are used and a number is appended to the minify URI (e.g. `/min/g=css&456`), then Minify sends an Expires date of 1 year in the future. This is great for caching, but places responsibility on your HTML pages. They _must_ change the number whenever a JS/CSS source file is updated, or the browser will not know to re-request the file. If you're generating your page with PHP, the [Minify\_groupUri](http://code.google.com/p/minify/source/browse/min/utils.php?r=222#11) utility function can make this easier to manage.

# Using `HTTP_ConditionalGet` in other projects

Minify uses the PHP class [HTTP\_ConditionalGet](http://code.google.com/p/minify/source/browse/lib/HTTP/ConditionalGet.php) to implement the conditional GET model. To use this in your own project you'll need the last modification time of your content (for a file, use [filemtime()](http://www.php.net/filemtime)), or a short hash digest of the content (something that changes when the content changes). You'll also want to consider if the content can be stored in public caches, or should only be stored in the user's browser.

## When the last modification time is known

In this example we implement conditional GET for a mostly-static PHP script. The browser needs to redownload the content only when the file is updated.

```
// top of file
$cg = new HTTP_ConditionalGet(array(
  'isPublic' => true,
  'lastModifiedTime' => filemtime(__FILE__)
));
$cg->sendHeaders();
if ($cg->cacheIsValid) { // 304 already sent
    exit();
}
// rest of script
```

For the first request the browser's cache won't be valid, so the full script will execute, sending the full content. On the next, the cache will be valid and the sendHeaders() method will have already set the 304 header, so the script can be halted.

There's also a shortcut static method for this:
```
HTTP_ConditionalGet::check(filemtime(__FILE__), true); // exits if client has cache
// rest of script
```

## When last modification time isn't known

Let's say you have an HTML page in a database, but no modification time. To reduce DB requests, you cache this content in a file/memory, but you'd also like to reduce bandwidth. In this case, what you can do is also cache a hash of the page along with the content. Now you can do this:

```
$cache = getCache();
$cg = new HTTP_ConditionalGet(array(
  'isPublic' => true,
  'contentHash' => $cache['hash']
));
$cg->sendHeaders();
if ($cg->cacheIsValid) { // 304 already sent
    exit();
}
echo $cache['page'];
```

Although Last-Modified cannot be set, ETag will serve the same purposes in most browsers, allowing the conditional GET.