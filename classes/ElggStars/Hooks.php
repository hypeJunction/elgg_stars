<?php

namespace ElggStars;

use Elgg\Hook;

/**
 * Plugin-hook handlers for elgg_stars.
 *
 * Registered via elgg-plugin.php with the 4.x single-argument signature.
 */
class Hooks {

	/**
	 * Block double-voting on rateable entities.
	 *
	 * @param \Elgg\Hook $hook 'permissions_check:annotate','all'
	 * @return bool Updated permission
	 */
	public static function canAnnotate(Hook $hook) {
		$return = $hook->getValue();
		$entity = $hook->getParam('entity');
		$user = $hook->getParam('user');
		$annotation_name = $hook->getParam('annotation_name');

		if (!elgg_stars_is_valid_rating_annotation_name($annotation_name)) {
			return $return;
		}

		if (!$user instanceof \ElggUser || !$entity instanceof \ElggEntity) {
			return false;
		}

		return !elgg_stars_has_user_voted($entity, $user, $annotation_name);
	}

	/**
	 * Replace the default annotation view with a starrating view
	 * for registered rating annotation names.
	 *
	 * @param \Elgg\Hook $hook 'view','annotation/default'
	 * @return string Rendered view
	 */
	public static function annotationViewReplacement(Hook $hook) {
		$return = $hook->getValue();
		$vars = $hook->getParam('vars');
		$annotation = elgg_extract('annotation', $vars);

		if (!$annotation instanceof \ElggAnnotation) {
			return $return;
		}

		if (elgg_stars_is_valid_rating_annotation_name($annotation->name)) {
			return elgg_view('annotation/starrating', $vars);
		}

		return $return;
	}

	/**
	 * Apply granular per-type/subtype criteria from plugin settings.
	 *
	 * @param \Elgg\Hook $hook 'criteria','stars'
	 * @return array Updated criteria list
	 */
	public static function criteria(Hook $hook) {
		$return = $hook->getValue();
		$entity = $hook->getParam('entity');

		if (!$entity instanceof \ElggEntity) {
			return $return;
		}

		$type = $entity->getType();
		$subtype = $entity->getSubtype();
		if (!$subtype) {
			$subtype = 'default';
		}

		$granular_criteria = elgg_stars_decode_setting(elgg_get_plugin_setting('granular_criteria', 'elgg_stars'));

		if (!is_array($granular_criteria) || !isset($granular_criteria["$type:$subtype"])) {
			return $return;
		}

		return $granular_criteria["$type:$subtype"];
	}

	/**
	 * Append the ratings module to comments output (view extension via hook).
	 *
	 * @param \Elgg\Hook $hook 'view','page/elements/comments' OR 'comments','all'
	 * @return string Augmented output
	 */
	public static function commentsRatingAddon(Hook $hook) {
		$output = $hook->getValue();
		$vars = ($hook->getName() === 'view') ? $hook->getParam('vars') : $hook->getParams();

		$ratings_view = elgg_view('stars/ratings', $vars);

		return $output . $ratings_view;
	}
}
