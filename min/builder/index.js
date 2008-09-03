// @todo update test links when reordering, app instructions
var MUB = {
    _uid : 0
    ,newLi : function () {
        return '<li id="li' + MUB._uid + '">http://' + location.host + '<span>/</span><input type=text size=20>' 
        + ' <button title="Remove">x</button> <button title="Include Earlier">&uarr;</button>'
        + ' <button title="Include Later">&darr;</button> <a href=# target="_blank" '
        + 'title="Open this URL in a new window">test link</a></li>';
    }
    ,addLi : function () {
        $('#sources').append(MUB.newLi());
        var li = $('#li' + MUB._uid)[0];
        $('button[title=Remove]', li).click(function () {
            $(li).remove();
        });
        $('button[title$=Earlier]', li).click(function () {
            $(li).prev('li').find('input').each(function () {
                // this = previous li input
                var tmp = this.value;
                this.value = $('input', li).val();
                $('input', li).val(tmp);
            });
        });
        $('button[title$=Later]', li).click(function () {
            $(li).next('li').find('input').each(function () {
                // this = next li input
                var tmp = this.value;
                this.value = $('input', li).val();
                $('input', li).val(tmp);
            });
        });
        $('input', li).keyup(function () {
            $('a', li)[0].href = '/' + this.value;
        });
        ++MUB._uid;
    }
    ,update : function () {
        var sources = []
           ,ext = false
           ,fail = false;
        $('#sources input').each(function () {
            if (! fail && this.value && (m = this.value.match(/\.(css|js)$/))) {
                var thisExt = m[1];
                if (ext === false)
                    ext = thisExt; 
                else if (thisExt !== ext) {
                    fail = true;
                    return alert('extensions must match!');
                }
                if (-1 != $.inArray(this.value, sources)) {
                    fail = true;
                    return alert('duplicate file!');
                }
                sources.push(this.value);
            } 
        });
        if (fail || ! sources.length)
            return;
        var uri = '/min/?f=' + sources.join(',')
            ,uriH = uri.replace(/</, '&lt;').replace(/>/, '&gt;').replace(/&/, '&amp;');
        $('#uriA').html(uriH)[0].href = uri;
        $('#uriHtml').val(
            ext === 'js' 
            ? '<script type="text/javascript" src="' + uriH + '"></script>'
            : '<link type="text/css" rel="stylesheet" href="' + uriH + '" />'
        );
        $('table').show();
    }
    ,init : function () {
        $('#add a').click(MUB.addLi);
        $('#uriHtml').click(function () {
            this.select();
        }).focus(function () {
            this.select();
        });
        $('#update').click(MUB.update);
    }
};
window.onload = MUB.init;