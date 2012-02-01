var MrClay = window.MrClay || {};

/**
 * Simplified access to/manipulation of the query string
 * 
 * Based on: http://adamv.com/dev/javascript/files/querystring.js
 * Design pattern: http://www.litotes.demon.co.uk/js_info/private_static.html#wConst
 */
MrClay.QueryString = function(){
    /**
     * @static
     * @private
     */
    var parse = function(str) {
        var assignments = str.split('&')
            ,obj = {}
            ,propValue;
        for (var i = 0, l = assignments.length; i < l; ++i) {
            propValue = assignments[i].split('=');
            if (propValue.length > 2
                || -1 != propValue[0].indexOf('+')
                || propValue[0] == ''
            ) {
                continue;
            }
            if (propValue.length == 1) {
                propValue[1] = propValue[0];
            }
            obj[unescape(propValue[0])] = unescape(propValue[1].replace(/\+/g, ' '));
        }
        return obj;
    };
    
    /**
     * Constructor (MrClay.QueryString becomes this)
     *
     * @param mixed A window object, a query string, or empty (default current window)
     */
    function construct_(spec) {
        spec = spec || window;
        if (typeof spec == 'object') {
            // get querystring from window
            this.window = spec;
            spec = spec.location.search.substr(1);
        } else {
            this.window = window;
        }
        this.vars = parse(spec);
    }
    
    /**
     * Reload the window
     *
     * @static
     * @public
     * @param object vars Specify querystring vars only if you wish to replace them
     * @param object window_ window to be reloaded (current window by default)
     */
    construct_.reload = function(vars, window_) {
        window_ = window_ || window;
        vars = vars || (new MrClay.QueryString(window_)).vars;
        var l = window_.location
            ,currUrl = l.href
            ,s = MrClay.QueryString.toString(vars)
            ,newUrl = l.protocol + '//' + l.hostname + l.pathname
                + (s ? '?' + s : '') + l.hash;
        if (currUrl == newUrl) {
            l.reload();
        } else {
            l.assign(newUrl);
        }
    };
    
    /**
     * Get the value of a querystring var
     *
     * @static
     * @public
     * @param string key
     * @param mixed default_ value to return if key not found
     * @param object window_ window to check (current window by default)
     * @return mixed
     */
    construct_.get = function(key, default_, window_) {
        window_ = window_ || window;
        return (new MrClay.QueryString(window_)).get(key, default_);
    };
    
    /**
     * Reload the page setting one or multiple querystring vars
     *
     * @static
     * @public
     * @param mixed key object of query vars/values, or a string key for a single
     * assignment
     * @param mixed null for multiple settings, the value to assign for single
     * @param object window_ window to reload (current window by default)
     */
    construct_.set = function(key, value, window_) {
        window_ = window_ || window;
        (new MrClay.QueryString(window_)).set(key, value).reload();
    };
    
    /**
     * Convert an object of query vars/values to a querystring
     *
     * @static
     * @public
     * @param object query vars/values
     * @return string
     */
    construct_.toString = function(vars) {
        var pieces = [];
        for (var prop in vars) {
            pieces.push(escape(prop) + '=' + escape(vars[prop]));
        }
        return pieces.join('&');
    };
    
    /**
     * @public
     */
    construct_.prototype.reload = function() {
        MrClay.QueryString.reload(this.vars, this.window);
        return this;
    };
    
    /**
     * @public
     */
    construct_.prototype.get = function(key, default_) {
        if (typeof default_ == 'undefined') {
            default_ = null;
        }
        return (this.vars[key] == null)
            ? default_
            : this.vars[key];
    };
    
    /**
     * @public
     */
    construct_.prototype.set = function(key, value) {
        var obj = {};
        if (typeof key == 'string') {
            obj[key] = value;
        } else {
            obj = key;
        }
        for (var prop in obj) {
            if (obj[prop] == null) {
                delete this.vars[prop];
            } else {
                this.vars[prop] = obj[prop];
            }
        }
        return this;
    };
    
    /**
     * @public
     */
    construct_.prototype.toString = function() {
        return QueryString.toString(this.vars);
    };
    
    return construct_;
}(); // define and execute