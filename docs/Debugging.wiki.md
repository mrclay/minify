# Server Errors

| **Code** | **Most likely cause** |
|:---------|:----------------------|
| 400      | Controller failed to return valid set of sources to serve |
| 500      | Minifier threw exception (e.g. JSMin choked on syntax) |

You can find details by enabling FirePHP logging:

  1. Install/enable FirePHP for [Firefox](https://addons.mozilla.org/en-US/firefox/addon/6149) or [Chrome](https://chrome.google.com/webstore/detail/firephp4chrome/gpgbmonepdpnacijbbdijfbecmgoojma?hl=en-US).
  1. Open the Chrome DevTools/Firebug console
  1. Set `$min_errorLogger = true;` in config.php
  1. Reload the Minify URL

Hopefully you'll see the error appear:

```
Minify: Something bad happened!
```

# Javascript/CSS Problems

When Javascript errors occur, or URIs in CSS files are incorrectly rewritten, enable "debug mode" to ease debugging combined files:

  1. Set `$min_allowDebugFlag = true;` in config.php
  1. Append `&debug` to the Minify URI. E.g. `/min/?f=script1.js,script2.js&debug` (or use the bookmarklet provided by /min/builder/)

In "debug mode":

  * comments are inserted into the output showing you line numbers in the original file(s)
  * no minification is performed
  * In CSS, URI rewriting _is_ performed
  * In CSS, a leading comment shows how URIs were rewritten.

Example: a combination of two Javascript files in debug mode

```js
/* firstFile.js */

/* 1  */ (function () {
/* 2  */ 	if (window.foo) {
...
/* 11 */ })();

;
/* secondFile.js */

/* 1   */ var Foo = window.Foo || {};
/* 2   */
...
```

Example: Top of CSS output in debug mode

```
docRoot    : M:\home\foo\www
currentDir : M:\home\foo\www\css

file-relative URI  : typography.css
path prepended     : M:\home\foo\www\css\typography.css
docroot stripped   : \css\typography.css
traversals removed : /css/typography.css

file-relative URI  : ../images/bg.jpg
path prepended     : M:\home\foo\www\css\..\images\bg.jpg
docroot stripped   : \css\..\images\bg.jpg
traversals removed : /images/bg.jpg
```

### Tips for handling Javascript errors

  * Use the latest version (2.1.4 beta as of Dec 2010)
  * Try [debug mode](#javascriptcss-problems) to make the combined file more readable (and error locations findable)
  * Find out if other browsers have the same error
  * For pre-minified files, make the filenames end in `.min.js` or `-min.js`, which will prevent Minify from altering them
  * Test your scripts in [JSLint](http://www.jslint.com/).

## See Also

  * [CommonProblems](CommonProblems.wiki.md)
