## Version 2.1.5 (2012-03-10)
  * Removed XSS vulnerability
  * Disabled builder by default
  * command line tools to minify and rewrite URIs in CSS
  * upgrade (optional) JSMin+ library
  * more efficient JS minification when using CC/YUIC
  * Closure Compiler uses cURL when allow\_url\_fopen is off
  * Missing file notices when using groups

## Version 2.1.4b (2010-07-10)
  * Option to minify JS with Closure Compiler API w/ JSMin failover
  * Cookie/bookmarklet-based debug mode. No HTML editing!
  * Allows 1 file to be missing w/o complete failure
  * Combine multiple groups and files in single URI
  * More useful HTML helpers for writing versioned URIs
  * More detailed error logging, including minifier exceptions
  * Builder offers more helpful messages/PHP environment warnings
  * Bypass minification based on filename pattern. e.g. foo.min.js / foo-min.css
  * JSMin won't choke on common Closure compiler syntaxes (`i+ ++j`)
  * Better caching in IE6
  * Cache ids are influenced by group/file names
  * Debug mode for Javascript doesn't break on common XPath strings (Prototype 1.6)
  * Removed annoying maxFiles limit
  * mbstring.func\_overload usage is safer

## Version 2.1.3 (2009-06-30)
  * CSS fixes
    * A few URI rewriting bugs fixed
    * comment/whitespace removal no longer breaks some values
    * IE6 [pseudo-element selector bug](http://www.crankygeek.com/ie6pebug/) no longer triggered
  * HTTP fixes
    * Proper Expires handling in webkit (dropped "must-revalidate", which triggered a [webkit bug](http://mrclay.org/index.php/2009/02/24/safari-4-beta-cache-controlmust-revalidate-bug/))
    * ETag generation now valid ([must be unique when gzipped](https://issues.apache.org/bugzilla/show_bug.cgi?id=39727))
    * Vary header always sent when Accept-Encoding is sniffed
    * Dropped deflate encoding, since browser and proxy support [could be buggy](http://stackoverflow.com/questions/883841/).
  * File cache now works w/o setting `$min_cachePath`
  * No more 5.3 deprecation warnings: `split()` removed
  * API: Can set contentType Minify\_Source objects (fixes an annoying [caveat](http://groups.google.com/group/minify/msg/8446d32ee99a4961))
  * [Resolved Issue list](http://code.google.com/p/minify/issues/list?can=1&q=label%3ARelease-2.1.2%20status%3AVerified)

## Version 2.1.2 (2009-03-04)
  * Javascript fixes
    * Debug mode no longer confused by `*/*` in strings/RegExps (jQuery)
    * quote characters inside RegExp literals no longer cause exception
    * files ending in single-line comments no longer cause code loss
  * CSS: data: URLs no longer mangled
  * Optional error logging to Firefox's FirePHP extension
  * Unit tests to check for common DOCUMENT\_ROOT problems
    * DOCUMENT\_ROOT no longer overwritten on IIS servers
  * Builder app doesn't fail on systems without gzdeflate()
  * APC caching class included

## Version 2.1.1 (2008-10-19)
  * Bug fix release
  * Detection and workarounds for zlib.output\_compression and non-PHP encoding modules
  * Zlib not required (mod\_rewrite, et.al., can still be used for encoding)
  * HTML : More IE conditional comments preserved
  * Minify\_groupUri() utility fixed

## Version 2.1.0 (2008-09-18)
  * "min" default application for quick deployment
  * Minify URI Builder app & bookmarklet for quickly creating minify URIs
  * Relative URIs in CSS file are fixed automatically by default
  * "debug" mode for revealing original line #s in combined files
  * Better IIS support
  * Improved minifier classes:
    * JS: preserves IE conditional comments
    * CSS: smaller output, preserves more hacks and valid CSS syntax, shorter line lengths, other bug fixes
    * HTML: smaller output, shorter line lengths, other bug fixes
  * Default Cache-Control: max-age of 30 minutes
  * Conditional GETs supported even when max-age sent
  * Experimental memcache cache class (default is files)
  * Minify\_Cache\_File has flock()s (by default)
  * Workaround for Windows mtime reporting bug


## Version 2.0.0 (2008-05-22)
  * Complete code overhaul. Minify is now a PEAR-style class and toolkit for building customized minifying file servers.
  * Content-Encoding: deflate/gzip/compress, based on request headers
  * Expanded CSS and HTML minifiers with test cases
  * Easily plug-in 3rd-party minifiers (like Packer)
  * Plug-able front end controller allows changing the way files are chosen
  * Compression & encoding modules lazy-loaded as needed (304 responses use minimal code)
  * Separate utility classes for HTTP encoding and cache control

## Version 1.0.1 (2007-05-05)
  * Fixed various problems resolving pathnames when hosted on an NFS mount.
  * Fixed 'undefined constant' notice.
  * Replaced old JSMin library with a much faster custom implementation.

## Version 1.0.0 (2007-05-02)
  * First release.