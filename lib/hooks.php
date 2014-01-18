<?php

/**
 * Add starrating menu items to rateable entities
 *
 * @param string $hook Equals 'register'
 * @param string $type Equals 'menu:entity' or 'menu:title'
 * @param array $return Current menu items
 * @param array $params Additional params
 * @return array Update menu items
 */
function elgg_stars_menu_setup($hook, $type, $return, $params) {

	if (!(bool)elgg_get_plugin_setting('extend_menu', 'elgg_stars')) {
		return $return;
	}

	$entity = elgg_extract('entity', $params, false);
	if (!elgg_instanceof($entity)) {
		return $return;
	}

	$type_subtype_pairs = elgg_stars_get_rateable_type_subtype_pairs();
	$type = $entity->getType();
	$subtype = $entity->getSubtype();

	if (!array_key_exists($type, $type_subtype_pairs)) {
		return $return;
	}

	if ($subtype && array_search($subtype, $type_subtype_pairs[$type]) === false) {
		return $return;
	}

	$starrating = array(
		'name' => 'stars',
		'priority' => 10,
		'text' => elgg_view_form('stars/rate', array(), $params),
		'href' => false,
		'encode_text' => false,
		'section' => 'rating'
	);
	$return[] = ElggMenuItem::factory($starrating);

	return $return;
}

/**
 * Setup starrating annotation menu
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 */
function elgg_stars_annotation_menu_setup($hook, $type, $return, $params) {

	$annotation = elgg_extract('annotation', $params);

	if (!elgg_stars_is_valid_rating_annotation_name($annotation->name)) {
		return $return;
	}

	if ($annotation->canEdit()) {
		$return[] = ElggMenuItem::factory(array(
			'name' => 'delete',
			'text' => elgg_view_icon('delete'),
			'href' => 'action/stars/delete?annotation_id=' . $annotation->id,
			'is_action' => true
		));
	}

	return $return;
}

/**
 * Check if the user can rate this entity with a given annotation name
 *
 * @param string $hook Equals 'permissions_check:annotate'
 * @param string $type Any entity type
 * @param boolean $return Current permission
 * @param array $params Additional params
 * @return boolean Updated permission
 */
function elgg_stars_can_annotate($hook, $type, $return, $params) {

	$entity = elgg_extract('entity', $params);
	$user = elgg_extract('user', $params);
	$annotation_name = elgg_extract('annotation_name', $params);

	if (!elgg_stars_is_valid_rating_annotation_name($annotation_name)) {
		return $return;
	}

	if (!elgg_instanceof($user) || !elgg_instanceof($entity)) {
		return false;
	}

	return !elgg_stars_has_user_voted($entity, $user, $annotation_name);
}

/**
 * Replace default annotation view with a starrating annotation view for registered rating annotation names
 *
 * @param string $hook Equals 'view'
 * @param string $type Equals 'annotation/default'
 * @param string $return Current view
 * @param array $params Additional params
 * @return string
 */
function elgg_stars_annotation_view_replacement($hook, $type, $return, $params) {

	$vars = elgg_extract('vars', $params);
	$annotation = elgg_extract('annotation', $vars);

	if (!$annotation instanceof ElggAnnotation) {
		return $return;
	}

	if (elgg_stars_is_valid_rating_annotation_name($annotation->name)) {
		return elgg_view('annotation/starrating', $vars);
	}

	return $return;
}

/**
 * Apply plugins settings to entity ratings criteria
 *
 * @param string $hook Equals 'criteria'
 * @param string $type Equals 'stars'
 * @param array $return Current list of criteria
 * @param array $params Additional params
 * @return array Updated list of criteria
 */
function elgg_stars_rating_criteria_hook($hook, $type, $return, $params) {

	$entity = elgg_extract('entity', $params);

	if (!elgg_instanceof($entity)) {
		return $return;
	}

	$type = $entity->getType();
	$subtype = $entity->getSubtype();
	if (!$subtype) {
		$subtype = 'default';
	}

	$granular_criteria = unserialize(elgg_get_plugin_setting('granular_criteria', 'elgg_stars'));

	return $granular_criteria["$type:$subtype"];
}

/**
 * Append the rating module to comments
 * Note: Using a plugin hook, since some plugins overwrite the output completely
 */
function elgg_stars_comments_rating_addon($hook, $type, $output, $params) {

	$vars = ($hook == 'view') ? elgg_extract('vars', $params) : $params;

	$ratings_view = elgg_view('stars/ratings', $vars);

	return $output . $ratings_view;
}