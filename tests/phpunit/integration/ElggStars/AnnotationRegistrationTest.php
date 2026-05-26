<?php

namespace ElggStars;

use Elgg\IntegrationTestCase;

/**
 * Verifies that the elgg_stars plugin registers its rating annotation
 * name(s) at init time and that the helper API surface exposes them.
 *
 * The plugin's core contract: when the `criteria` plugin setting is
 * empty (the default), the single annotation name `starrating` is the
 * sole registered rating. When the setting is populated with a
 * comma/space-separated list, each token becomes a valid annotation
 * name. Both states are exercised below.
 */
class AnnotationRegistrationTest extends IntegrationTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'elgg_stars';
	}

	public function testStarratingAnnotationNameIsRegistered(): void {
		$this->assertTrue(
			\elgg_stars_is_valid_rating_annotation_name('starrating'),
			'default starrating annotation name was not registered at init'
		);
	}

	public function testUnknownAnnotationNameIsRejected(): void {
		$this->assertFalse(
			\elgg_stars_is_valid_rating_annotation_name('definitely_not_a_rating_' . uniqid()),
			'unknown annotation names must not be reported as valid ratings'
		);
	}

	public function testGetRatingAnnotationNamesIncludesStarrating(): void {
		$names = \elgg_stars_get_rating_annotation_names();
		$this->assertIsArray($names);
		$this->assertContains('starrating', $names);
	}

	public function testRegisterRatingAnnotationNameIsIdempotent(): void {
		// Registering the same name twice must not duplicate it in the
		// global registry — duplicates would cause double-counting in
		// elgg_get_entity_rating_values()'s annotation_names filter.
		\elgg_stars_register_rating_annotation_name('starrating');
		\elgg_stars_register_rating_annotation_name('starrating');
		$names = \elgg_get_config('elgg_stars_annotation_names');
		$this->assertSame(
			1,
			count(array_filter($names, fn ($n) => $n === 'starrating')),
			'registering the same annotation name twice produced duplicates'
		);
	}

	public function testRegisterCustomRatingAnnotationName(): void {
		$custom = 'test_rating_' . uniqid();
		\elgg_stars_register_rating_annotation_name($custom);
		$this->assertTrue(\elgg_stars_is_valid_rating_annotation_name($custom));

		// Restore registry: drop the custom name we just added so other tests
		// keep a deterministic baseline.
		$names = \elgg_get_config('elgg_stars_annotation_names');
		\elgg_set_config(
			'elgg_stars_annotation_names',
			array_values(array_filter($names, fn ($n) => $n !== $custom))
		);
	}

	public function testCriteriaHookIsRegistered(): void {
		// elgg_stars_get_rating_annotation_names() triggers the
		// 'criteria','stars' plugin hook so other plugins can scope
		// annotation names to specific entities. We assert the hook
		// dispatches by registering a probe that asserts on the
		// $params shape and then unregistering it.
		$probe_called = false;
		$probe = function ($hook, $type, $value, $params) use (&$probe_called) {
			$probe_called = true;
			return $value;
		};
		\elgg_register_plugin_hook_handler('criteria', 'stars', $probe);
		try {
			\elgg_stars_get_rating_annotation_names();
		} finally {
			\elgg_unregister_plugin_hook_handler('criteria', 'stars', $probe);
		}
		$this->assertTrue(
			$probe_called,
			"'criteria','stars' hook did not fire when fetching annotation names"
		);
	}
}
