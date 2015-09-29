If you test sites in a subdirectory (e.g. `http://localhost/testSite/`) rather than a virtualhost, then you'll need to adjust the way you use Minify to rewrite CSS correctly.

1. Place the following in `config.php`:

```php
// Set the document root to be the path of the "site root"
$min_documentRoot = substr(__FILE__, 0, -11);

// Set $sitePrefix to the path of the site from the webserver's real docroot
list($sitePrefix) = explode('/index.php', $_SERVER['SCRIPT_NAME'], 2);

// Prepend $sitePrefix to the rewritten URIs in CSS files
$min_symlinks['//' . ltrim($sitePrefix, '/')] = $min_documentRoot;
```

2. In the HTML, make your Minify URIs document-relative (e.g. `min/f=js/file.js` and `../min/f=js/file.js`), not root-relative.

Now the `min` application should operate correctly from a subdirectory and will serve files relative to your "site" root rather than the document root. E.g.

| **environment** | production | testing |
|:----------------|:-----------|:--------|
| **server document root** | `/home/mysite_com/www` | `/var/www` |
| **`$min_documentRoot` ("site root")** | `/home/mysite_com/www` | `/var/www/testSite` |
| **`$sitePrefix`** | (empty)    | `/testSite` |
| **Minify URL**  | `http://mysite.com/min/f=js/file1.js` | `http://localhost/testSite/min/f=js/file1.js` |
| **file served** | `/home/mysite_com/www/js/file1.js` | `/var/www/testSite/js/file1.js` |

Caveats:
  * This configuration may break the Builder application (located at `/min/builder/`) used to create Minify URIs, but you can still create them by hand.
  * Make sure you don't reset `$min_symlinks` to a different value lower in your config file.