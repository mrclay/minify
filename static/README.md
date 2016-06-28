
# Static file serving

**Note:** This feature is new and not extensively tested.

Within this folder, Minify creates minified files on demand, serving them without the overhead of PHP at all.

For example, when a visitor requests a URL like `/min/static/1467089473/f=js/my-script.js`, Minify creates the directories `1467089473/f=js`, and saves the minified file `my-script.js` in it. On following requests, the file is served directly.

## Getting started

1. Make sure the `static` directory is writable by your server.

2. In `minify/config.php`, set `$min_enableStatic = true;`

3. Request the test script http://example.org/min/static/0/f=min/quick-test.js

    This will create a new cache directory within `static` and redirect the browser to the new location, e.g. http://example.org/min/static/1467089473/f=min/quick-test.js.

    You should see the minified script and on the server the `static` directory should contain a new subdirectory tree with the static file. Following requests will serve the file directly.

4. Delete the new subdirectory (e.g. `1467089473`) and refresh the browser.

You should be redirected to the new location where the file and cache directory has been recreated.

## Site integration

You don't want to hardcode any URLs. Instead we'll use functions in `lib.php`:

```php
require_once __DIR__ . '/path/to/static/lib.php';

$static_uri = "/min/static";
$query = "b=scripts&f=1.js,2.js";
$type = "js";

$uri = Minify\StaticService\build_uri($static_uri, $query, $type);
```

If you release a new build (or change any source file), you *must* clear the cache by deleting the entire directory:

```php
require_once __DIR__ . '/path/to/static/lib.php';

Minify\StaticService\flush_cache();
```

## URL rules

As URLs result in files being created, they are more strictly formatted.

* Arbitrary parameters (e.g. to bust a cache) are not permitted.
* URLs must end with `.js` or `.css`.

If your URL does not end with `.js` or `.css`, you'll need to append `&z=.js` or `&z=.css` to the URL. E.g.:

* http://example.org/min/static/1467089473/g=home-scripts&z=.js
* http://example.org/min/static/1467089473/f=styles.less&z=.css

Note that `Minify\StaticService\build_uri` handles this automatically for you.

URLs aren't canonical, so these URLs are all valid and will produce separate files:

* http://example.org/min/static/1467089473/f=one/two/three.js
* http://example.org/min/static/1467089473/b=one/two&f=three.js
* http://example.org/min/static/1467089473/f=three.js&b=one/two&z=.js

## Disable caching

You can easily switch to use the regular `min/` endpoint during development:

```php
<?php

$query = "b=styles&f=minimal.less";
$type = "css";

if ($use_static) {
    require_once __DIR__ . '/path/to/static/lib.php';
    $static_uri = "/min/static";
    $uri = Minify\StaticService\build_uri($static_uri, $query, $type);
} else {
    $uri = "/min/?$query";
}
```
