:: Generates Minify documentation using PhpDocumentor.
@echo off
phpdoc -ti "Minify docs" -p on -d ../lib -t ../docs -o html:smarty:php