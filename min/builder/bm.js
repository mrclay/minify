javascript:(function() {
    var d = document
       ,uris = []
       ,i = 0
       ,o
       ,home = (location + '').split('/').splice(0, 3).join('/') + '/';
    function add(uri) {
        return (0 === uri.indexOf(home))
            && (!/[\?&]/.test(uri))
            && uris.push(escape(uri.substr(home.length)));
    };
    function sheet(ss) {
        // we must check the domain with add() before accessing ss.cssRules
        // otherwise a security exception will be thrown
        if (ss.href && add(ss.href) && ss.cssRules) {
            var i = 0, r;
            while (r = ss.cssRules[i++])
                r.styleSheet && sheet(r.styleSheet);
        }
    };
    while (o = d.getElementsByTagName('script')[i++])
        o.src && !(o.type && /vbs/i.test(o.type)) && add(o.src);
    i = 0;
    while (o = d.styleSheets[i++]) 
        sheet(o);
    if (uris.length)
        window.open('%BUILDER_URL%#' + uris.join(','));
    else
        alert('No js/css files found with URLs within "' 
            + home.split('/')[2]
            + '".\n(This tool is limited to URLs with the same domain.)');
})();