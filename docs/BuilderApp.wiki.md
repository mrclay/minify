Minify ships with "Builder", a simple Javascript app for constructing URIs to use with Minify. ([screenshots of the 2.1.0 version](http://www.mrclay.org/index.php/2008/09/19/minify-21-on-mrclayorg/))

It also does some run-time checks of your PHP and Minify configuration to look for problematic settings like [auto-encoding](http://code.google.com/p/minify/wiki/CommonProblems#Output_is_distorted/random_chars).

After installation, this is found at **`http://example.com/min/builder/`**

You must enable it by editing `min/config.php` and setting `$min_enableBuilder = true;`

After use, you should disable it by resetting `$min_enableBuilder = false;`