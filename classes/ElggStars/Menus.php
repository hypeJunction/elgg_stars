<?php

namespace ElggStars;

use Elgg\Hook;
use ElggMenuItem;

/**
 * Menu registration handlers for elgg_stars.
 *
 * Registered via elgg-plugin.php with the 4.x single-argument signature.
 */
class Menus {

	/**
	 * Append the rating widget to entity / title menus on rateable entities.
	 *
	 * @param \Elgg\Hook $hook 'register','menu:entity'
	 * @return array Updated menu items
	 */
	public static function entityMenu(Hook $hook) {
		$return = $hook->getValue();

		if (!(bool) \elgg_get_plugin_setting('extend_menu', 'elgg_stars')) {
			return $return;
		}

		$entity = $hook->getParam('entity');
		if (!$entity instanceof \ElggEntity) {
			return $return;
		}

		$type_subtype_pairs = \elgg_stars_get_rateable_type_subtype_pairs();
		$type = $entity->getType();
		$subtype = $entity->getSubtype();

		if (!array_key_exists($type, $type_subtype_pairs)) {
			return $return;
		}

		if ($subtype && array_search($subtype, $type_subtype_pairs[$type]) === false) {
			return $return;
		}

		$starrating = [
			'name' => 'stars',
			'priority' => 10,
			'text' => \elgg_view_form('stars/rate', [], $hook->getParams()),
			'href' => false,
			'encode_text' => false,
			'section' => 'rating',
		];

		$return[] = ElggMenuItem::factory($starrating);

		return $return;
	}

	/**
	 * Add a delete-rating item to the annotation menu for owners.
	 *
	 * @param \Elgg\Hook $hook 'register','menu:annotation'
	 * @return array Updated menu items
	 */
	public static function annotationMenu(Hook $hook) {
		$return = $hook->getValue();
		$annotation = $hook->getParam('annotation');

		if (!$annotation instanceof \ElggAnnotation) {
			return $return;
		}

		if (!\elgg_stars_is_valid_rating_annotation_name($annotation->name)) {
			return $return;
		}

		if ($annotation->canEdit()) {
			$return[] = ElggMenuItem::factory([
				'name' => 'delete',
				'text' => \elgg_view_icon('delete'),
				'href' => \elgg_generate_action_url('stars/delete', ['annotation_id' => $annotation->id]),
				'confirm' => true,
			]);
		}

		return $return;
	}
}
