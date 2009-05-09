The files in this directory represent a new way to serve minified files via the 
Apache file server. This will yield much better performance than Minify can
deliver when all requests are routed through PHP.


REQUIREMENTS

Apache server with mod_negotiation and mod_rewrite


GETTING STARTED

The "min" directory must already be set up within the DOCUMENT_ROOT.

Place the "mincache" directory in DOCUMENT_ROOT.

Request this URL: http://yourserver.com/mincache/f/test.mcss
(It should return a CSS file with a "Success" comment.)

Place CSS/JS source files in "/mincache/src" and/or specify groups in 
"/min/groupsConfig.php"


UPDATING FILES

After changing any source files:

1. Delete any associated ".mcss" or ".mjs" files inside the "f" and "g" 
   directories. (the next request will trigger PHP to rebuild them.)

2. Add or update a revision number within the URIs in your HTML files. This is
   recommended because we send a far-future Expires header and you want users
   to see your changes.
   E.g. /mincache/f/one,two.mjs      => /mincache/f/one,two_2.mjs
        /mincache/g/grpName_123.mcss => /mincache/g/grpName_124.mcss


HOW THIS WORKS

In your HTML files you'll refer to minify URIs like:
  /mincache/f/one,two_12345.mjs
  /mincache/g/groupName_12345.mcss

.htaccess will internally strip the revision numbers (_\d+) from the URIs.

If the files exist, they'll be served directly by Apache and with deflate
encoding where supported.

If not, .htaccess will call /mincache/gen.php to generate the files.


URIS IN THE "/f" DIRECTORY

For these URIs, Minify will combine files from a single specified source 
directory, "/mincache/src" by default. E.g.:
  /mincache/f/one,two.mcss = /mincache/src/one.css 
                           + /mincache/src/two.css
  /mincache/f/jQuery.1.3,site.mjs = /mincache/src/jQuery.1.3.js 
                                  + /mincache/src/site.js


URIS IN THE "/g" DIRECTORY

(To be implemented)


QUESTIONS?

http://groups.google.com/group/minify