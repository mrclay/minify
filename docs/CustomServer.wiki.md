Please see the UserGuide if you're just getting started with Minify. This guide is for more advanced users who wish to implement an HTTP server in PHP using the [Minify](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify.php) class.

The basic steps are:

  1. Set up the include\_path
  1. Set up caching
  1. Choose a Minify controller
  1. Set up service and controller options
  1. Handle the request

## Set up the include\_path

```
set_include_path('/path/to/min/lib' . PATH_SEPARATOR . get_include_path());
```

## Set up caching

Minify ships with [cache classes](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify/Cache/) for files, APC, and memcache. Choose one, instantiate it, and pass it to Minify:
```
require 'Minify.php';
require 'Minify/Cache/File.php';

Minify::setCache(new Minify_Cache_File()); // files in directory chosen by Solar_Dir
```

## Choose a Minify controller

Minify uses controller classes to analyze HTTP requests and determine which sources will make up the response. (The content isn't always returned; if the browser's cache is valid, Minify can return a 304 header instead).

The [Files](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify/Controller/Files.php#9) controller doesn't care about the HTTP request. It just specifies a given array of sources (file paths or [source objects](CustomSource.md)).

The [Groups](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify/Controller/Groups.php) controller uses `$_SERVER['PATH_INFO']` to choose from an array of source lists. There's an example at the end of this page.

The Files controller is simple, so we'll use it here.

## Set up service and controller options

A single array is used for configuration, and is passed to the `Minify::serve` method. `serve` passes it to the controller, which picks off the keys it needs and returns it to `serve` for Minify's own built-in [options](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify.php#88). This architecture allows the controller to set defaults for `serve`.

The Files controller only needs one key: `files`: the array of sources to be combined and served.

The only `serve` option we'll set is `maxAge` (the default is a measly 1800 seconds).

```
$options = array(
    // options for the controller
    'files'  => array('//js/file1.js', '//js/file2.js', $src),
    // options for Minify::serve
    'maxAge' => 86400
);
```
(In the above $src is a [Minify\_Source object](CustomSource.md), which allows you to serve non-file content, and/or apply settings to individual sources.)

## Handle the request

```
Minify::serve('Files', $options);
```

That's it...

Minify's default application (sometimes referred to as "min") is implemented this way, in the file [min/index.php](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/index.php). Most of its request handling is encapsulated in its own [MinApp](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/lib/Minify/Controller/MinApp.php#15) controller.

# The Request Cycle

In handling a request, `Minify::serve` does a number of operations:

  1. creates a controller object, unless you pass it a readymade object (let's call this `$ctrl`).
  1. calls `$ctrl->setupSources($options)`, which analyzes the request and sets `$ctrl->sources` accordingly to an array of Minify\_Source objects.
  1. calls `$ctrl->analyzeSources($options)`, which set `$options` keys for `contentType` and `lastModifiedTime`, based on the sources.
  1. calls `$ctrl->mixInDefaultOptions($options)`, to mix the controller's default options with the user's.
  1. determines if the browser accepts gzip
  1. validates the browser cache (optionally responding with a 304)
  1. validates the server cache. If it needs refreshing, `Minify::_combineMinify` is called, which...
    * calls `$source->getContent` on each source
    * the content is combined before or after minification (depending on individual source options)
  1. sets up headers to be sent
  1. either returns the headers and content in an array (if `quiet` is set), or sends it to the browser.

# Using the Groups controller

```
$options = array(
    'groups' => array(
        'js'  => array('//js/file1.js', '//js/file2.js'),
        'css' => array('//css/file1.css', '//css/file2.css'),
    ),
);
Minify::serve('Groups', $options);
```

With the above, if you request `http://example.org/myServer.php/css`, Apache will set `$_SERVER['PATH_INFO'] = '/css'` and the sources in `$options['groups']['css']` will be served.

<a href='Hidden comment: Move somewhere else

== Sending far future Expires headers ==

By default Minify enables [http://fishbowl.pastiche.org/2002/10/21/http_conditional_get_for_rss_hackers conditional GETs] for client-side caching, but in this model the browser has to continuously check back with the server to revalidate its cache. A better method is to send [http://developer.yahoo.com/performance/rules.html#expires far future Expires headers] and change the URL when the file changes. As long as you generate your HTML with PHP, Minify makes it easy to do this.

1. Move the "groups" configuration array into a separate file "_groupsConfig.php":

```
<?php
// configures both Minify::serve() and Minify_Build
return array(
    "js1" => array("//js/yourFile1.js", "//js/yourFile2.js")
    ,"js2" => array("//js/yourFile1.js", "//js/yourFile3.js")
    ,"jQuery" => array("//js/jquery-1.2.6.js")
    ,"css" => array("//css/layout.css", "//css/fonts.css")
);
```

2. Adjust "min.php" to use this file:

```
Minify::serve("Groups", array(
    "groups" => (require "_groupsConfig.php")
));
```

3. In your HTML-generating script, create Minify_Build objects for each minify URI you"re going to use, and, to each, apply the uri() method:

```
require "Minify/Build.php";
$_gc = (require "_groupsConfig.php");
$js1Build = new Minify_Build($_gc["js1"]);
$cssBuild = new Minify_Build($_gc["css"]);

echo "<link rel="stylesheet" type="text/css" href="" . $cssBuild->uri("/min.php/css") . "" />";
/* ... */
echo "<script type="text/javascript" src="" . $js1Build->uri("/min.php/js1") . ""></script>";
```

5. Open the (X)HTML page in your browser. The Javascript and CSS should "work".

6. View source. The minify URIs should look like: "/min.php/js1?##########" (a unix timestamp)

7. Now that our URI"s are synched with source file changes, we can safely send Expires headers. In "min.php":

```
Minify::serve("Groups", array(
    "groups" => (require "_groupsConfig.php")
    ,"maxAge" => 31536000 // 1 yr
    // in 2.0 was "setExpires" => $_SERVER["REQUEST_TIME"] + 31536000
));
```

Now "min.php" will serve files with Expires headers, causing the browser to always retrieve them from cache (until the expiration date).

When you make a change to any of your source Javascript/CSS files, your HTML file will have a different querystring appended to the minify URIs, causing the browser to download it as a new URL, and Minify will automatically rebuild its cache files on the server.

'></a>