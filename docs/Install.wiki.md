# Installation

Minify requires PHP 5.3+, `git`, and `composer`.

## Typical Installation

Clone the project into the `min/` directory inside your document root and install its dependencies:

```bash
cd /path/to/public_html
git clone https://github.com/mrclay/minify.git min
cd min
composer install --no-dev
```

**Note:** If you do this on localhost, make sure the `min/vendor/` directory gets deployed to production.

## Installing as a composer dependency

Add `"mrclay/minify": "~3.0.0"` to your site's composer.json, and `composer install`.

The following assumes your `vendor` directory is in your document root. Adjust the `MINIFY` path as needed:

```bash
cd /path/to/public_html
mkdir min
MIN=min/
MINIFY=vendor/mrclay/minify/
cp ${MINIFY}example.index.php ${MIN}index.php
cp ${MINIFY}.htaccess ${MIN}
cp ${MINIFY}config.php ${MIN}
cp ${MINIFY}groupsConfig.php ${MIN}
cp ${MINIFY}quick-test.js ${MIN}
cp ${MINIFY}quick-test.css ${MIN}
```

Edit `min/index.php` to remove the ``die()`` statement and adjust the `vendor` path as needed.

**Note:** This does not install the [URL builder](BuilderApp.wiki.md), but it's not necessary for operation.

## Verifing it works

You can verify it works via these two URLs:

* http://example.org/min/?f=min/quick-test.js
* http://example.org/min/?f=min/quick-test.css

If your server supports mod_rewrite, the `?` are not necessary:

* http://example.org/min/f=min/quick-test.js
* http://example.org/min/f=min/quick-test.css

## Having trouble?

Write the [Google Group](http://groups.google.com/group/minify) for help.

## More links

* [Usage instructions](UserGuide.wiki.md)
* [Cookbook](CookBook.wiki.md) for more advanced options
* [All docs](docs)
