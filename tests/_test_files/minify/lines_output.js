
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

/* 51 */ recognizesCondComm = false;
/* 52 */ //@cc_on*/
/* 53 */ 
/* 54 */ if ((is.ie && is.win) == recognizesCondComm)
/* 55 */     document.write("PASS: IE/win honored single-line conditional comment.<br>");
/* 56 */ else 
/* 57 */     document.write("FAIL: Non-IE/win browser did not ignore single-line conditional comment.<br>");
/* 58 */ 
/* 59 */ // hello
/* 60 */ //@cc_on/*
/* 61 */ // world
/* 62 */ //@cc_on*/
/* 63 */ //@cc_on/*
/* 64 */ 'hello';
/* 65 */ /*!* preserved */
/* 66 */ /*!* preserved */
