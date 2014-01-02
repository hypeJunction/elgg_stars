<?php
// Read and cache the RateIt JS scripe
readfile(elgg_get_root_path() . 'mod/elgg_stars/vendors/rateit/jquery.rateit.min.js');
?>

//<script>

	elgg.provide('elgg.stars');

	elgg.stars.init = function() {
		$('.rateit').rateit();

		$('.rateit').bind('rated', function() {

			$elem = $(this);
			$form = $(this).closest('.elgg-form-stars-rate');

			if (!$form.length) {
				return true;
			}

			guid = $elem.data('guid');
			annotation_name = $elem.data('annotationName');

			$starinput = $('.rateit[data-guid="' + guid + '"][data-annotation-name="' + annotation_name + '"]');
			$caption = $('.elgg-stars-rating-caption[data-guid="' + guid + '"][data-annotation-name="' + annotation_name + '"]');

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
			})
		})
	}

	elgg.register_hook_handler('init', 'system', elgg.stars.init);
