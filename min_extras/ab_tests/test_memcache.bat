@SET PATH=%PATH%;C:\xampp\apache\bin

@SET DOMAIN=http://mc.dev

@SET ABTESTS=%DOMAIN%/min_extras/ab_tests

ab -d -S -c 1 -n 2 -H "Accept-Encoding: deflate, gzip" %ABTESTS%/minify/test_Files_Memcache.php

DEL results.txt

ab -d -S -c 100 -n 1000 -H "Accept-Encoding: deflate, gzip" %ABTESTS%/minify/test_Files_Memcache.php >> results.txt

START "C:\Program Files\Notepad++\notepad++.exe" results.txt