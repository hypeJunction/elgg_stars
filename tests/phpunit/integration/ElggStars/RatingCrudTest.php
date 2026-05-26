<?php

namespace ElggStars;

use Elgg\IntegrationTestCase;

/**
 * Round-trip coverage for star-rating annotations: create via the core
 * annotation API, verify aggregation and "has user voted" lookups, then
 * delete and verify cleanup.
 *
 * The plugin's stars/rate action is a thin wrapper around
 * create_annotation() with the entity's access_id and the logged-in
 * user as owner. We exercise the same primitives directly so the test
 * stays decoupled from action-dispatch internals (CSRF, forward(), etc.)
 * while still covering the database round trip that any rating goes
 * through.
 */
class RatingCrudTest extends IntegrationTestCase {

	public function up() {
		// Each test creates its own entities via $this->createObject() /
		// createUser() which are auto-cleaned by the seeding trait.
	}

	public function down() {}

	public function getPluginID(): string {
		return 'elgg_stars';
	}

	public function testCreateRatingAnnotationPersists(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog']);

		$id = create_annotation(
			$entity->guid,
			'starrating',
			4,
			'',
			$user->guid,
			ACCESS_PUBLIC
		);
		$this->assertNotEmpty($id, 'create_annotation returned no id');

		$loaded = elgg_get_annotation_from_id($id);
		$this->assertNotNull($loaded);
		$this->assertSame('starrating', $loaded->name);
		$this->assertEquals(4, (int) $loaded->value);
		$this->assertEquals($user->guid, $loaded->owner_guid);
		$this->assertEquals($entity->guid, $loaded->entity_guid);
	}

	public function testGetEntityRatingValuesAggregatesAcrossUsers(): void {
		// Plugin settings min=0/max=5/step=1 are seeded by activate.php on first
		// activation; we set them explicitly here to be insensitive to the order
		// in which the test stack is brought up.
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('min_value', 0);
		$plugin->setSetting('max_value', 5);
		$plugin->setSetting('step', 1);

		$entity = $this->createObject(['subtype' => 'blog']);
		$alice = $this->createUser();
		$bob = $this->createUser();

		create_annotation($entity->guid, 'starrating', 4, '', $alice->guid, ACCESS_PUBLIC);
		create_annotation($entity->guid, 'starrating', 2, '', $bob->guid, ACCESS_PUBLIC);

		$stats = elgg_stars_get_entity_rating_values($entity, 'starrating');

		$this->assertIsArray($stats);
		$this->assertEquals(2, (int) $stats['count']);
		$this->assertEquals(6, (int) $stats['sum']);
		$this->assertEqualsWithDelta(3.0, (float) $stats['value'], 0.0001);
		$this->assertEquals(0, (int) $stats['min']);
		$this->assertEquals(5, (int) $stats['max']);
	}

	public function testGetEntityRatingValuesWithZeroVotesReturnsMin(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('min_value', 0);
		$plugin->setSetting('max_value', 5);
		$plugin->setSetting('step', 1);

		$entity = $this->createObject(['subtype' => 'blog']);
		$stats = elgg_stars_get_entity_rating_values($entity, 'starrating');

		$this->assertEquals(0, (int) $stats['count']);
		// With no votes, sum + value default to the configured minimum.
		$this->assertEquals(0, (int) $stats['sum']);
		$this->assertEquals(0, (int) $stats['value']);
	}

	public function testHasUserVotedTracksAnnotationOwnership(): void {
		$user = $this->createUser();
		$other = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog']);

		$this->assertFalse(elgg_stars_has_user_voted($entity, $user, 'starrating'));

		create_annotation($entity->guid, 'starrating', 5, '', $user->guid, ACCESS_PUBLIC);

		$this->assertTrue(elgg_stars_has_user_voted($entity, $user, 'starrating'));
		$this->assertFalse(
			elgg_stars_has_user_voted($entity, $other, 'starrating'),
			'has_user_voted leaked across users'
		);
	}

	public function testDeleteRatingAnnotationRemovesItFromCount(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('min_value', 0);
		$plugin->setSetting('max_value', 5);
		$plugin->setSetting('step', 1);

		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog']);

		// $annotation->delete() runs ElggAnnotation::canEdit() which checks the
		// currently-logged-in user. IntegrationTestCase starts with no session,
		// so we sign the annotation owner in for the duration of the delete.
		$session = elgg_get_session();
		$session->setLoggedInUser($user);
		try {
			$id = create_annotation($entity->guid, 'starrating', 3, '', $user->guid, ACCESS_PUBLIC);
			$this->assertNotEmpty($id);

			$before = elgg_stars_get_entity_rating_values($entity, 'starrating');
			$this->assertEquals(1, (int) $before['count']);

			$annotation = elgg_get_annotation_from_id($id);
			$this->assertTrue($annotation->delete(), 'annotation->delete() returned false');

			$after = elgg_stars_get_entity_rating_values($entity, 'starrating');
			$this->assertEquals(0, (int) $after['count']);
			// Elgg 3.x returns false (not null) when an annotation id no longer
			// resolves — assert against the falsy sentinel directly so this is
			// stable across the 3.x → 4.x → 5.x return-shape evolution.
			$this->assertEmpty(elgg_get_annotation_from_id($id));
		} finally {
			$session->removeLoggedInUser();
		}
	}

	public function testIsValidRatingHonoursPluginSettings(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('min_value', 0);
		$plugin->setSetting('max_value', 5);
		$plugin->setSetting('step', 1);

		// Plugin defines valid as: $val > min && $val <= max.
		// So at the default 0..5 range:
		$this->assertFalse(elgg_stars_is_valid_rating(0), '0 must be invalid (rule: > min)');
		$this->assertTrue(elgg_stars_is_valid_rating(1));
		$this->assertTrue(elgg_stars_is_valid_rating(5));
		$this->assertFalse(elgg_stars_is_valid_rating(6));
	}
}
