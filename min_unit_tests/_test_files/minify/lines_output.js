
/* email.js */

/* 1  */ // http://mrclay.org/
/* 2  */ (function(){
/* 3  */ 	var
/* 4  */ 		reMailto = /^mailto:my_name_is_(\S+)_and_the_domain_is_(\S+)$/,
/* 5  */ 		reRemoveTitleIf = /^my name is/,
/* 6  */ 		oo = window.onload,
/* 7  */ 		fixHrefs = function() {
/* 8  */ 			var i = 0, l, m;
/* 9  */ 			while (l = document.links[i++]) {
/* 10 */ 				// require phrase in href property
/* 11 */ 				if (m = l.href.match(reMailto)) {
/* 12 */ 					l.href = 'mailto:' + m[1] + '@' + m[2];
/* 13 */ 					if (reRemoveTitleIf.test(l.title)) {
/* 14 */ 						l.title = '';
/* 15 */ 					}
/* 16 */ 				}
/* 17 */ 			}
/* 18 */ 		};
/* 19 */ 	// end var
/* 20 */ 	window.onload = function() {
/* 21 */ 		oo && oo();
/* 22 */ 		fixHrefs();
/* 23 */ 	};
/* 24 */ })();

;
/* lines_bugs.js */

/* 1 */ var triggerBug = {_default: "*/*"};
/* 2 */ var essentialFunctionality = true;
/* 3 */ 

;
/* QueryString.js */

/* 1   */ var MrClay = window.MrClay || {};
/* 2   */ 
/* 3   */ /**
/* 4   *|  * Simplified access to/manipulation of the query string
/* 5   *|  * 
/* 6   *|  * Based on: http://adamv.com/dev/javascript/files/querystring.js
/* 7   *|  * Design pattern: http://www.litotes.demon.co.uk/js_info/private_static.html#wConst
/* 8   *|  */
/* 9   */ MrClay.QueryString = function(){
/* 10  */     /**
/* 11  *|      * @static
/* 12  *|      * @private
/* 13  *|      */
/* 14  */     var parse = function(str) {
/* 15  */         var assignments = str.split('&')
/* 16  */             ,obj = {}
/* 17  */             ,propValue;
/* 18  */         for (var i = 0, l = assignments.length; i < l; ++i) {
/* 19  */             propValue = assignments[i].split('=');
/* 20  */             if (propValue.length > 2
/* 21  */                 || -1 != propValue[0].indexOf('+')
/* 22  */                 || propValue[0] == ''
/* 23  */             ) {
/* 24  */                 continue;
/* 25  */             }
/* 26  */             if (propValue.length == 1) {
/* 27  */                 propValue[1] = propValue[0];
/* 28  */             }
/* 29  */             obj[unescape(propValue[0])] = unescape(propValue[1].replace(/\+/g, ' '));
/* 30  */         }
/* 31  */         return obj;
/* 32  */     };
/* 33  */     
/* 34  */     /**
/* 35  *|      * Constructor (MrClay.QueryString becomes this)
/* 36  *|      *
/* 37  *|      * @param mixed A window object, a query string, or empty (default current window)
/* 38  *|      */
/* 39  */     function construct_(spec) {
/* 40  */         spec = spec || window;
/* 41  */         if (typeof spec == 'object') {
/* 42  */             // get querystring from window
/* 43  */             this.window = spec;
/* 44  */             spec = spec.location.search.substr(1);
/* 45  */         } else {
/* 46  */             this.window = window;
/* 47  */         }
/* 48  */         this.vars = parse(spec);
/* 49  */     }
/* 50  */     

/* QueryString.js */

/* 51  */     /**
/* 52  *|      * Reload the window
/* 53  *|      *
/* 54  *|      * @static
/* 55  *|      * @public
/* 56  *|      * @param object vars Specify querystring vars only if you wish to replace them
/* 57  *|      * @param object window_ window to be reloaded (current window by default)
/* 58  *|      */
/* 59  */     construct_.reload = function(vars, window_) {
/* 60  */         window_ = window_ || window;
/* 61  */         vars = vars || (new MrClay.QueryString(window_)).vars;
/* 62  */         var l = window_.location
/* 63  */             ,currUrl = l.href
/* 64  */             ,s = MrClay.QueryString.toString(vars)
/* 65  */             ,newUrl = l.protocol + '//' + l.hostname + l.pathname
/* 66  */                 + (s ? '?' + s : '') + l.hash;
/* 67  */         if (currUrl == newUrl) {
/* 68  */             l.reload();
/* 69  */         } else {
/* 70  */             l.assign(newUrl);
/* 71  */         }
/* 72  */     };
/* 73  */     
/* 74  */     /**
/* 75  *|      * Get the value of a querystring var
/* 76  *|      *
/* 77  *|      * @static
/* 78  *|      * @public
/* 79  *|      * @param string key
/* 80  *|      * @param mixed default_ value to return if key not found
/* 81  *|      * @param object window_ window to check (current window by default)
/* 82  *|      * @return mixed
/* 83  *|      */
/* 84  */     construct_.get = function(key, default_, window_) {
/* 85  */         window_ = window_ || window;
/* 86  */         return (new MrClay.QueryString(window_)).get(key, default_);
/* 87  */     };
/* 88  */     
/* 89  */     /**
/* 90  *|      * Reload the page setting one or multiple querystring vars
/* 91  *|      *
/* 92  *|      * @static
/* 93  *|      * @public
/* 94  *|      * @param mixed key object of query vars/values, or a string key for a single
/* 95  *|      * assignment
/* 96  *|      * @param mixed null for multiple settings, the value to assign for single
/* 97  *|      * @param object window_ window to reload (current window by default)
/* 98  *|      */
/* 99  */     construct_.set = function(key, value, window_) {
/* 100 */         window_ = window_ || window;

/* QueryString.js */

/* 101 */         (new MrClay.QueryString(window_)).set(key, value).reload();
/* 102 */     };
/* 103 */     
/* 104 */     /**
/* 105 *|      * Convert an object of query vars/values to a querystring
/* 106 *|      *
/* 107 *|      * @static
/* 108 *|      * @public
/* 109 *|      * @param object query vars/values
/* 110 *|      * @return string
/* 111 *|      */
/* 112 */     construct_.toString = function(vars) {
/* 113 */         var pieces = [];
/* 114 */         for (var prop in vars) {
/* 115 */             pieces.push(escape(prop) + '=' + escape(vars[prop]));
/* 116 */         }
/* 117 */         return pieces.join('&');
/* 118 */     };
/* 119 */     
/* 120 */     /**
/* 121 *|      * @public
/* 122 *|      */
/* 123 */     construct_.prototype.reload = function() {
/* 124 */         MrClay.QueryString.reload(this.vars, this.window);
/* 125 */         return this;
/* 126 */     };
/* 127 */     
/* 128 */     /**
/* 129 *|      * @public
/* 130 *|      */
/* 131 */     construct_.prototype.get = function(key, default_) {
/* 132 */         if (typeof default_ == 'undefined') {
/* 133 */             default_ = null;
/* 134 */         }
/* 135 */         return (this.vars[key] == null)
/* 136 */             ? default_
/* 137 */             : this.vars[key];
/* 138 */     };
/* 139 */     
/* 140 */     /**
/* 141 *|      * @public
/* 142 *|      */
/* 143 */     construct_.prototype.set = function(key, value) {
/* 144 */         var obj = {};
/* 145 */         if (typeof key == 'string') {
/* 146 */             obj[key] = value;
/* 147 */         } else {
/* 148 */             obj = key;
/* 149 */         }
/* 150 */         for (var prop in obj) {

/* QueryString.js */

/* 151 */             if (obj[prop] == null) {
/* 152 */                 delete this.vars[prop];
/* 153 */             } else {
/* 154 */                 this.vars[prop] = obj[prop];
/* 155 */             }
/* 156 */         }
/* 157 */         return this;
/* 158 */     };
/* 159 */     
/* 160 */     /**
/* 161 *|      * @public
/* 162 *|      */
/* 163 */     construct_.prototype.toString = function() {
/* 164 */         return QueryString.toString(this.vars);
/* 165 */     };
/* 166 */     
/* 167 */     return construct_;
/* 168 */ }(); // define and execute

;
/* before.js */

/* 1  */ /*! is.js
/* 2  *| 
/* 3  *|  (c) 2001 Douglas Crockford
/* 4  *|  2001 June 3
/* 5  *| */
/* 6  */ 
/* 7  */ // is
/* 8  */ 
/* 9  */ // The -is- object is used to identify the browser.  Every browser edition
/* 10 */ // identifies itself, but there is no standard way of doing it, and some of
/* 11 */ // the identification is deceptive. This is because the authors of web
/* 12 */ // browsers are liars. For example, Microsoft's IE browsers claim to be
/* 13 */ // Mozilla 4. Netscape 6 claims to be version 5.
/* 14 */ 
/* 15 */ var is = {
/* 16 */     ie:      navigator.appName == 'Microsoft Internet Explorer',
/* 17 */     java:    navigator.javaEnabled(),
/* 18 */     ns:      navigator.appName == 'Netscape',
/* 19 */     ua:      navigator.userAgent.toLowerCase(),
/* 20 */     version: parseFloat(navigator.appVersion.substr(21)) ||
/* 21 */              parseFloat(navigator.appVersion),
/* 22 */     win:     navigator.platform == 'Win32'
/* 23 */ }
/* 24 */ /*!*
/* 25 *|  * preserve this comment, too
/* 26 *|  */
/* 27 */ is.mac = is.ua.indexOf('mac') >= 0;
/* 28 */ if (is.ua.indexOf('opera') >= 0) {
/* 29 */     is.ie = is.ns = false;
/* 30 */     is.opera = true;
/* 31 */ }
/* 32 */ if (is.ua.indexOf('gecko') >= 0) {
/* 33 */     is.ie = is.ns = false;
/* 34 */     is.gecko = true;
/* 35 */ }
/* 36 */ 
/* 37 */ /*@cc_on
/* 38 *|    /*@if (@_win32)
/* 39 *|     if (is.ie && is.win)
/* 40 *|         document.write("PASS: IE/win honored conditional comment.<br>");
/* 41 *|    @else @*/
/* 42 */     if (is.ie && is.win)
/* 43 */         document.write("FAIL: IE/win did not honor multi-line conditional comment.<br>");
/* 44 */     else 
/* 45 */         document.write("PASS: Non-IE/win browser ignores multi-line conditional comment.<br>");
/* 46 */    /*@end
/* 47 *| @*/
/* 48 */ 
/* 49 */ var recognizesCondComm = true;
/* 50 */ //@cc_on/*

/* before.js */

/* 51 *| recognizesCondComm = false;
/* 52 *| //@cc_on*/
/* 53 */ 
/* 54 */ if ((is.ie && is.win) == recognizesCondComm)
/* 55 */     document.write("PASS: IE/win honored single-line conditional comment.<br>");
/* 56 */ else 
/* 57 */     document.write("FAIL: Non-IE/win browser did not ignore single-line conditional comment.<br>");
/* 58 */ 
