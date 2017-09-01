This was quickly converted from an e-mail, please consider it "temporary".

## Each file specified by `$_GET['f']` must:

  * Have the [same extension, either "css" or "js"](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/lib/Minify/Controller/MinApp.php#66),
  * Exist, and...
  * Have a [realpath() within a whitelist of subdirectories](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/lib/Minify/Controller/Base.php#122).

The default whitelist contains only DOCUMENT\_ROOT, but can be [specified](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/config.php#57).

Then, a few more steps just to be paranoid:

  * If a base was given by `$_GET['b']`, [it can't have ".."](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/lib/Minify/Controller/MinApp.php#84).
  * `$_GET['f']` [must not contain "//", "\", or "./"](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/lib/Minify/Controller/MinApp.php#64).
  * There can be [no duplicates](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/lib/Minify/Controller/MinApp.php#77) and only a [limited number of files](http://code.google.com/p/minify/source/browse/tags/release_2.1.1/min/config.php#73) can be specified.