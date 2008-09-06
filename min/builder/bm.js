javascript:(function() {
    function add(uri) {
        (0 === uri.indexOf(home))
        && (!/[\?&]/.test(uri))
        && uris.push(escape(uri.substr(home.length)));
    }
    function sheet(ss) {
        ss.href && add(ss.href);
        if (ss.cssRules) {
            var i = 0, r;
            while (r = ss.cssRules[i++])
                r.styleSheet && sheet(r.styleSheet);
        }
    }    
    var d = document
       ,uris = []
       ,i = 0
       ,o
       ,home = (location + '').split('/').splice(0, 3).join('/') + '/';
    while (o = d.getElementsByTagName('script')[i++])
        o.src && !(o.type && /vbs/i.test(o.type)) && add(o.src);
    i = 0;
    while (o = d.styleSheets[i++]) 
        sheet(o);
    if (uris.length)
        window.open('%BUILDER_URL%#' + uris.join(','));
    else
        alert('No minifiable resources found.');
})();