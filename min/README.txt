The files in this directory represent the default Minify setup designed to ease
integration with your site. Out-of-the-box, Minify can combine and minify files
and serve them with HTTP compression and cache headers.


RECOMMENDED

It's recommended to edit config.php to set $minifyCachePath to a writeable
directory on your system. This will slightly improve the performance of each
request.


MINIFYING A SINGLE FILE

Let's say you want to serve this file:
  http://example.com/wp-content/themes/default/default.css

Here's the "Minify URL" for this file:
  http://example.com/min/?f=wp-content/themes/default/default.css

In other words, the "f" argument is set to the file path from root without the 
initial "/". As CSS files may contain relative URIs, Minify will automatically
"fix" these by rewriting them as root relative.


COMBINING MULTIPLE FILES IN ONE DOWNLOAD

Separate the file paths given to "f" with commas.

Let's say you have CSS files at these URLs:
  http://example.com/scripts/jquery-1.2.6.js
  http://example.com/scripts/site.js

You can combine these files through Minify by requesting this URL:
  http://example.com/min/?f=scripts/jquery-1.2.6.js,scripts/site.js


SIMPLIFYING URLS WITH A BASE PATH

If you're combining files that share the same ancestor directory, you can use
the "b" argument to set the base directory for the "f" argument. Do not include
the leading or trailing "/" characters.

E.g., the following URLs will serve the exact same content:
  http://example.com/min/?f=scripts/jquery-1.2.6.js,scripts/site.js
  http://example.com/min/?b=scripts&f=jquery-1.2.6.js,site.js


USING THESE URLS IN HTML

In (X)HTML files, make sure to replace any "&" characters with "&amp;".



SPECIFYING ALLOWED DIRECTORIES

By default, Minify will serve any *.css/*.js files within the DOCUMENT_ROOT. If
you'd prefer to limit Minify's access to certain directories, set the 
$minifyAllowDirs array in config.php. E.g. to limit to the /js and 
/themes/default directories, use:

$minifyAllowDirs = array('//js', '//themes/default');


FASTER PERFORMANCE AND SHORTER URLS

For the best performance, edit groupsConfig.php to pre-specify groups of files 
to be combined under different keys. E.g., here's an example configuration in 
groupsConfig.php:

return array(
    'js' => array('//js/Class.js', '//js/email.js')
);

This pre-selects the following files to be combined under the key "js":
  http://example.com/js/Class.js
  http://example.com/js/email.js
  
You can now serve these files with this simple URL:
  http://example.com/min/?g=js
  
