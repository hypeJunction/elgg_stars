<?php

/**
 * Star rating input view - an HTML tag back with a hidden input
 *
 * @uses $vars['class'] CSS class
 * @uses $vars['name'] Input name to retrieve the value in the action
 * @uses $vars['value'] Current value
 * @uses $vars['disabled'] Whether or not this is a readonly input
 * @uses $vars['min'] Minimum input value
 * @uses $vars['max'] Maximum input value
 * @uses $vars['step'] At what step the input values are iterated
 */

elgg_load_js('jquery.rateit');
elgg_load_js('elgg.rateit');

$settings = elgg_stars_get_rating_settings();

$id = md5(microtime());
$options = array(
	'data-rateit-backingfld' => "#$id",
	'data-rateit-readonly' => true,
	'data-rateit-resetable' => 0,
);

if (isset($vars['class'])) {
	$options['class'] = "{$vars['class']} rateit";
	unset($vars['class']);
} else {
	$options['class'] = 'rateit';
}

if (isset($vars['name'])) {
	$name = $options['data-annotation-name'] = $vars['name'];
	unset($vars['name']);
} else {
	$name = $options['data-annotation-name'] = 'starrating';
}

if (isset($vars['value'])) {
	$value = $options['data-rateit-value'] = $vars['value'];
	unset($vars['value']);
}

if (isset($vars['disabled'])) {
	$options['data-rateit-readonly'] = $vars['disabled'];
	unset($vars['disabled']);
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
echo elgg_view('input/hidden', array(
	'id' => $id,
	'name' => $name,
	'value' => $value,
));