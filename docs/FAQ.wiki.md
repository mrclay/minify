## Why do the CSS & HTML minifiers add so many line breaks?

TL;DR: Ignore them. They don't add to the output size and if you absolutely want all content on one line you will have to use another tool.

It's [rumored](https://github.com/yui/yuicompressor/blob/master/doc/README#L43) that some source control tools and old browsers don't like very long lines. Compressed files with shorter lines are also easier to diff.

Since both Minify classes are regex-based, it would be very difficult/error-prone to count characters then try to re-establish context to add line breaks. Instead, both classes trade 1 space for 1 line break (`\n`) wherever possible, adding breaks but without adding bytes.

If you can think of another safe & efficient way to limit lines in these two tools without adding bytes, please  submit a patch, but this is not something anyone should be worrying about.

## Minify doesn't compress as much as product XYZ. Why not?

Out of the box, Minify uses algorithms available in PHP, which frankly aren't as solid as competing products, but I consider them _good enough_. At this point I don't have time to work on further tweaking, so if you must have _perfect_ minification you can explore the CookBook to plug in other minifiers. If you'd like to propose specific tweaks in the algorithm (and don't have a patch), please propose these in the Google Group, not the issue tracker.

## How fast is it?

With Minify you will ideally serve _fewer_ requests, but Minify can be slower than your HTTPd serving flat files. If you have a high-traffic site with hundreds of simultaneous requests from new users, you should probably:

  * Use the [APC/Memcache adapters](CookBook.md).
  * Revision your Minify URIs (so far-off Expires headers will be sent). One way to do this is using [predefined groups](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/README.txt#69) and the [Minify\_groupUri()](http://code.google.com/p/minify/source/browse/tags/release_2.1.3/min/utils.php#13) utility function.
  * Place your HTTPd behind a [reverse proxy](http://www.squid-cache.org/Intro/why.dyn) to cache the Minify URLs.
  * Benchmark Minify on your development server before rolling out to production.

### Will it get faster?

Ideally, but a couple [other goals](ProjectGoals.md) come first. For Apache users we're designing a feature to enable [minified and pre-encoded files to be served directly from the HTTPd](http://mrclay.org/index.php/2008/05/25/apache-http-encoding-negotiation-notes/). Requests will not execute PHP at all and be blazingly fast (for varying definitions of "blazingly").

## How does it compare with other services?

Yahoo's [Combo Handler](http://yuiblog.com/blog/2008/07/16/combohandler/) and Google's [AJAX Libraries API](http://code.google.com/apis/ajaxlibs/) both serve content from their heavy-duty [CDN](http://en.wikipedia.org/wiki/Content_Delivery_Network)s and _potentially_ increase the chance that your visitor will already have a file in her browser cache. Neither service serves custom content that you provide. You may wish to use these services to serve popular libraries and Minify to serve your code.

## Is this where I get support for...

If you get a link to this page in response to a request for help, please make sure that you're using the software downloaded from [this project](http://code.google.com/p/minify/) (or [on github](https://github.com/mrclay/minify)), and have followed the [directions](UserGuide.md).

There are many projects with "minify" in the title/description but don't have anything to do with this project, or which many only use a few [components](ComponentClasses.md) from this project.

Although you may be able to get support for usage of the components, the [Google Group](http://groups.google.com/group/minify) members/project owners may not be able to offer any helpful advice with unrelated projects.

## Does it support gzip compression?

Yes. Based on the browser's Accept-Encoding header, Minify will serve content encoded with deflate or gzip.

## Does it work with PHP opcode caches like APC and eAccelerator?

Of course, and you can also use [APC for content caching](CookBook.md).

## Can it minify remote files/the output of dynamic scripts?

[Yes](http://code.google.com/p/minify/wiki/CustomSource#Non-File_Sources), but it's not a straightforward setup, and probably best avoided.

## Is there a minifier for HTML?

Yes, but only in the form of a PHP class: [Minify\_HTML](http://code.google.com/p/minify/source/browse/min/lib/Minify/HTML.php).
It also can accept callbacks to minify embedded STYLE and SCRIPT elements.

Since Minify\_HTML is not fast, there's no _easy way_ to integrate it into dynamic pages, and you'll have to search the archives for ideas of how to use it. One opportunity would be when storing HTML (assuming writes are infrequent); e.g., in a DB keep one copy for editing and one minified for serving.

Minify is not suited for _serving_ HTML pages on a site, though it can be done for small numbers of static pages. Look the the [Page controller](http://code.google.com/p/minify/source/browse/min/lib/Minify/Controller/Page.php).

## How does it ensure that the client can't request files it shouldn't have access to?

In 2.1, by default, Minify allows files to be specified using the URI, or using pre-configured sets of files. With URI-specified files, Minify is [very careful](Security.md) to serve only JS/CSS files that are already public on your server, but if you hide public directories--with .htaccess, e.g.--Minify can't know that. Obvious Tip: don't put sensitive info in JS/CSS files inside DOC\_ROOT :)

An included option can disable URI-specified files so Minify will serve only the pre-configured file sets.

## Is it used in production by any large-scale websites?

I'd love to know. 2.1.1 had 54K downloads and I know the library is powering several [plugins](http://mrclay.org/index.php/2009/01/10/minify-getting-out-there/) these days, at least 3 for WordPress.

## Can I use it with my commercial website or product?

Yes. Minify is distributed under the [New BSD License](http://www.opensource.org/licenses/bsd-license.php), which means that you're free to use, modify, and redistribute Minify or derivative works thereof, even for commercial purposes, as long as you comply with a few simple requirements. See the [LICENSE.txt](http://code.google.com/p/minify/source/browse/LICENSE.txt) file for details.

## How can I tell if my server cache is working?

The easiest way is to place a Minify URL directly in your browser's address bar and refresh (F5), which should override the client-side caching that Minify specifies and force Minify to send you a complete response. With cache working, this response should take 100ms or so. Without cache, multiple seconds. (You can get accurate response times using an HTTP inspector like Firebug.)

If you have file access to the server you can check your cache path directly for filenames beginning with "minify