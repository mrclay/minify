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