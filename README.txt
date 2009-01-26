WELCOME TO MINIFY 2.1!

Minify is an HTTP content server. It compresses sources of content 
(usually files), combines the result and serves it with appropriate 
HTTP headers. These headers can allow clients to perform conditional 
GETs (serving content only when clients do not have a valid cache) 
and tell clients to cache the file for a period of time. 
More info: http://code.google.com/p/minify/


UPGRADING

See UPGRADING.txt for instructions.


INSTALLATION AND USAGE:

1. Place the /min/ directory as a child of your DOCUMENT_ROOT 
directory: i.e. you will have: /home/user/www/public_html/min

2. Open http://yourdomain/min/ in a web browser. This will forward
you to the Minify URI Builder application, which will help you
quickly start using Minify to serve content on your site.


UNIT TESTING:

1. Place the /min_unit_tests/ directory as a child of your DOCUMENT_ROOT 
directory: i.e. you will have: /home/user/www/public_html/min_unit_tests

2. To run unit tests, access: http://yourdomain/min_unit_tests/test_all.php

(If you wish, the other test_*.php files can be run to test individual
components with more verbose output.)

3. Remove /min_unit_tests/ from your DOCUMENT_ROOT when you are done.


EXTRAS:

The min_extras folder contains files for benchmarking using Apache ab on Windows
and a couple single-use tools. DO NOT place this on your production server.


FILE ENCODINGS

Minify *should* work fine with files encoded in UTF-8 or other 8-bit 
encodings like ISO 8859/Windows-1252. By default Minify appends
";charset=utf-8" to the Content-Type headers it sends. 

Leading UTF-8 BOMs are stripped from all sources to prevent 
duplication in output files, and files are converted to Unix newlines.

