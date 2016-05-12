"Min" is the front end application `index.php` that handles requests to `http://example.com/min/` and responds with compressed/combined content.

When the documentation refers to "Minify" it usually means this application, but sometimes refers to the [ComponentClasses](ComponentClasses.wiki.md).

User-configurable files:

  * `/config.php`: general configuration
  * `/groupsConfig.php`: configuration of pre-defined groups of files

Other files of interest:

  * `/.htaccess`: rewrites URLs for the front controller
  * `/index.php`: front controller
  * `/lib/Minify/Controller/MinApp.php`: determines which files are combined based on `$_GET`, sets some default options
