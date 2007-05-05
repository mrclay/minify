; Generates Minify documentation using PhpDocumentor.
@echo off
phpdoc -f ../minify.php,../lib/jslib.php -t ../docs -o html:smarty:php