<?php

if (!isset($vars['entity']->num_display)) {
	$vars['entity']->num_display = 5;
}
if (!isset($vars['entity']->content_type)) {
	$vars['entity']->content_type = 'object';
}

$params = array(
	'name' => 'params[num_display]',
	'value' => $vars['entity']->num_display,
	'options' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10),
);
$limit = elgg_view('input/dropdown', $params);

echo '<div>';
echo '<label>' . elgg_echo('stars:widget:numbertodisplay') . '</label>';
echo $limit;
echo '</div>';

if (!elgg_in_context('profile') && !elgg_in_context('groups')) {
	echo '<div>';
	echo '<label>' . elgg_echo('stars:widget:types') . '</label>';
	echo elgg_view('input/dropdown', array(
		'name' => 'params[content_type]',
		'value' => $vars['entity']->content_type,
		'options_values' => array(
			'object' => elgg_echo('stars:widget:types:object'),
			'user' => elgg_echo('stars:widget:types:user'),
			'group' => elgg_echo('stars:widget:types:group'),
		)
	));
	echo '</div>';
}
