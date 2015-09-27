## Default operation

Minify uses an algorithm to rewrite relative URIs in CSS output to root-relative URIs so that each link points to the same location it did in its original file.

Say your style sheet `http://example.org/theme/fashion/style.css` contains:
```
body { background: url(bg.jpg); }
```

When Minify serves this content (from `http://example.org/min/f=theme/fashion/style.css` or `http://example.org/min/g=css`) it re-writes the URI so the image is correctly linked:
```
body{background:url(/theme/fashion/bg.jpg)}
```

You can see the steps used to rewrite your URIs by enabling [debug mode](Debugging.md).

## Disable Rewriting

You can disable the automatic rewriting by setting this in min/config.php:
```
$min_serveOptions['rewriteCssUris'] = false;
```

## Manual Rewriting

You can manually rewrite relative URIs in CSS in a couple ways. The simplest is to prepend a string to each relative URI:
```
$min_serveOptions['rewriteCssUris'] = false;
$min_serveOptions['minifierOptions']['text/css']['prependRelativePath'] = '/css/';
```

Or you can run the minified output through a custom [post-processor](CookBook#Processing_Output_After_Minification.md) function.

## Document Root Confusion

Out-of-the-box, Minify gets confused when `min` is placed in a subdirectory of the real document root. There's now a [simple workaround](AlternateFileLayouts.md) for this, making `min` more portable.

## Aliases / Symlinks / Virtual Directories

Whether you use [aliases](http://httpd.apache.org/docs/2.2/mod/mod_alias.html), [symlinks](http://en.wikipedia.org/wiki/Symbolic_link), or [virtual directories](http://msdn.microsoft.com/en-us/library/zwk103ab.aspx), if you make content outside of the DOC\_ROOT available at public URLs, Minify may need manual configuration of the `$min_symlinks` option to rewrite some URIs correctly. Consider this scenario, where `http://example.org/static/style.css` will serve `/etc/static_content/style.css`:

| document root     | `/var/www`                                  |
|:------------------|:--------------------------------------------|
| Apache mod\_alias | `Alias /static /etc/static_content`         |
| ...or symlink     | `ln -s /etc/static_content /var/www/static` |

In `/min/config.php` you'll need the following:
```
// map URL path to file path
$min_symlinks = array(
    '//static' => '/etc/static_content'
);
```
This lets Minify know during the rewriting process that an internal file path starting with `/etc/static_content` should be rewritten as a public URI beginning with `/static`.

If your alias target directory is outside of DOC\_ROOT, you'll also need to explicitly allow Minify to serve files from it:
```
$min_serveOptions['minApp']['allowDirs'] = array(
    '//',                 // allow from the normal DOC_ROOT
    '/etc/static_content' // allow from our alias target
); 
```

### What's my document root?

You can enable the script `min/server-info.php` and open http://example.org/min/server-info.php to find useful `$_SERVER` values. People in the [Google Group](https://groups.google.com/forum/#!forum/minify) might need these to help you.

## It's still not working

  1. Make sure you have the [latest version](http://code.google.com/p/minify/downloads/list).
  1. Enable [debug mode](Debugging.md), which will show you the URI transformation process.
  1. Check that `$_SERVER['DOCUMENT_ROOT']` is a real directory path. If not, URI rewriting will fail. If you cannot fix this in httpd.conf, etc., use the [configuration option](http://code.google.com/p/minify/source/browse/min/config.php?r=292#47).
  1. Paste your [debug mode](Debugging.md) comment block into a new post on the [minify mailing list](http://groups.google.com/group/minify).
