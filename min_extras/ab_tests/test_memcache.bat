@SET PATH=%PATH%;C:\xampp\apache\bin;C:\Program Files\GnuWin32\bin

@SET DOMAIN=http://localhost/minify

@SET ABTESTS=%DOMAIN%/min_extras/ab_tests

DEL results.txt

DEL memcached_stats.txt

ab -d -S -c 100 -n 1000 %ABTESTS%/minify/test_memcache.php >> results.txt

START "C:\Program Files\Notepad++\notepad++.exe" memcached_stats.txt