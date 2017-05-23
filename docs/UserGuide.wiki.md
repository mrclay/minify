If this page doesn't help, please post a question on our [Google group](http://groups.google.com/group/minify).

# Creating Minify URLs

Let's say you want to serve the file http://example.com/css/foo/bar.css

You would use the URL http://example.com/min/?f=css/foo/bar.css

In other words, the "f" argument is set to the file path from root without the initial `/`. As CSS files may contain relative URIs, Minify will automatically "fix" these by rewriting them as root relative.

To combine multiple files, separate the paths given to "f" with commas.

Let's say you have JS files at these URLs:

* http://example.com/scripts/library-1.5.js
* http://example.com/scripts/site.js

You'd use the URL http://example.com/min/?f=scripts/library-1.5.js,scripts/site.js

## Shortening URLs with common directories

If you're combining files that share the same ancestor directory, you can use the "b" argument to set the base directory for the "f" argument. Do not include the leading or trailing `/` characters.

E.g., the following URLs will serve the exact same content:

* http://example.com/min/?f=path/to/scripts/library-1.5.js,path/to/scripts/foo/site.js
* http://example.com/min/?b=path/to/scripts&f=library-1.5.js,site.js,home.js

# Limiting access to directories

By default, Minify will serve any CSS/JS files within the DOCUMENT_ROOT. If you'd prefer to limit Minify's access to certain directories, set the `$min_serveOptions['minApp']['allowDirs']` array in config.php. E.g. to limit to the `/js` and `/themes/default` directories, use:

```php
$min_serveOptions['minApp']['allowDirs'] = ['//js', '//themes/default'];
```

# Using groups for nicer URLs

For nicer URLs, edit groupsConfig.php to pre-specify groups of files to be combined under preset keys. E.g., here's an example configuration in groupsConfig.php:

```php
return [
    'js' => ['//js/Class.js', '//js/email.js'],
];
```

This pre-selects the following files to be combined under the key "js":

* http://example.com/js/Class.js
* http://example.com/js/email.js

You can now serve these files with http://example.com/min/?g=js

## Specifying files outside the document root

In the groupsConfig.php array, the `//` in the file paths is a shortcut for the DOCUMENT_ROOT, but you can also specify paths from the root of the filesystem or relative to the DOC_ROOT:

```php
return [
    'js' => [
        '//js/file.js',           // file within DOC_ROOT
        '//../file.js',           // file in parent directory of DOC_ROOT
        'C:/Users/Steve/file.js', // file anywhere on filesystem
    ],
];
```

## Multiple groups and files in one URL

E.g.: http://example.com/min/?g=js&f=more/scripts.js

Separate group keys with commas: http://example.com/min/?g=baseCss,css1&f=moreStyles.css

## Creating URLs with the Builder

Enable the [BuilderApp](BuilderApp.wiki.md) via config.php. The default password is "admin", but even if no password is used there's very little server information disclosed.

Browse to http://example.com/min/builder/

The Minify URI Builder will help you create URIs you can use to minify existing files on your site. You can see screenshots and get a feel for this process from this [walkthrough on mrclay.org](http://mrclay.org/index.php/2008/09/19/minify-21-on-mrclayorg/)

You may want to disable the [BuilderApp](BuilderApp.wiki.md) when not in use.

# Far-future Expires headers

Minify can send far-future (one year) Expires headers. To enable this you must add a number or the parameter "v" to the querystring (e.g. `/min/?g=js&1234` or `/min/?g=js&v=1234`) and alter it whenever a source file is changed. If you have a build process you can use a build/source control revision number.

You can alternately use the utility function `Minify_getUri()` to get a "versioned" Minify URI for use in your HTML (it sniffs the `mtime` of the files). E.g.:

```php
require 'path/to/min/utils.php';

$jsUri = Minify_getUri('js'); // a key in groupsConfig.php
echo "<script src='{$jsUri}'></script>";

$cssUri = Minify_getUri([ // a list of files
    '//css/styles1.css',
    '//css/styles2.css',
]);
echo "<link rel=stylesheet href='{$cssUri}'>";
```

# Debug mode

In debug mode, instead of compressing files, Minify sends combined files with comments prepended to each line to show the line number in the original source file. To enable this, set `$min_allowDebugFlag` to `true` in config.php and append `&debug=1` to your URIs. E.g. `/min/?f=script1.js,script2.js&debug=1`

Known issue: files with comment-like strings/regexps can cause problems in this mode.

# Configuration

See [config.php](../config.php) for general config options.

[groupsConfig.php](../groupsConfig.php) holds preset groups of files to minify. (The builder application can help with this).

[CookBook](CookBook.wiki.md) shows how to customize settings between production/development environments, and between groups.

[CustomSource](CustomSource.wiki.md) shows how to set some file/source-specific options, or serve content from a PHP script or URL.

### Hosting on Lighttpd

Minify comes with Apache mod_rewrite rules, but this does the same for Lighttpd:

```
url.rewrite-once = ( "^/min/([a-z]=.*)" => "/min/index.php?$1" )
```

# Problems?

See [CommonProblems](CommonProblems.wiki.md) and [Debugging](Debugging.wiki.md). You might also try running `server-info.php` in particular.
