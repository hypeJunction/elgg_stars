<?php

namespace ElggStars;

use Elgg\Event;
use Elgg\IntegrationTestCase;

/**
 * Regression coverage for the 4.x/5.x/6.x/7.x handler migration of the
 * elgg_stars Menus and Events classes.
 *
 * Two migration fixes are guarded here:
 *
 *   a892b15 — legacy plugin_hook helpers were replaced with the unified
 *             \Elgg\Event API. Every handler now takes a single \Elgg\Event
 *             and reads state via getValue()/getParam(). We construct real
 *             Event objects (via elgg()'s public container, exactly as
 *             \Elgg\HandlersService does) and call the static handlers to
 *             prove they operate on Event and produce the documented result.
 *
 *   33b9212 — Elgg 7 register/menu handlers return a plain array of
 *             ElggMenuItem; the old $menu->add() call fatals. We assert the
 *             handlers append via array-return and grow the menu.
 *
 * These handlers are registered declaratively in elgg-plugin.php, so calling
 * them directly keeps each assertion isolated from unrelated core handlers on
 * the same event name.
 */
class MenusEventsTest extends IntegrationTestCase {

	/** @var array<string,mixed> */
	private $saved = [];

	private const KEYS = ['extend_menu', 'type_subtype_pairs', 'granular_criteria'];

	public function up() {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		foreach (self::KEYS as $k) {
			$this->saved[$k] = $plugin->getSetting($k);
		}
	}

	public function down() {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		foreach ($this->saved as $k => $v) {
			if ($v === null || $v === false) {
				$plugin->unsetSetting($k);
			} else {
				$plugin->setSetting($k, $v);
			}
		}
	}

	public function getPluginID(): string {
		return 'elgg_stars';
	}

	private function event(string $name, string $type, $value, array $params): Event {
		// Mirror \Elgg\HandlersService::call — new Event(elgg(), name, type, value, params).
		return new Event(elgg(), $name, $type, $value, $params);
	}

	public function testCanAnnotateBlocksSecondVote(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog']);

		$params = ['entity' => $entity, 'user' => $user, 'annotation_name' => 'starrating'];

		// No vote yet → permission passes through (user may rate).
		$this->assertTrue(
			Events::canAnnotate($this->event('permissions_check:annotate', 'all', true, $params)),
			'canAnnotate must allow a first vote on a rateable entity'
		);

		create_annotation($entity->guid, 'starrating', 4, '', $user->guid, ACCESS_PUBLIC);

		// After voting → denied (one rating per user).
		$this->assertFalse(
			Events::canAnnotate($this->event('permissions_check:annotate', 'all', true, $params)),
			'canAnnotate must block a second vote by the same user'
		);
	}

	public function testCanAnnotatePassesThroughForNonRatingAnnotation(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog']);

		// A non-rating annotation name must not be touched — the incoming value
		// is returned verbatim regardless of prior votes.
		$params = ['entity' => $entity, 'user' => $user, 'annotation_name' => 'generic_comment'];
		$event = $this->event('permissions_check:annotate', 'all', true, $params);

		$this->assertTrue(
			Events::canAnnotate($event),
			'canAnnotate must pass the value through untouched for non-rating annotation names'
		);
	}

	public function testCriteriaAppliesGranularSettingAndPassesThroughOtherwise(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('granular_criteria', json_encode([
			'object:blog' => ['accuracy', 'clarity'],
		]));

		$blog = $this->createObject(['subtype' => 'blog']);
		$configured = Events::criteria(
			$this->event('criteria', 'stars', ['starrating'], ['entity' => $blog])
		);
		$this->assertSame(
			['accuracy', 'clarity'],
			$configured,
			'criteria must return the granular list configured for object:blog'
		);

		// A type/subtype with no granular config returns the incoming list unchanged.
		$page = $this->createObject(['subtype' => 'page']);
		$passthrough = Events::criteria(
			$this->event('criteria', 'stars', ['starrating'], ['entity' => $page])
		);
		$this->assertSame(
			['starrating'],
			$passthrough,
			'criteria must pass through the incoming names when no granular config matches'
		);
	}

	public function testAnnotationViewReplacementSwapsRatingView(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog']);
		$id = create_annotation($entity->guid, 'starrating', 5, '', $user->guid, ACCESS_PUBLIC);
		$annotation = elgg_get_annotation_from_id($id);

		$sentinel = '__ORIGINAL_ANNOTATION_VIEW__';

		// Registered rating annotation → view is swapped to annotation/starrating.
		$swapped = Events::annotationViewReplacement(
			$this->event('view', 'annotation/default', $sentinel, ['vars' => ['annotation' => $annotation]])
		);
		$this->assertNotSame(
			$sentinel,
			$swapped,
			'annotationViewReplacement must swap the default view for a rating annotation'
		);

		// Non-rating annotation name → original output is preserved.
		$commentId = create_annotation($entity->guid, 'generic_comment', 1, 'hi', $user->guid, ACCESS_PUBLIC);
		$comment = elgg_get_annotation_from_id($commentId);
		$kept = Events::annotationViewReplacement(
			$this->event('view', 'annotation/default', $sentinel, ['vars' => ['annotation' => $comment]])
		);
		$this->assertSame(
			$sentinel,
			$kept,
			'annotationViewReplacement must not touch non-rating annotations'
		);
	}

	public function testEntityMenuAppendsStarsItemWhenRateable(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('extend_menu', 1);
		$plugin->setSetting('type_subtype_pairs', json_encode(['object:blog']));

		$entity = $this->createObject(['subtype' => 'blog']);

		$result = Menus::entityMenu(
			$this->event('register', 'menu:entity', [], ['entity' => $entity])
		);

		$this->assertIsArray($result, 'entity menu handler must return an array (7.x menu contract)');
		$this->assertCount(1, $result, 'a single stars menu item must be appended for a rateable entity');
		$this->assertInstanceOf(\ElggMenuItem::class, $result[0]);
		$this->assertSame('stars', $result[0]->getName());
	}

	public function testEntityMenuUnchangedWhenExtendMenuOff(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->unsetSetting('extend_menu');

		$entity = $this->createObject(['subtype' => 'blog']);
		$result = Menus::entityMenu(
			$this->event('register', 'menu:entity', [], ['entity' => $entity])
		);

		$this->assertSame([], $result, 'entity menu must be untouched when extend_menu is off');
	}

	public function testAnnotationMenuAddsDeleteForOwner(): void {
		$user = $this->createUser();
		$entity = $this->createObject(['subtype' => 'blog']);

		$session = elgg_get_session();
		$session->setLoggedInUser($user);
		try {
			$id = create_annotation($entity->guid, 'starrating', 3, '', $user->guid, ACCESS_PUBLIC);
			$annotation = elgg_get_annotation_from_id($id);

			$result = Menus::annotationMenu(
				$this->event('register', 'menu:annotation', [], ['annotation' => $annotation])
			);

			$this->assertIsArray($result, 'annotation menu handler must return an array (7.x menu contract)');
			$names = array_map(static fn (\ElggMenuItem $i) => $i->getName(), $result);
			$this->assertContains(
				'delete',
				$names,
				'owner-editable rating annotation must gain a delete menu item'
			);
		} finally {
			$session->removeLoggedInUser();
		}
	}
}
