/*!
 * Minify URI Builder
 */
var MUB = {
    _uid : 0,
    _minRoot : '/min/?',
    checkRewrite : function () {
        var testUri = location.pathname.replace(/\/[^\/]*$/, '/rewriteTest.js').substr(1);
        function fail() {
            $('#minRewriteFailed')[0].className = 'topNote';
        }
        $.ajax({
            url : '../f=' + testUri + '&' + (new Date()).getTime(),
            success : function (data) {
                if (data === '1') {
                    MUB._minRoot = '/min/';
                    $('span.minRoot').html('/min/');
                } else
                    fail();                
            },
            error : fail
        });
    },
    /**
     * Get markup for new source LI element
     */
    newLi : function () {
        return '<li id="li' + MUB._uid + '">' + location.protocol + '//' + location.host + '/<input type=text size=20>' +
        ' <button class="btn btn-danger btn-sm" title="Remove">x</button> <button class="btn btn-default btn-sm" title="Include Earlier">&uarr;</button>' +
        ' <button class="btn btn-default btn-sm" title="Include Later">&darr;</button> <span></span></li>';
    },
    /**
     * Add new empty source LI and attach handlers to buttons
     */
    addLi : function () {
        $('#sources').append(MUB.newLi());
        var li = $('#li' + MUB._uid)[0];
        $('button[title=Remove]', li).click(function () {
            $('#results').addClass('hide');
            var hadValue = !!$('input', li)[0].value;
            $(li).remove();
        });
        $('button[title$=Earlier]', li).click(function () {
            $(li).prev('li').find('input').each(function () {
                $('#results').addClass('hide');
                // this = previous li input
                var tmp = this.value;
                this.value = $('input', li).val();
                $('input', li).val(tmp);
                MUB.updateAllTestLinks();
            });
        });
        $('button[title$=Later]', li).click(function () {
            $(li).next('li').find('input').each(function () {
                $('#results').addClass('hide');
                // this = next li input
                var tmp = this.value;
                this.value = $('input', li).val();
                $('input', li).val(tmp);
                MUB.updateAllTestLinks();
            });
        });
        ++MUB._uid;
    },
    /**
     * In the context of a source LI element, this will analyze the URI in
     * the INPUT and check the URL on the site.
     */
    liUpdateTestLink : function () { // call in context of li element
        if (! $('input', this)[0].value) 
            return;
        var li = this;
        $('span', this).html('');
        var url = location.protocol + '//' + location.host + '/' +
                $('input', this)[0].value.replace(/^\//, '');
        $.ajax({
            url : url,
            complete : function (xhr, stat) {
                if ('success' === stat)
                    $('span', li).html('<a href="#" class="btn btn-success btn-sm disabled">&#x2713;</a>');
                else {
                    $('span', li).html('<button class="btn btn-warning btn-sm"><b>404! </b> recheck</button>')
                        .find('button').click(function () {
                            MUB.liUpdateTestLink.call(li);
                        });
                }
            },
            dataType : 'text'
        });
    },
    /**
     * Check all source URLs
     */
    updateAllTestLinks : function () {
        $('#sources li').each(MUB.liUpdateTestLink);
    },
    /**
     * In a given array of strings, find the character they all have at
     * a particular index
     * @param Array arr array of strings
     * @param Number pos index to check
     * @return mixed a common char or '' if any do not match
     */
    getCommonCharAtPos : function (arr, pos) {
        var i,
            l = arr.length,
            c = arr[0].charAt(pos);
        if (c === '' || l === 1)
            return c;
        for (i = 1; i < l; ++i)
            if (arr[i].charAt(pos) !== c)
                return '';
        return c;
    },
    /**
     * Get the shortest URI to minify the set of source files
     * @param Array sources URIs
     */
    getBestUri : function (sources) {
        var pos = 0,
            base = '',
            c;
        while (true) {
            c = MUB.getCommonCharAtPos(sources, pos);
            if (c === '')
                break;
            else
                base += c;
            ++pos;
        }
        base = base.replace(/[^\/]+$/, '');
        var uri = MUB._minRoot + 'f=' + sources.join(',');
        if (base.charAt(base.length - 1) === '/') {
            // we have a base dir!
            var basedSources = sources,
                i,
                l = sources.length;
            for (i = 0; i < l; ++i) {
                basedSources[i] = sources[i].substr(base.length);
            }
            base = base.substr(0, base.length - 1);
            var bUri = MUB._minRoot + 'b=' + base + '&f=' + basedSources.join(',');
            //window.console && console.log([uri, bUri]);
            uri = uri.length < bUri.length ? uri : bUri;
        }
        return uri;
    },
    /**
     * Create the Minify URI for the sources
     */
    update : function () {
        MUB.updateAllTestLinks();
        var sources = [],
            ext = false,
            fail = false,
            markup;
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
                if (-1 !== $.inArray(this.value, sources)) {
                    fail = true;
                    return alert('duplicate file!');
                }
                sources.push(this.value);
            } 
        });
        if (fail || ! sources.length)
            return;
        $('#groupConfig').val("    'keyName' => array('//" + sources.join("', '//") + "'),");
        var uri = MUB.getBestUri(sources),
            uriH = uri.replace(/</, '&lt;').replace(/>/, '&gt;').replace(/&/, '&amp;');
        $('#uriA').html(uriH)[0].href = uri;
        if (ext === 'js') {
            markup = '<script type="text/javascript" src="' + uriH + '"></script>';
        } else {
            markup = '<link type="text/css" rel="stylesheet" href="' + uriH + '" />';
        }
        $('#uriHtml').val(markup);
        $('#results').removeClass('hide');
    },
    /**
     * Handler for the "Add file +" button
     */
    addButtonClick : function () {
        $('#results').addClass('hide');
        MUB.addLi();
        MUB.updateAllTestLinks();
        $('#update').removeClass('hide').click(MUB.update);
        $('#sources li:last input')[0].focus();
    },
    /**
     * Runs on DOMready
     */
    init : function () {
        $('#jsDidntLoad').remove();
        $('#app').removeClass('hide');
        $('#sources').html('');
        $('#add button').click(MUB.addButtonClick);
        // make easier to copy text out of
        $('#uriHtml, #groupConfig, #symlinkOpt').click(function () {
            this.select();
        }).focus(function () {
            this.select();
        });
        $('a.ext').attr({target:'_blank'});
        if (location.hash) {
            // make links out of URIs from bookmarklet
            $('#getBm').addClass('hide');
            var i = 0, found = location.hash.substr(1).split(','), l = found.length;
            $('#bmUris').html('<p><strong>Found by bookmarklet:</strong> /</p>');
            var $p = $('#bmUris p');
            for (; i < l; i++) {
                $p.append($('<a href=#></a>').text(found[i])[0]);
                if (i < (l - 1)) {
                    $p.append(', /');
                }
            }
            $('#bmUris a').click(function () {
                MUB.addButtonClick();
                $('#sources li:last input').val(this.innerHTML);
                MUB.liUpdateTestLink.call($('#sources li:last')[0]);
                $('#results').addClass('hide');
                return false;
            }).attr({title:'Add file +'});
        } else {
            // setup bookmarklet 1
            $.ajax({
                url : '../?f=' + location.pathname.replace(/\/[^\/]*$/, '/bm.js').substr(1),
                success : function (code) {
                    $('#bm')[0].href = code
                        .replace('%BUILDER_URL%', location.href)
                        .replace(/\n/g, ' ');
                },
                dataType : 'text'
            });
            if ($.browser.msie) {
                $('#getBm p:last').append(' Sorry, not supported in MSIE!');
            }
            MUB.addButtonClick();
        }
        // setup bookmarklet 2
        $.ajax({
            url : '../?f=' + location.pathname.replace(/\/[^\/]*$/, '/bm2.js').substr(1),
            success : function (code) {
                $('#bm2')[0].href = code.replace(/\n/g, ' ');
            },
            dataType : 'text'
        });
        MUB.checkRewrite();
    }
};
$(MUB.init);
