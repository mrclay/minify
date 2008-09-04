@SET PATH=%PATH%;C:\xampp\apache\bin

@SET DOMAIN=http://mc.dev

@SET ABCALL=ab -d -S -c 100 -n 2000 -H "Accept-Encoding: deflate, gzip"
:: for priming cache
@SET ABPRIM=ab -d -S -c 1 -n 2 -H "Accept-Encoding: deflate, gzip"

@SET ABTESTS=%DOMAIN%/min_extras/ab_tests

::@SET TXTVIEWER=notepad.exe
@SET TXTVIEWER="C:\Program Files\Notepad++\notepad++.exe"

@SET DELIM=TYPE _delimiter

DEL results.txt

:: prime caches (though some may not need it)
%ABPRIM% %ABTESTS%/ideal_php/before.php
%ABPRIM% %ABTESTS%/v1.0/minify.php?files=before.js
%ABPRIM% %ABTESTS%/minify/test_Files.php
%ABPRIM% %ABTESTS%/minify/test_Files_Memcache.php
%ABPRIM% %ABTESTS%/minify/test_Groups.php/test
%ABPRIM% %ABTESTS%/minify/test_Version1.php?files=before.js
%ABPRIM% %DOMAIN%/min/?f=min_extras/ab_tests/minify/before.js
%ABPRIM% %ABTESTS%/mod_deflate/before.js
%ABPRIM% %ABTESTS%/type-map/before.js.var

:: baseline PHP
%ABCALL% %ABTESTS%/ideal_php/before.php >> results.txt
@%DELIM% >> results.txt

:: 1.0 release
%ABCALL% %ABTESTS%/v1.0/minify.php?files=before.js >> results.txt
@%DELIM% >> results.txt

:: Files controller
%ABCALL% %ABTESTS%/minify/test_Files.php >> results.txt
@%DELIM% >> results.txt

:: Files controller w/ Memcache as cache
%ABCALL% %ABTESTS%/minify/test_Files_Memcache.php >> results.txt
@%DELIM% >> results.txt

:: Groups controller
%ABCALL% %ABTESTS%/minify/test_Groups.php/test >> results.txt
@%DELIM% >> results.txt

:: Version1 controller
%ABCALL% %ABTESTS%/minify/test_Version1.php?files=before.js >> results.txt
@%DELIM% >> results.txt

::/min application
%ABCALL% %DOMAIN%/min/?f=min_extras/ab_tests/minify/before.js >> results.txt
@%DELIM% >> results.txt

:: mod_deflate
%ABCALL% %ABTESTS%/mod_deflate/before.js >> results.txt
@%DELIM% >> results.txt

:: type-map
%ABCALL% %ABTESTS%/type-map/before.js.var >> results.txt
@%DELIM% >> results.txt

FINDSTR "Path: Length: Requests --" results.txt > results_summary.txt

START %TXTVIEWER% results_summary.txt