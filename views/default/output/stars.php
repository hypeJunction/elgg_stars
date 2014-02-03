<?php

/**
 * Star rating output view
 *
 * @uses $vars['value'] Value to display
 * @uses $vars['class'] Additional CSS classes
 * @uses $vars['min'] Minimum input value
 * @uses $vars['max'] Maximum input value
 * @uses $vars['step'] At what step the input values are iterated
 */

elgg_load_js('jquery.rateit');
elgg_load_js('elgg.rateit');

$settings = elgg_stars_get_rating_settings();

$options = array(
	'data-rateit-readonly' => true,
	'data-rateit-resetable' => 0,
);

if (isset($vars['class'])) {
	$options['class'] = "{$vars['class']} rateit";
	unset($vars['class']);
} else {
	$options['class'] = 'rateit';
}

if (isset($vars['value'])) {
	$value = $options['data-rateit-value'] = $vars['value'];
	unset($vars['value']);
}

if (isset($vars['min'])) {
	$options['data-rateit-min'] = $vars['min'];
	unset($vars['min']);
} else {
	$options['data-rateit-min'] = $settings['min'];
}

if (isset($vars['max'])) {
	$options['data-rateit-max'] = $vars['max'];
	unset($vars['max']);
} else {
	$options['data-rateit-max'] = $settings['max'];
}

if (isset($vars['step'])) {
	$options['data-rateit-step'] = $vars['step'];
	unset($vars['step']);
} else {
	$options['data-rateit-step'] = $settings['step'];
}

$options = array_merge($vars, $options);
$attrs = elgg_format_attributes($options);

echo "<div $attrs></div>";