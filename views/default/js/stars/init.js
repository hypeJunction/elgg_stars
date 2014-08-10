define(function(require) {

	var $ = require('jquery');

	if ($('.rateit').length) {
		require(['stars/lib'], function(stars) {
			stars.init();
		});
	}

	$(document).ajaxSuccess(function(data) {
		if ($(data).has('.rateit')) {
			require(['stars/lib'], function(stars) {
				stars.init();
			});
		}
	});
});