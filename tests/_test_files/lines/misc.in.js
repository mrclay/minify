// sections from Prototype 1.6.1
var xpath = ".//*[local-name()='ul' or local-name()='UL']" +
          "//*[local-name()='li' or local-name()='LI']";
this.matcher = ['.//*'];
xpath = {
    descendant:   "//*",
    child:        "/*",
    f: 0
};
document._getElementsByXPath('.//*' + cond, element);

// from angular 1.4.8
var URL_REGEXP = /^[a-z][a-z\d.+-]*:\/*(?:[^:@]+(?::[^@]+)?@)?(?:[^\s:/?#]+|\[[a-f\d:]+\])(?::\d+)?(?:\/[^?#]*)?(?:\?[^#]*)?(?:#.*)?$/i;

