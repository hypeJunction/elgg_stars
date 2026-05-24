<?php

/**
 * Get rating config
 * @return array
 */
function elgg_stars_get_rating_settings() {

	$min = elgg_get_plugin_setting('min_value', 'elgg_stars');
	$max = elgg_get_plugin_setting('max_value', 'elgg_stars');
	$step = elgg_get_plugin_setting('step', 'elgg_stars');

	return [
		'min' => $min,
		'max' => $max,
		'step' => $step
	];
}

/**
 * Add an annotation name to a list of valid rating names.
 *
 * @param string $annotation_name Annotation name to register
 * @return void
 */
function elgg_stars_register_rating_annotation_name($annotation_name) {

	$rating_annotation_names = elgg_get_config('elgg_stars_annotation_names');
	if (!is_array($rating_annotation_names)) {
		$rating_annotation_names = [];
	}

	if (!in_array($annotation_name, $rating_annotation_names)) {
		$rating_annotation_names[] = $annotation_name;
	}

	elgg_set_config('elgg_stars_annotation_names', $rating_annotation_names);
}

/**
 * Get registered rating annotation names for a given entity.
 *
 * @param \ElggEntity|null $entity Entity to scope annotation names to, or null for global
 * @return array
 */
function elgg_stars_get_rating_annotation_names($entity = null) {

	$rating_annotation_names = elgg_get_config('elgg_stars_annotation_names');
	if (!is_array($rating_annotation_names)) {
		$rating_annotation_names = [];
	}

	return elgg_trigger_plugin_hook('criteria', 'stars', ['entity' => $entity], $rating_annotation_names);
}

/**
 * Check if the annotation name has been registered as a rating name.
 *
 * @param string $annotation_name Annotation name to check
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
 * @param \ElggEntity $entity           Entity being rated
 * @param mixed       $annotation_names One or more annotation names (or null for all registered)
 * @return array|boolean Array of rating stats, or false if entity is invalid
 */
function elgg_stars_get_entity_rating_values($entity, $annotation_names = null) {

	if (!elgg_instanceof($entity)) {
		return false;
	}

	if (!$annotation_names) {
		$annotation_names = elgg_stars_get_rating_annotation_names($entity);
	}

	$settings = elgg_stars_get_rating_settings();

	$count = elgg_get_annotations([
		'guid' => $entity->guid,
		'annotation_names' => $annotation_names,
		'count' => true
	]);

	if ($count > 0) {
		$sum = elgg_get_annotations([
			'guid' => $entity->guid,
			'annotation_names' => $annotation_names,
			'annotation_calculation' => 'sum'
		]);

		$average = $sum / $count;
		$average_rounded = round($average, 2);
	} else {
		$sum = $settings['min'];
		$average_rounded = $settings['min'];
	}

	$stats = [
		'count' => $count,
		'sum' => $sum,
		'value' => $average_rounded,
	];

	$values = $stats + $settings;

	return elgg_trigger_plugin_hook('rating', 'stars', ['entity' => $entity], $values);
}

/**
 * Check whether the user has cast a vote on a given entity for any of the supplied annotation names.
 *
 * @param \ElggEntity     $entity           Entity being rated
 * @param \ElggUser|null  $user             User to check (defaults to logged-in user)
 * @param string|string[] $annotation_names Annotation name(s) to look for
 * @return boolean
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

	$votes = elgg_get_annotations([
		'annotation_names' => $annotation_names,
		'guids' => $entity->guid,
		'annotation_owner_guids' => $user->guid,
		'count' => true
	]);

	return ((int) $votes > 0);
}

/**
 * Check if annotation value is in acceptable range.
 *
 * @param float $val Rating value
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
	$type_subtype_pairs = ['object' => []];

	if ($setting) {
		$setting = elgg_stars_decode_setting($setting);
		if (is_array($setting)) {
			foreach ($setting as $s) {
				list($type, $subtype) = explode(':', $s);
				if (!isset($type_subtype_pairs[$type])) {
					$type_subtype_pairs[$type] = [];
				}

				if ($subtype != 'default') {
					$type_subtype_pairs[$type][] = $subtype;
				}
			}
		}
	}

	return $type_subtype_pairs;
}

/**
 * Encode a setting value for storage as JSON.
 *
 * @param mixed $value Value to encode
 * @return string JSON-encoded value
 */
function elgg_stars_encode_setting($value) {
	return json_encode($value);
}

/**
 * Decode a stored setting value.
 *
 * Accepts JSON (preferred, post-3.0) and legacy PHP-serialized payloads
 * (pre-3.0 data) so existing sites continue to work until the upgrade
 * batch runs.
 *
 * @param string $value Stored setting value
 * @return mixed Decoded value, or null when the payload is unreadable
 */
function elgg_stars_decode_setting($value) {
	if (!is_string($value) || $value === '') {
		return null;
	}

	$decoded = json_decode($value, true);
	if ($decoded !== null || $value === 'null') {
		return $decoded;
	}

	// Legacy PHP-serialized payload — disallow object instantiation.
	$legacy = @unserialize($value, ['allowed_classes' => false]);
	return $legacy === false && $value !== 'b:0;' ? null : $legacy;
}

