@SET PATH=%PATH%;C:\xampp\apache\bin

::SET ABCALL=ab -d -S -c 100 -n 2000 -H "Accept-Encoding: deflate, gzip" http://localhost
@SET ABCALL=ab -d -S -c 100 -n 2000 -H "Accept-Encoding: deflate, gzip" http://mc.dev/_3rd_party

::SET TXTVIEWER=notepad.exe
@SET TXTVIEWER="C:\Program Files\Notepad++\notepad++.exe"

@SET DELIM=TYPE _delimiter

DEL results.txt

:: baseline PHP
%ABCALL%/minify/web/ab_tests/ideal_php/before.php >> results.txt
@%DELIM% >> results.txt

:: 1.0 release
%ABCALL%/minify/web/ab_tests/v1.0/minify.php?files=before.js >> results.txt
@%DELIM% >> results.txt

:: Files controller
%ABCALL%/minify/web/ab_tests/minify/test_Files.php >> results.txt
@%DELIM% >> results.txt

:: Groups controller
%ABCALL%/minify/web/ab_tests/minify/test_Groups.php/test >> results.txt
@%DELIM% >> results.txt

:: Version1 controller
%ABCALL%/minify/web/ab_tests/minify/test_Version1.php?files=before.js >> results.txt
@%DELIM% >> results.txt

:: mod_deflate
%ABCALL%/minify/web/ab_tests/mod_deflate/before.js >> results.txt
@%DELIM% >> results.txt

:: type-map
%ABCALL%/minify/web/ab_tests/type-map/before.js.var >> results.txt
@%DELIM% >> results.txt

FINDSTR "Path: Length: Requests --" results.txt > results_summary.txt

START %TXTVIEWER% results_summary.txt