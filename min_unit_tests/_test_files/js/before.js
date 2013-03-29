/*! is.js

 (c) 2001 Douglas Crockford
 2001 June 3
*/

// is

// The -is- object is used to identify the browser.  Every browser edition
// identifies itself, but there is no standard way of doing it, and some of
// the identification is deceptive. This is because the authors of web
// browsers are liars. For example, Microsoft's IE browsers claim to be
// Mozilla 4. Netscape 6 claims to be version 5.

var is = {
    ie:      navigator.appName == 'Microsoft Internet Explorer',
    java:    navigator.javaEnabled(),
    ns:      navigator.appName == 'Netscape',
    ua:      navigator.userAgent.toLowerCase(),
    version: parseFloat(navigator.appVersion.substr(21)) ||
             parseFloat(navigator.appVersion),
    win:     navigator.platform == 'Win32'
}
/*!*
 * preserve this comment, too
 */
is.mac = is.ua.indexOf('mac') >= 0;
if (is.ua.indexOf('opera') >= 0) {
    is.ie = is.ns = false;
    is.opera = true;
}
if (is.ua.indexOf('gecko') >= 0) {
    is.ie = is.ns = false;
    is.gecko = true;
}

/*@cc_on
   /*@if (@_win32)
    if (is.ie && is.win)
        document.write("PASS: IE/win honored conditional comment.<br>");
   @else @*/
    if (is.ie && is.win)
        document.write("FAIL: IE/win did not honor multi-line conditional comment.<br>");
    else 
        document.write("PASS: Non-IE/win browser ignores multi-line conditional comment.<br>");
   /*@end
@*/

var recognizesCondComm = true;
//@cc_on/*
recognizesCondComm = false;
//@cc_on*/

if ((is.ie && is.win) == recognizesCondComm)
    document.write("PASS: IE/win honored single-line conditional comment.<br>");
else 
    document.write("FAIL: Non-IE/win browser did not ignore single-line conditional comment.<br>");

// hello
//@cc_on/*
// world
//@cc_on*/
//@cc_on/*
'hello';
/*!* preserved */
/*!* preserved */