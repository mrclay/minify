# Welcome to Minify!

Minify is an HTTP server for JS and CSS assets. It compresses and combines files
and serves it with appropriate headers, allowing conditional GET or long-Expires.

| *Before* | ![7 requests](http://mrclay.org/wp-content/uploads/2008/09/fiddler_before.png) |
|----------|-----------------------------------------------------------------|
| *After*  | ![2 requests](http://mrclay.org/wp-content/uploads/2008/09/fiddler_after.png)  |

The stats above are from a [brief walkthrough](http://mrclay.org/index.php/2008/09/19/minify-21-on-mrclayorg/) which shows how easy it is to set up Minify on an existing site. It eliminated 5 HTTP requests and reduced JS/CSS bandwidth by 70%.

Relative URLs in CSS files are rewritten to compensate for being served from a different directory.

## Support

Post to the [Google Group](http://groups.google.com/group/minify).

### Wordpress User?

Consider instead using a dedicated WordPress plugin for more deep integration and simpler installation. E.g.:
- [BWP Minify](http://wordpress.org/extend/plugins/bwp-minify/)
- [W3 Total Cache](http://wordpress.org/extend/plugins/w3-total-cache/)

Unfortunately we can't support the WordPress plugins here.

## Installation

See the [install guide](https://github.com/mrclay/minify/blob/master/docs/Install.wiki.md).

## Configuration & Usage

See the [user guide](https://github.com/mrclay/minify/blob/master/docs/UserGuide.wiki.md).

Minify also comes with a [URI Builder application](https://github.com/mrclay/minify/blob/master/docs/BuilderApp.wiki.md) that can help you write URLs
for use with Minify or configure groups of files.

See the [cookbook](https://github.com/mrclay/minify/blob/master/docs/CookBook.wiki.md) for more advanced options for minification.

More [docs are available](https://github.com/mrclay/minify/tree/master/docs).

## Unit Testing

1. Install dev deps via Composer: `composer install`
1. `composer test` or `phpunit`

## Warnings

* Minify is designed for efficiency, but, for very high traffic sites, it will probably serve files slower than your HTTPd due to the CGI overhead of PHP. See the [FAQ](https://github.com/mrclay/minify/blob/master/docs/FAQ.wiki.md#how-fast-is-it) and [CookBook](https://github.com/mrclay/minify/blob/master/docs/CookBook.wiki.md) for more info.
* If you combine a lot of CSS, watch out for [IE's 4096 selectors-per-file limit](http://stackoverflow.com/a/9906889/3779), affects IE 6 through 9.
* Minify *should* work fine with files encoded in UTF-8 or other 8-bit encodings like ISO 8859/Windows-1252. By default Minify appends ";charset=utf-8" to the Content-Type headers it sends.

## Acknowledgments

Minify was inspired by [jscsscomp](http://code.google.com/p/jscsscomp/) by Maxim Martynyuk and by the article [Supercharged JavaScript](http://www.hunlock.com/blogs/Supercharged_Javascript) by Patrick Hunlock.

The [JSMin library](http://www.crockford.com/javascript/jsmin.html) used for JavaScript minification was originally written by Douglas Crockford and was [ported to PHP](https://github.com/mrclay/jsmin-php) by Ryan Grove specifically for use in Minify.
