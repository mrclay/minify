## 1. Be easy to implement

A PHP user should be able to quickly start serving JS/CSS much more optimally.

## 2. Be extensible/versatile

The user should be able to work Minify into an environment without modifying Minify's source. Right now this should be easy on the controller side, but the Minify class is static, which is fast, but may hinder extensibility.

## 3. Be fast as possible

With the release of 2.0.2 Minify will be much faster, outperforming even Apache's mod\_deflate (which apparently doesn't cache the encoded content like mod\_gzip does).

Since testing has shown that [pre-encoding files and using type-maps on Apache is blazingly fast](http://mrclay.org/index.php/2008/06/03/pre-encoding-vs-mod_deflate/), Minify should work towards maintaining builds of pre-encoded files and letting Apache serve them. It's not clear if there's something similar to type-maps on lighttpd, but it's worth looking into.