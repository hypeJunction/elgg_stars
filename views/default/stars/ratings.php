<?php

/**
 * Display a list of ratings and a ratings form
 */
if (!(bool)elgg_get_plugin_setting('extend_comments', 'elgg_stars')) {
	return;
}

$entity = elgg_extract('entity', $vars);
$show_add_form = elgg_extract('show_add_form', $vars, true);

if (!elgg_instanceof($entity)) {
	return;
}

$type_subtype_pairs = elgg_stars_get_rateable_type_subtype_pairs();
$type = $entity->getType();
$subtype = $entity->getSubtype();

if (!array_key_exists($type, $type_subtype_pairs)) {
	return;
}

if ($subtype && array_search($subtype, $type_subtype_pairs[$type]) === false) {
	return;
}

if ($show_add_form) {
	$body .= elgg_view_form('stars/rate', array(), array(
		'entity' => $entity
	));
}

$title = elgg_echo('stars:ratings');

$annotation_names = elgg_stars_get_rating_annotation_names($entity);

$body .= elgg_list_annotations(array(
	'guid' => $entity->guid,
	'annotation_names' => $annotation_names,
		));

$entity_ratings = elgg_stars_get_entity_rating_values($entity, $annotation_names);

$label = '<label>' . elgg_echo('stars:stats:totals') . '</label>';
$total = elgg_view('output/stars', array('value' => $entity_ratings['value']));
$stats = elgg_echo('stars:stats', array($entity_ratings['value'], $entity_ratings['max'], $entity_ratings['count']));

$footer = elgg_view_image_block($label, $total, array(
	'image_alt' => $stats
		));

echo elgg_view_module('aside', $title, $body, array(
	'footer' => $footer
));
