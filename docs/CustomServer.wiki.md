Please see the UserGuide if you're just getting started with Minify. This guide is for more advanced users who wish to implement an HTTP server in PHP using the `Minify` class.

## Pull in Minify via Composer

In composer.json:
```
	"require": {
		"mrclay/minify": "~3"
	},
```

```bash
composer install
```

## Set up autoloading, caching, and the Minify instance

Minify ships with several [cache classes](https://github.com/mrclay/minify/tree/master/lib/Minify/Cache) for files, APC, memcache, etc.:

```php
require __DIR__ . '/vendor/autoload.php';

$cache = new Minify_Cache_APC();
$minify = new Minify($cache);
```

## Create the environment and the factory for source objects

```php
$env = new Minify_Env();
$sourceFactory = new Minify_Source_Factory($env, [], $cache);
```

## Choose a Minify controller

Minify uses controller classes to analyze the environment (HTTP requests) and determine which sources will make up the response. (The content isn't always returned; if the browser's cache is valid, Minify can return a 304 header instead).

The `Files` controller doesn't care about the HTTP request. It just specifies a given array of sources (file paths or [source objects](CustomSource.md)).

The `Groups` controller uses `$_SERVER['PATH_INFO']` to choose from an array of source lists. There's an example at the end of this page.

The Files controller is simple, so we'll use it here.

```php
$controller = new Minify_Controller_Files($env, $sourceFactory);
```

## Set up service and controller options

A single array is used for configuring both the behavior of `Minify::serve` (see the [default options](../lib/Minify.php#L73)) and the controller, which has its own option keys.

The Files controller only needs one key: `files`: the array of sources to be combined and served.

The only `serve` option we'll set is `maxAge` (the default is only 1800 seconds).

```php
$options = [
    // options for the controller
    'files'  => ['//js/file1.js', '//js/file2.js'],
    
    // options for Minify::serve
    'maxAge' => 86400,
    'minifierOptions' => [
        'text/css' => [
            'docRoot' => $env->getDocRoot(), // allows URL rewriting
        ],
    ],
];
```

Note: `files` can also accept `Minify_Source` objects, which allow serving more than static files.

## Handle the request

```php
$minify->serve($controller, $options);
```

That's it...

Minify's default application (`index.php`) is implemented similarly, but uses the `MinApp` controller. If you need URL rewriting in CSS files, you'll need to configure

# The Request Cycle

In handling a request, `Minify::serve` does a number of operations:

  1. Minify merges your given options with its default options
  1. calls the controller's `createConfiguration()`, which analyzes the request and returns a `Minify_ServeConfiguration` object, encapsulating the source objects found.
  1. uses `analyzeSources()` to determine the Content-Type and last modified time.
  1. determines if the browser accepts gzip
  1. validates the browser cache (optionally responding with a 304)
  1. validates the server cache. If it needs refreshing, `combineMinify()` fetchs and combines the content of each source.
  1. sets up headers to be sent
  1. either returns the headers and content in an array (if `quiet` is set), or sends it to the browser.

# Using the Groups controller

The Groups controller uses `$_SERVER['PATH_INFO']` to select an array of sources from the given options: 
```
$options = [
    'groups' => [
        'js'  => ['//js/file1.js', '//js/file2.js'],
        'css' => ['//css/file1.css', '//css/file2.css'],
    ],
];
```

With the above, if you request `http://example.org/myServer.php/css`, Apache will set `$_SERVER['PATH_INFO'] = '/css'` and the sources in `$options['groups']['css']` will be served.
