### Browsers, trust your cache

In all responses a `Cache-Control` header is sent, telling the browser it doesn't need to "check in" with the server for some period of time. The ideal request is the one that never leaves the browser!

### Convert a request to source objects

When the browser makes a request like `/min/g=css`, Apache rewrites this to `/min/index.php?g=css`, which calls Minify's front controller.

A separate controller then uses the querystring to establish a "sources" array, specifying exactly which objects (usually files) are to be included in the final output.

### Try browser cache

Minify finds the latest modification time of all the source objects (`filemtime` for files, so if you use a tool that doesn't update this, you might need to `touch` your modified files).

If the browser has sent an `If-Modified-Since` header, and it's valid (the given date is older than the most recent source), then a 304 header is returned, execution stops, and the browser uses its cache copy.

### Try server cache

Minify generates a unique cache ID for the particular set of sources and their options. This is used to maintain a cache (file usually) of the final output.

If the cache is "valid" (younger than the most recently modified source), then its content is sent along with a `Last-Modified` header with the most recent source's modification time, and execution stops.

### Minification has to be done

If any source is younger than the cache, the cache must be rebuilt from the minification process (slow, but infrequently done):

Minify processes each source with a "minifier" function (determined by the content type of the sources and source-specific options), combines them to a single string, saves this to the cache object, then serves it with the `Last-Modified` header as sbove.

#### Content encoding

Minify actually stores a gzipped version of each output in a second cache object. If the browser supports it, Minify streams the pre-compressed content straight from cache (disk usually) to the browser.