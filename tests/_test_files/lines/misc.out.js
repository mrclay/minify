
/* misc.in.js */

/* 1  */ // sections from Prototype 1.6.1
/* 2  */ var xpath = ".//*[local-name()='ul' or local-name()='UL']" +
/* 3  */           "//*[local-name()='li' or local-name()='LI']";
/* 4  */ this.matcher = ['.//*'];
/* 5  */ xpath = {
/* 6  */     descendant:   "//*",
/* 7  */     child:        "/*",
/* 8  */     f: 0
/* 9  */ };
/* 10 */ document._getElementsByXPath('.//*' + cond, element);
/* 11 */
/* 12 */ // from angular 1.4.8
/* 13 */ var URL_REGEXP = /^[a-z][a-z\d.+-]*:\/*(?:[^:@]+(?::[^@]+)?@)?(?:[^\s:/?#]+|\[[a-f\d:]+\])(?::\d+)?(?:\/[^?#]*)?(?:\?[^#]*)?(?:#.*)?$/i;
/* 14 */
/* 15 */
