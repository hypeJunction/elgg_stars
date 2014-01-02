<?php

$guid = get_input('guid', false);
$entity = get_entity($guid);

if (!elgg_instanceof($entity)) {
	register_error(elgg_echo('stars:rate:error'));
	forward(REFERER);
}


$owner = elgg_get_logged_in_user_entity();

$annotation_names = get_input('annotation_names');

foreach ($annotation_names as $annotation_name) {

	$annotation_value = get_input($annotation_name);

	if ($entity->canAnnotate(0, $annotation_name) && elgg_stars_is_valid_rating($annotation_value)) {
		$id = create_annotation($guid, $annotation_name, (float) $annotation_value, '', $owner->guid, $entity->access_id);
		if ($id) {
			add_to_river('stars/river/rating', "stream:rating", elgg_get_logged_in_user_guid(), $entity->guid, $entity->access_id, time(), $id);
		} else {
			register_error(elgg_echo('stars:rate:error'));
		}
	}

	$entity_ratings = elgg_stars_get_entity_rating_values($entity, $annotation_name);
	$response[$guid][$annotation_name] = $entity_ratings;
	
}

if (elgg_is_xhr()) {
	system_message(elgg_echo('stars:rate:success'));
	print(json_encode($response));
}

forward(REFERER);
