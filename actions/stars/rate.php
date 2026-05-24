<?php

$guid = (int) get_input('guid');
$entity = get_entity($guid);

if (!$entity instanceof ElggEntity) {
	return elgg_error_response(elgg_echo('stars:rate:error'));
}

$owner = elgg_get_logged_in_user_entity();
$annotation_names = (array) get_input('annotation_names', []);

$response = [];

foreach ($annotation_names as $annotation_name) {
	$annotation_value = get_input($annotation_name);

	if ($entity->canAnnotate(0, $annotation_name) && elgg_stars_is_valid_rating($annotation_value)) {
		$id = $entity->annotate(
			$annotation_name,
			(float) $annotation_value,
			$entity->access_id,
			$owner->guid
		);

		if ($id) {
			elgg_create_river_item([
				'view' => 'stars/river/rating',
				'action_type' => 'stream:rating',
				'subject_guid' => elgg_get_logged_in_user_guid(),
				'object_guid' => $entity->guid,
				'annotation_id' => $id,
			]);
		} else {
			return elgg_error_response(elgg_echo('stars:rate:error'));
		}
	}

	$entity_ratings = elgg_stars_get_entity_rating_values($entity, $annotation_name);
	$response[$guid][$annotation_name] = $entity_ratings;
}

return elgg_ok_response($response, elgg_echo('stars:rate:success'));
