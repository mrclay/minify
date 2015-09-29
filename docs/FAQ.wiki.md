## Minify (JSMin) doesn't compress as much as product XYZ. Why not?

The simple JSMin algorithm is the most reliable in PHP, but check the [CookBook](CookBook.wiki.md) to plug in other minifiers.

## How fast is it?

Certainly not as fast as an HTTPd serving flat files. On a high-traffic site:

  * **Use a reverse proxy** to cache the Minify URLs. This is by far the most important tip.
  * Revision your Minify URIs (so far-off Expires headers will be sent). One way to do this is using [groups](UserGuide.wiki.md#using-groups-for-nicer-urls) and the [Minify_groupUri()](UserGuide.wiki.md#far-future-expires-headers) utility function. Without this, clients will re-request Minify URLs every 30 minutes to check for updates.
  * Use the [APC/Memcache adapters](CookBook.wiki.md).
  
## Does it support gzip compression?

Yes. Based on the browser's Accept-Encoding header.

## Does it work with PHP opcode caches?

Yes, and you can also use [APC for content caching](CookBook.wiki.md).

## Can it minify remote files/the output of dynamic scripts?

[Yes](CustomSource.wiki.md#non-file-sources), but it's not a straightforward setup, and probably best avoided.

## Is there a minifier for HTML?

The class `Minify_HTML` can do this (and minify embedded STYLE and SCRIPT elements), but it's too slow to use directly. You'd want to integrate it into a system that caches the output. E.g., in a CMS, keep one copy for editing and one minified for serving.

## How does it ensure that the client can't request files it shouldn't have access to?

Minify allows files to be specified using the URI, or using pre-configured sets of files. With URI-specified files, Minify is very careful to serve only JS/CSS files that are already public on your server, but if you hide public directories--with .htaccess, e.g.--Minify can't know that. Obvious Tip: don't put sensitive info in JS/CSS files inside DOC_ROOT :)

An included option can disable URI-specified files so Minify will serve only the pre-configured file sets.

## Is it used in production by any large-scale websites?

The libraries are used in many CMS's and frameworks, but the use of `index.php` to serve URLs like http://example.com/min/f=hello.js probably is rare. Minify is made to drop in place to boost small to medium sites not already built for performance. 

Version 2.1.1 had 54K downloads.

## Can I use it with my commercial website or product?

Yes. Minify is distributed under the [New BSD License](http://www.opensource.org/licenses/bsd-license.php).

## How can I tell if my server cache is working?

The easiest way is to place a Minify URL directly in your browser's address bar and refresh (F5), which should override the client-side caching that Minify specifies and force Minify to send you a complete response. With cache working, this response should take 100ms or so. Without cache, it could be multiple seconds.

If you have file access to the server you can check your cache path directly for filenames beginning with `minify_`.