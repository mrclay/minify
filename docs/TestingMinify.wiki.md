# Unit Testing

0. If you haven't already, install Minify using the UserGuide.

1. Copy the "min\_unit\_tests" directory directly into your DOCUMENT\_ROOT.

2. Browse to http://example.com/min_unit_tests/test_all.php

You should see a list of "PASS"es. You can run the individual test PHP files in http://example.com/min_unit_tests/ for more verbose output.

## Common Problems

### !WARN: environment : Local HTTP request failed. Testing cannot continue.

[test\_environment.php](http://code.google.com/p/minify/source/browse/min_unit_tests/test_environment.php) makes a few local HTTP requests to sniff for `zlib.output_compression` and other auto-encoding behavior, which may break Minify's output. This warning will appear if `allow_url_fopen` is disabled in php.ini, but **does not** necessarily mean there is a problem.

If Minify seems to work fine, ignore the warning. If Minify produces garbled output, enable `allow_url_fopen` in php.ini and re-run the tests. The tests may be able to tell you if PHP or your server is automatically encoding output.

Unless you need it in other scripts, disable `allow_url_fopen` once the issue is resolved. Minify does not need it.