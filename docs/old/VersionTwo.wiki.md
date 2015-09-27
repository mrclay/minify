# Minify 2

[Minify 2.0.0](http://code.google.com/p/minify/source/browse/tags/release_2.0.0/) was released May 22, 2008 and represents an architectural redesign of Minify's code and its usage. 2.0 is built as a library of classes allowing users to easily build customized minified-file servers; or add minification, HTTP encoding, or [conditional GET](http://fishbowl.pastiche.org/2002/10/21/http_conditional_get_for_rss_hackers) to existing projects.

The release includes 3 [example sites](http://code.google.com/p/minify/source/browse/tags/release_2.0.0/web/examples) to demostrate usage and [unit tests](http://code.google.com/p/minify/source/browse/tags/release_2.0.0/web/test/) you can run on your system.

## Documentation

Each PHP file is documented, but, for now, the [README file](http://code.google.com/p/minify/source/browse/tags/release_2.0.0/README) is the best reference for getting started with the library. This  [blog post](http://mrclay.org/index.php/2008/03/27/minifying-javascript-and-css-on-mrclayorg/) may also give you some ideas.

The best place for questions is the [minify Google group](http://groups.google.com/group/minify).

### Included HTTP Classes

The two HTTP utility classes, [HTTP\_ConditionalGet](http://code.google.com/p/minify/source/browse/lib/HTTP/ConditionalGet.php) and [HTTP\_Encoder](http://code.google.com/p/minify/source/browse/lib/HTTP/Encoder.php), are already fairly well-tested and include a set of test pages to see how they work. On the [Florida Automated Weather Network](http://fawn.ifas.ufl.edu/) site, these are used especially in scripts that serve data to our Flash components.

Here's an example of using both to conditionally serve a text file gzipped:
```
$cg = new HTTP_ConditionalGet(array(
    'lastModifiedTime' => filemtime($filepath)
    ,'isPublic' => true
));
$cg->sendHeaders();
if ($cg->cacheIsValid) {
    // client cache was valid, no content needed
    exit();
}
require 'HTTP/Encoder.php';
$he = new HTTP_Encoder(array(
    'content' => file_get_contents($filepath)
    ,'type' => 'text/plain'
));
$he->encode();
$he->sendAll();
```