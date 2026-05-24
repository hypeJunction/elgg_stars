/**
 * Stars init ES module (6.x).
 *
 * Auto-imported via elgg_import_esm('stars/init'). Wires up the jQuery rateit
 * widget on any '.rateit' element present at load time and re-wires it after
 * AJAX responses that inject new '.rateit' nodes.
 *
 * The jquery.rateit plugin itself is a non-ESM jQuery plugin loaded via a
 * regular <script> tag (registered in Bootstrap), so it attaches to the
 * jQuery instance globally by the time this module runs.
 */
import 'jquery';
import { init } from 'stars/lib';

if ($('.rateit').length) {
	init();
}

$(document).ajaxSuccess((event, xhr, settings, data) => {
	if ($(data).has('.rateit')) {
		init();
	}

});
