"Min" is the application included in Minify that handles requests to `http://example.com/min/` and responds with compressed/combined content.

When the documentation refers to "Minify" it usually means this application, but sometimes refers to the ComponentClasses.

User-configurable files:

  * `/min/config.php`: general configuration
  * `/min/groupsConfig.php`: configuration of pre-defined groups of files

Other files of interest:

  * `/min/.htaccess`: rewrites URLs for the front controller
  * `/min/index.php`: front controller
  * `/min/lib/Minify/Controller/MinApp.php`: determines which files are combined based on `$_GET`, sets some default options