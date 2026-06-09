<?php

namespace ElggStars;

use Elgg\Event;
use ElggMenuItem;

/**
 * Menu registration handlers for elgg_stars.
 *
 * Registered via elgg-plugin.php with the 5.x single-argument signature.
 */
class Menus {

	/**
	 * Append the rating widget to entity / title menus on rateable entities.
	 *
	 * @param \Elgg\Event $event 'register','menu:entity'
	 * @return array Updated menu items
	 */
	public static function entityMenu(Event $event) {
		$return = $event->getValue();

		if (!(bool) elgg_get_plugin_setting('extend_menu', 'elgg_stars')) {
			return $return;
		}

		$entity = $event->getParam('entity');
		if (!$entity instanceof \ElggEntity) {
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

		$starrating = [
			'name' => 'stars',
			'priority' => 10,
			'text' => self::renderRateForm($event->getParams()),
			'href' => false,
			'encode_text' => false,
			'section' => 'rating',
		];

		$return[] = ElggMenuItem::factory($starrating);

		return $return;
	}

	/**
	 * Render the rating form for use as menu item text.
	 *
	 * The entity menu handler may fire while an *outer* form is being rendered
	 * (e.g. the admin plugin-settings form, or any page that scrapes menus via a
	 * synthetic register,menu:entity event). elgg_view_form() toggles the shared
	 * FormsService "rendering" flag off when it completes, which would corrupt
	 * the outer form's deferred-footer logic and throw a LogicException from
	 * FormsService::getFooter(). We snapshot and restore that flag around the
	 * nested form render so the outer form is unaffected.
	 *
	 * @param array $params register,menu:entity event params (carries 'entity')
	 * @return string Rendered form HTML
	 */
	protected static function renderRateForm(array $params): string {
		$forms = _elgg_services()->forms;

		$rendering_prop = null;
		$was_rendering = false;
		try {
			$ref = new \ReflectionProperty($forms, 'rendering');
			$ref->setAccessible(true);
			$rendering_prop = $ref;
			$was_rendering = (bool) $ref->getValue($forms);
		} catch (\Throwable $e) {
			// FormsService internals changed; fall back to plain render.
			$rendering_prop = null;
		}

		$html = elgg_view_form('stars/rate', [], $params);

		if ($rendering_prop instanceof \ReflectionProperty && $was_rendering) {
			// Restore the outer form-rendering state clobbered by the nested form.
			$rendering_prop->setValue($forms, true);
		}

		return $html;
	}

	/**
	 * Add a delete-rating item to the annotation menu for owners.
	 *
	 * @param \Elgg\Event $event 'register','menu:annotation'
	 * @return array Updated menu items
	 */
	public static function annotationMenu(Event $event) {
		$return = $event->getValue();
		$annotation = $event->getParam('annotation');

		if (!$annotation instanceof \ElggAnnotation) {
			return $return;
		}

		if (!elgg_stars_is_valid_rating_annotation_name($annotation->name)) {
			return $return;
		}

		if ($annotation->canEdit()) {
			$return[] = ElggMenuItem::factory([
				'name' => 'delete',
				'text' => elgg_view_icon('delete'),
				'href' => elgg_generate_action_url('stars/delete', ['annotation_id' => $annotation->id]),
				'confirm' => true,
			]);
		}

		return $return;
	}
}
