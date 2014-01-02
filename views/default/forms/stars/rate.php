<?php

/**
 * Entity rating form
 */
$entity = elgg_extract('entity', $vars, false);
$annotation_names = elgg_extract('annotation_names', $vars);

if (!elgg_instanceof($entity)) {
	return;
}

if (!$annotation_names) {
	$annotation_names = elgg_stars_get_rating_annotation_names($entity);
} else if (!is_array($annotation_names)) {
	$annotation_names = (array) $annotation_names;
}

foreach ($annotation_names as $annotation_name) {

	if (!elgg_stars_is_valid_rating_annotation_name($annotation_name)) {
		elgg_log('To use custom annotation names, please use elgg_stars_register_rating_annotation_name() to register them first', 'WARNING');
		return;
	}

	$entity_ratings = elgg_stars_get_entity_rating_values($entity, $annotation_name);

	$defaults = array(
		'name' => $annotation_name,
		'disabled' => (!$entity->canAnnotate(0, $annotation_name)),
		'class' => 'elgg-stars-rating-input',
		'data-guid' => $entity->guid,
	);

	$options = array_merge($defaults, $entity_ratings);

	if (sizeof($annotation_names) > 1) {
		$body .= '<label>' . elgg_echo("rating_name:$annotation_name") . '</label>';
	}

	$body .= elgg_view('input/stars', $options);

	$caption_attrs = array(
		'class' => 'elgg-stars-rating-caption',
		'data-guid' => $entity->guid,
		'data-annotation-name' => $annotation_name
	);

	$caption_attrs_str = elgg_format_attributes($caption_attrs);

	$body .= "<div $caption_attrs_str>" . elgg_echo('stars:stats', array($options['value'], $options['max'], $options['count'])) . '</div>';

	$body .= elgg_view('input/hidden', array(
		'name' => 'annotation_names[]',
		'value' => $annotation_name,
	));
}

$body .= elgg_view('input/hidden', array(
	'name' => 'guid',
	'value' => $entity->guid,
		));

$body .= elgg_view('input/submit', array(
	'value' => elgg_echo('stars:rate'),
		));

echo $body;
