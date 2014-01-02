<?php
$widget = elgg_extract('entity', $vars);

if (elgg_in_context('profile') || elgg_in_context('groups')) {
	$page_owner = elgg_get_page_owner_entity();
}

if (elgg_instanceof($page_owner, 'user')) {
	$owner_guid = $page_owner->guid;
	$container_guid = ELGG_ENTITIES_ANY_VALUE;
} else if (elgg_instanceof($page_owner, 'group')) {
	$owner_guid = ELGG_ENTITIES_ANY_VALUE;
	$container_guid = $page_owner->guid;
} else {
	$owner_guid = ELGG_ENTITIES_ANY_VALUE;
	$container_guid = ELGG_ENTITIES_ANY_VALUE;
}

$annotation_names = elgg_stars_get_rating_annotation_names();
$entity_rating = array(
	'types' => $widget->content_type,
	'owner_guid' => $owner_guid,
	'container_guid' => $container_guid,
	'annotation_names' => $annotation_names,
	'calculation' => 'avg',
	'order_by' => 'annotation_calculation desc',
	'limit' => $widget->num_display
);

$entities = elgg_get_entities_from_annotation_calculation($entity_rating);

elgg_push_context('starrating');

foreach ($entities as $entity) {

	$title = elgg_view('output/url', array(
		'text' => (isset($entity->title)) ? $entity->title : $entity->name,
		'href' => $entity->getURL(),
		'is_trusted' => true
	));

	if (!empty($entity->description)) {
		$desc = elgg_view('output/longtext', array(
			'value' => elgg_get_excerpt($entity->description)
		));
	} else {
		$desc = '';
	}

	$icon = elgg_view_entity_icon($entity, 'small');
	$entity_rating = elgg_stars_get_entity_rating_values($entity, $annotation_names);

	$rating = elgg_view('output/stars', array(
		'value' => $entity_rating['value']
	));
	$rating .= '<div class="elgg-stars-rating-caption">' . elgg_echo('stars:stats', array($entity_rating['value'], $entity_rating['max'], $entity_rating['count'])) . '</div>';

	$list .= '<li>' . elgg_view_image_block($icon, $title . $desc, array(
				'image_alt' => $rating
			)) . '</li>';
}
elgg_pop_context();

echo '<ul class="elgg-list elgg-list-most-rated">' . $list . '</ul>';
?>
<script type="text/javascript">
	$('.elgg-list-most-rated').ready(function() {
		$('.rateit').rateit();
	})
</script>
