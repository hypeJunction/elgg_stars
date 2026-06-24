/**
 * Stars init ES module (6.x).
 *
 * Auto-imported via elgg_import_esm('stars/init'). Wires up the jQuery rateit
 * widget on any '.rateit' element present at load time and re-wires it after
 * AJAX responses that inject new '.rateit' nodes.
 *
 * The jquery.rateit plugin is a classic (non-ESM) jQuery plugin that expects a
 * GLOBAL jQuery. On Elgg 7 jQuery is an ES module, so we expose window.jQuery
 * here and THEN side-effect import the rateit bundle (registered in the
 * importmap in Bootstrap) so it attaches $.fn.rateit to this jQuery instance.
 */
import $ from 'jquery';
import { init } from 'stars/lib';

// Expose the global the classic rateit plugin needs, then load it.
window.$ = window.jQuery = $;
await import('elgg_stars/rateit');

if ($('.rateit').length) {
	init();
}

$(document).ajaxSuccess((event, xhr, settings, data) => {
	if ($(data).has('.rateit')) {
		init();
	}

});
