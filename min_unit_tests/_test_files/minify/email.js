// http://mrclay.org/
(function(){
	var
		reMailto = /^mailto:my_name_is_(\S+)_and_the_domain_is_(\S+)$/,
		reRemoveTitleIf = /^my name is/,
		oo = window.onload,
		fixHrefs = function() {
			var i = 0, l, m;
			while (l = document.links[i++]) {
				// require phrase in href property
				if (m = l.href.match(reMailto)) {
					l.href = 'mailto:' + m[1] + '@' + m[2];
					if (reRemoveTitleIf.test(l.title)) {
						l.title = '';
					}
				}
			}
		};
	// end var
	window.onload = function() {
		oo && oo();
		fixHrefs();
	};
})();