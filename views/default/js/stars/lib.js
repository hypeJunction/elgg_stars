define(['elgg', 'jquery', 'jquery.rateit'], function(elgg, $) {

	var stars = {
		init: function() {
			var selector = '.rateit:not(:has(.rateit-range))';

			$(selector).rateit();

			$(document).off('rated', '.rateit').on('rated', '.rateit', function() {
				var $elem = $(this);
				var $form = $(this).closest('.elgg-form-stars-rate');
				if (!$form.length) {
					return true;
				}

				var guid = $elem.data('guid');
				var annotation_name = $elem.data('annotationName');
				var $starinput = $('.rateit[data-guid="' + guid + '"][data-annotation-name="' + annotation_name + '"]');
				var $caption = $('.elgg-stars-rating-caption[data-guid="' + guid + '"][data-annotation-name="' + annotation_name + '"]');
				elgg.action($form.attr('action'), {
					data: $form.serialize(),
					beforeSend: function() {
						$starinput.rateit('readonly', true);
						$starinput.rateit('ispreset', true);
					},
					success: function(data) {
						if (data && data.output) {

							values = data.output[guid][annotation_name];
							$starinput.rateit('readonly', true);
							$starinput.rateit('value', values.value);
							$caption.text(elgg.echo('stars:stats', [values.value, values.max, values.count]));
						} else {
							$starinput.rateit('readonly', false);
						}
					},
					complete: function() {
						$starinput.rateit('ispreset', false);
					}
				});
			});
		}
	}

	return stars;
});