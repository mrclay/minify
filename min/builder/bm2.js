javascript:(function(){
    var d = document
       ,c = d.cookie
       ,m = c.match(/\bminifyDebug=([^; ]+)/)
       ,v = m ? decodeURIComponent(m[1]) : ''
       ,p = prompt('Debug Minify URIs on ' + location.hostname + ' which contain:'
                 + '\n(empty for none, space = OR, * = any string, ? = any char)', v)
    ;
    if (p === null) return;
    p = p.replace(/^\s+|\s+$/, '');
    v = (p === '')
        ? 'minifyDebug=; expires=Fri, 27 Jul 2001 02:47:11 UTC; path=/'
        : 'minifyDebug=' + encodeURIComponent(p) + '; path=/';
    d.cookie = v;
})();
