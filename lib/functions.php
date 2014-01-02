<?php

/**
 * Get rating config
 * @return array
 */
function elgg_stars_get_rating_settings() {

	$min = elgg_get_plugin_setting('min_value', 'elgg_stars');
	$max = elgg_get_plugin_setting('max_value', 'elgg_stars');
	$step = elgg_get_plugin_setting('step', 'elgg_stars');

	return array(
		'min' => $min,
		'max' => $max,
		'step' => $step
	);
}

/**
 * Add an annotation name to a list of valid rating names
 * 
 * @param string $annotation_name
 */
function elgg_stars_register_rating_annotation_name($annotation_name) {

	$rating_annotation_names = elgg_get_config('elgg_stars_annotation_names');
	if (!is_array($rating_annotation_names)) {
		$rating_annotation_names = array();
	}

	if (!in_array($annotation_name, $rating_annotation_names)) {
		$rating_annotation_names[] = $annotation_name;
	}

	elgg_set_config('elgg_stars_annotation_names', $rating_annotation_names);
}

/**
 * Get registered rating annotation names for a given type and subtype
 *
 * @param string $type Entity type
 * @param string $subtype Entity subtype
 * @return array
 */
function elgg_stars_get_rating_annotation_names($entity = null) {

	$rating_annotation_names = elgg_get_config('elgg_stars_annotation_names');
	if (!is_array($rating_annotation_names)) {
		$rating_annotation_names = array();
	}

	return elgg_trigger_plugin_hook('criteria', 'stars', array('entity' => $entity) , $rating_annotation_names);
}

/**
 * Check if the annotation name has been registered as a rating name
 *
 * @param string $annotation_name
 * @return boolean
 */
function elgg_stars_is_valid_rating_annotation_name($annotation_name) {

	$rating_annotation_names = elgg_get_config('elgg_stars_annotation_names');

	if (!is_array($rating_annotation_names)) {
		return false;
	}

	if (!in_array($annotation_name, $rating_annotation_names)) {
		return false;
	}

	return true;
}

/**
 * Calculate entity rating values for a given annotation name
 *
 * @param ElggEntity $entity
 * @param mixed $annotation_names One or more annotation names
 * @return int|boolean
 */
function elgg_stars_get_entity_rating_values($entity, $annotation_names = null) {

	if (!elgg_instanceof($entity)) {
		return false;
	}

	if (!$annotation_names) {
		$annotation_names = elgg_stars_get_rating_annotation_names($entity);
	}

	$settings = elgg_stars_get_rating_settings();

	$count = elgg_get_annotations(array(
		'guid' => $entity->guid,
		'annotation_names' => $annotation_names,
		'count' => true
	));

	if ($count > 0) {
		$sum = elgg_get_annotations(array(
			'guid' => $entity->guid,
			'annotation_names' => $annotation_names,
			'annotation_calculation' => 'sum'
		));

		$average = $sum / $count;
		$average_rounded = round($average, 2);
	} else {
		$sum = $settings['min'];
		$average_rounded = $settings['min'];
	}

	$stats = array(
		'count' => $count,
		'sum' => $sum,
		'value' => $average_rounded,
	);

	$values = $stats + $settings;

	return elgg_trigger_plugin_hook('rating', 'stars', array('entity' => $entity), $values);
}

/**
 * Check if the user has casted a vote on a given entity with a given annotation name
 *
 * @param ElggEntity $entity
 * @param ElggUser $user
 * @param string $annotation_name
 * @return false|array
 */
function elgg_stars_has_user_voted($entity, $user = null, $annotation_names = null) {

	if (!$user) {
		$user = elgg_get_logged_in_user_entity();
	}

	if (!elgg_instanceof($user)) {
		return false;
	}

	if (!$annotation_names) {
		$annotation_names = elgg_stars_get_rating_annotation_names($entity);
	}

	$votes = elgg_get_annotations(array(
		'annotation_names' => $annotation_names,
		'guids' => $entity->guid,
		'annotation_owner_guids' => $user->guid,
		'count' => true
	));

	return ((int) $votes > 0);
}

/**
 * Check if annotation value is in acceptable range
 *
 * @param float $val
 * @return boolean
 */
function elgg_stars_is_valid_rating($val) {

	$settings = elgg_stars_get_rating_settings();

	return ($val > $settings['min'] && $val <= $settings['max']);
}

/**
 * Get site configuration for rateable type subtype pairs
 * @return array
 */
function elgg_stars_get_rateable_type_subtype_pairs() {

	$setting = elgg_get_plugin_setting('type_subtype_pairs', 'elgg_stars');
	$type_subtype_pairs = array('object' => array());

	if ($setting) {
		$setting = unserialize($setting);
		if (is_array($setting)) {
			foreach ($setting as $s) {
				list($type, $subtype) = explode(':', $s);
				if (!isset($type_subtype_pairs[$type])) {
					$type_subtype_pairs[$type] = array();
				}
				if ($subtype != 'default') {
					$type_subtype_pairs[$type][] = $subtype;
				}
			}
		}
	}
	return $type_subtype_pairs;
}
