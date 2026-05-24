<?php

use Elgg\Database\Clauses\OrderByClause;
use Elgg\Database\QueryBuilder;

$widget = elgg_extract('entity', $vars);
$page_owner = elgg_get_page_owner_entity();

if (elgg_in_context('profile') && $page_owner instanceof \ElggUser) {
	$owner_guid = $page_owner->guid;
	$container_guid = ELGG_ENTITIES_ANY_VALUE;
} else if (elgg_in_context('groups') && $page_owner instanceof \ElggGroup) {
	$owner_guid = ELGG_ENTITIES_ANY_VALUE;
	$container_guid = $page_owner->guid;
} else {
	$owner_guid = ELGG_ENTITIES_ANY_VALUE;
	$container_guid = ELGG_ENTITIES_ANY_VALUE;
}

$annotation_names = elgg_stars_get_rating_annotation_names();
$num_display = (int) ($widget->num_display ?: 10);

// Replacement for elgg_get_entities_from_annotation_calculation() (deprecated in 3.x, removed in 5.x).
// Joins the annotations table for the configured rating annotation names and
// orders by AVG(value) DESC via a QueryBuilder closure.
$entities = elgg_get_entities([
	'types' => $widget->content_type,
	'owner_guid' => $owner_guid,
	'container_guid' => $container_guid,
	'limit' => $num_display,
	'preload_owners' => true,
	'annotation_name_value_pairs' => [
		[
			'name' => $annotation_names,
		],
	],
	'order_by' => [
		new OrderByClause(
			function (QueryBuilder $qb) use ($annotation_names) {
				$alias = $qb->joinAnnotationTable('e', 'guid', $annotation_names, 'inner', 'star_rating');
				return "AVG({$alias}.value)";
			},
			'desc'
		),
	],
	'group_by' => 'e.guid',
]);

elgg_push_context('starrating');
$list = '';

foreach ($entities as $entity) {
	$title = elgg_view('output/url', [
		'text' => (isset($entity->title)) ? $entity->title : $entity->name,
		'href' => $entity->getURL(),
		'is_trusted' => true,
	]);

	if (!empty($entity->description)) {
		$desc = elgg_view('output/longtext', [
			'value' => elgg_get_excerpt($entity->description),
		]);
	} else {
		$desc = '';
	}

	$icon = elgg_view_entity_icon($entity, 'small');
	$entity_rating = elgg_stars_get_entity_rating_values($entity, $annotation_names);

	$rating = elgg_view('output/stars', [
		'value' => $entity_rating['value'],
	]);
	$rating .= '<div class="elgg-stars-rating-caption">' . elgg_echo('stars:stats', [$entity_rating['value'], $entity_rating['max'], $entity_rating['count']]) . '</div>';

	$list .= '<li>' . elgg_view_image_block($icon, $title . $desc, [
		'image_alt' => $rating,
	]) . '</li>';
}

elgg_pop_context();

echo '<ul class="elgg-list elgg-list-most-rated">' . $list . '</ul>';
