/**
 * Stars rating library ES module (6.x).
 *
 * Exports init() which binds jQuery rateit to all '.rateit' inputs that don't
 * already have a 'rateit-range' child, and wires up the 'rated' event so a
 * change in a star widget POSTs to the surrounding stars/rate form.
 */
import $ from 'jquery';
import Ajax from 'elgg/Ajax';
import i18n from 'elgg/i18n';

export function init() {
	const selector = '.rateit:not(:has(.rateit-range))';

	$(selector).rateit();

	$(document).off('rated', '.rateit').on('rated', '.rateit', function () {
		const $elem = $(this);
		const $form = $elem.closest('.elgg-form-stars-rate');
		if (!$form.length) {
			return true;
		}

		const guid = $elem.data('guid');
		const annotationName = $elem.data('annotationName');
		const $starinput = $('.rateit[data-guid="' + guid + '"][data-annotation-name="' + annotationName + '"]');
		const $caption = $('.elgg-stars-rating-caption[data-guid="' + guid + '"][data-annotation-name="' + annotationName + '"]');

		const ajax = new Ajax();
		ajax.action($form.attr('action'), {
			data: $form.serialize(),
			beforeSend: function () {
				$starinput.rateit('readonly', true);
				$starinput.rateit('ispreset', true);
			},
			success: function (data) {
				if (data && data.output) {
					const values = data.output[guid][annotationName];
					$starinput.rateit('readonly', true);
					$starinput.rateit('value', values.value);
					$caption.text(i18n.echo('stars:stats', [values.value, values.max, values.count]));
				} else {
					$starinput.rateit('readonly', false);
				}
			},
			complete: function () {
				$starinput.rateit('ispreset', false);
			},
		});
	});
}
