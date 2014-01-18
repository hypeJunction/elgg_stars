<?php

$entity = elgg_extract('entity', $vars);

echo '<div>';
echo '<h3>' . elgg_echo('stars:settings:ranges') . '</h3>';

echo '<div>';
echo '<label>' . elgg_echo('stars:settings:ranges:min_value') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[min_value]',
	'value' => $entity->min_value
));
echo '</div>';

echo '<div>';
echo '<label>' . elgg_echo('stars:settings:ranges:max_value') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[max_value]',
	'value' => $entity->max_value
));
echo '</div>';

echo '<div>';
echo '<label>' . elgg_echo('stars:settings:ranges:step') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[step]',
	'value' => $entity->step
));
echo '</div>';

echo '</div>';


echo '<div>';
echo '<h3>' . elgg_echo('stars:settings:entity_ratings') . '</h3>';

echo '<div>';
echo '<label>' . elgg_echo('stars:settings:entity_ratings:extend_menu') . '</label>';
echo elgg_view('input/dropdown', array(
	'name' => 'params[extend_menu]',
	'value' => $entity->extend_menu,
	'options_values' => array(
		0 => elgg_echo('option:no'),
		1 => elgg_echo('option:yes')
	)
));
echo '</div>';

echo '<div>';
echo '<label>' . elgg_echo('stars:settings:entity_ratings:extend_comments') . '</label>';
echo elgg_view('input/dropdown', array(
	'name' => 'params[extend_comments]',
	'value' => $entity->extend_comments,
	'options_values' => array(
		0 => elgg_echo('option:no'),
		1 => elgg_echo('option:yes')
	)
));
echo '</div>';

echo '<div>';
echo '<label>' . elgg_echo('stars:settings:entity_ratings:criteria') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[criteria]',
	'value' => $entity->criteria,
));
echo '</div>';

echo '</div>';

$registered_entities = elgg_get_config('registered_entities');

foreach ($registered_entities as $type => $subtypes) {

	if (sizeof($subtypes) == 0) {
		$str = elgg_echo("item:$type");
		$subtype_options[$str] = "$type:default";
	} else {
		foreach ($subtypes as $subtype) {
			$str = elgg_echo("item:$type:$subtype");
			$subtype_options[$str] = "$type:$subtype";
		}
	}
}

$criteria = ($entity->criteria) ? string_to_tag_array($entity->criteria) : array();
foreach ($criteria as $criterion) {
	$criteria_options[$criterion] = $criterion;
}
$type_subtype_pairs_setting = isset($entity->type_subtype_pairs) ? unserialize($entity->type_subtype_pairs) : array();
$granular_criteria = isset($entity->granular_criteria) ? unserialize($entity->granular_criteria) : array();

echo '<div>';
echo '<h3>' . elgg_echo('stars:settings:type_subtype_pairs') . '</h3>';

echo elgg_view('input/hidden', array(
	'name' => 'params[type_subtype_pairs]',
	'value' => '',
));

echo '<table class="elgg-table-alt">';
echo '<thead>';
echo '<tr>';
echo '<th>' . elgg_echo('stars:settings:type_subtype_pairs') . '</th>';
if ($criteria) {
	echo '<th>' . elgg_echo('stars:settings:granular_criteria') . '</th>';
}
echo '</tr>';
echo '</thead>';
foreach ($subtype_options as $key => $type_subtype_pair) {

	echo '<tr>';
	echo '<td>';
	echo '<label>' . elgg_view('input/checkbox', array(
		'default' => false,
		'name' => 'params[type_subtype_pairs][]',
		'value' => $type_subtype_pair,
		'checked' => in_array($type_subtype_pair, $type_subtype_pairs_setting),
	)) . elgg_echo($key) . '</label>';
	echo '</td>';

	if ($criteria) {
		echo '<td>';
		echo elgg_view('input/checkboxes', array(
			'name' => "params[granular_criteria][$type_subtype_pair]",
			'value' => (isset($granular_criteria[$type_subtype_pair])) ? $granular_criteria[$type_subtype_pair] : $criteria,
			'options' => $criteria_options
		));
		echo '</td>';
	}
	echo '</tr>';
}

echo '</table>';

echo '</div>';
