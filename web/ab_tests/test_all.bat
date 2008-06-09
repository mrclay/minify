@SET PATH=%PATH%;C:\xampp\apache\bin

@REM SET ABCALL=ab -d -S -c 100 -n 2000 -H "Accept-Encoding: deflate, gzip" http://localhost
@SET ABCALL=ab -d -S -c 100 -n 2000 -H "Accept-Encoding: deflate, gzip" http://mc.dev/_3rd_party

@SET DELIM=TYPE _delimiter

DEL results.txt

@REM baseline PHP
%ABCALL%/minify/web/ab_tests/ideal_php/before.php >> results.txt
%DELIM% >> results.txt

@REM 1.0 release
%ABCALL%/minify/web/ab_tests/v1.0/minify.php?files=before.js >> results.txt
%DELIM% >> results.txt

@REM Files controller
%ABCALL%/minify/web/ab_tests/minify/test_Files.php >> results.txt
%DELIM% >> results.txt

@REM Version1 controller
%ABCALL%/minify/web/ab_tests/minify/test_Version1.php?files=before.js >> results.txt
%DELIM% >> results.txt

@REM mod_deflate
%ABCALL%/minify/web/ab_tests/mod_deflate/before.js >> results.txt
%DELIM% >> results.txt

@REM type-map
%ABCALL%/minify/web/ab_tests/type-map/before.js.var >> results.txt
%DELIM% >> results.txt

FINDSTR "Path: Length: Requests --" results.txt > results_summary.txt

notepad results_summary.txt