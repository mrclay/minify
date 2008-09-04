// @todo update test links when reordering, app instructions
var MUB = {
    _uid : 0
    ,newLi : function () {
        return '<li id="li' + MUB._uid + '">http://' + location.host + '/<input type=text size=20>' 
        + ' <button title="Remove">x</button> <button title="Include Earlier">&uarr;</button>'
        + ' <button title="Include Later">&darr;</button> <span></span></li>';
    }
    ,addLi : function () {
        $('#sources').append(MUB.newLi());
        var li = $('#li' + MUB._uid)[0];
        $('button[title=Remove]', li).click(function () {
            var hadValue = !!$('input', li)[0].value;
            $(li).remove();
            hadValue && MUB.update();
        });
        $('button[title$=Earlier]', li).click(function () {
            $(li).prev('li').find('input').each(function () {
                // this = previous li input
                var tmp = this.value;
                this.value = $('input', li).val();
                $('input', li).val(tmp);
                MUB.updateAllTestLinks();
                MUB.update();
            });
        });
        $('button[title$=Later]', li).click(function () {
            $(li).next('li').find('input').each(function () {
                // this = next li input
                var tmp = this.value;
                this.value = $('input', li).val();
                $('input', li).val(tmp);
                MUB.updateAllTestLinks();
                MUB.update();
            });
        });
        ++MUB._uid;
    }
    ,liUpdateTestLink : function () { // call in context of li element
        if (! $('input', this)[0].value) 
            return;
        var li = this;
        $('span', this).html('');
        var url = 'http://' + location.host + '/' 
                + $('input', this)[0].value.replace(/^\//, '');
        $.ajax({
            url : url
            ,complete : function (xhr, stat) {
                $('span', li).html(
                    'success' == stat
                        ? '&#x2713;'
                        : '<b>file not found!</b>'
                );
            }
            ,dataType : 'text'
        });
    }
    ,updateAllTestLinks : function () {
        $('#sources li').each(MUB.liUpdateTestLink);
    }
    ,getCommonCharAtPos : function (arr, pos) {
        var i
           ,l = arr.length
           ,c = arr[0].charAt(pos);
        if (c === '' || l === 1)
            return c;
        for (i = 1; i < l; ++i)
            if (arr[i].charAt(pos) !== c)
                return '';
        return c;
    }
    ,getBestUri : function (sources) {
        var pos = 0
           ,base = ''
           ,c;
        while (true) {
            c = MUB.getCommonCharAtPos(sources, pos);
            if (c === '')
                break;
            else
                base += c;
            ++pos;
        }
        base = base.replace(/[^\/]+$/, '');
        var uri = '/min/?f=' + sources.join(',');
        if (base.charAt(base.length - 1) === '/') {
            // we have a base dir!
            var basedSources = sources
               ,i
               ,l = sources.length;
            for (i = 0; i < l; ++i) {
                basedSources[i] = sources[i].substr(base.length);
            }
            base = base.substr(0, base.length - 1);
            var bUri = '/min/?b=' + base + '&f=' + basedSources.join(',');
            //window.console && console.log([uri, bUri]);
            uri = uri.length < bUri.length
                ? uri
                : bUri;
        }
        return uri;
    }
    ,update : function () {
        MUB.updateAllTestLinks();
        var sources = []
           ,ext = false
           ,fail = false;
        $('#sources input').each(function () {
            var m, val;
            if (! fail && this.value && (m = this.value.match(/\.(css|js)$/))) {
                var thisExt = m[1];
                if (ext === false)
                    ext = thisExt; 
                else if (thisExt !== ext) {
                    fail = true;
                    return alert('extensions must match!');
                }
                this.value = this.value.replace(/^\//, '');
                if (-1 != $.inArray(this.value, sources)) {
                    fail = true;
                    return alert('duplicate file!');
                }
                sources.push(this.value);
            } 
        });
        if (fail || ! sources.length)
            return;
        var uri = MUB.getBestUri(sources)
           ,uriH = uri.replace(/</, '&lt;').replace(/>/, '&gt;').replace(/&/, '&amp;');
        $('#uriA').html(uriH)[0].href = uri;
        $('#uriHtml').val(
            ext === 'js' 
            ? '<script type="text/javascript" src="' + uriH + '"></script>'
            : '<link type="text/css" rel="stylesheet" href="' + uriH + '" />'
        );
        $('#uriTable').show();
    }
    ,init : function () {
        $('#sources').html('');
        $('#add button').click(function () {
            MUB.addLi();
            MUB.updateAllTestLinks();
            $('#update').show().click(MUB.update);
        });
        $('#uriHtml').click(function () {
            this.select();
        }).focus(function () {
            this.select();
        });
    }
};
window.onload = MUB.init;