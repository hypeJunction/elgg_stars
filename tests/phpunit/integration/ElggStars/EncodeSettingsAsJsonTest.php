<?php

namespace ElggStars;

use Elgg\IntegrationTestCase;
use Elgg\Upgrade\Result;
use ElggStars\Upgrades\EncodeSettingsAsJson;

/**
 * Covers the EncodeSettingsAsJson upgrade batch introduced in the 3.x
 * migration. Two properties matter:
 *
 *   1. Correctness: legacy serialize()'d arrays in the `type_subtype_pairs`
 *      and `granular_criteria` plugin settings end up re-stored as JSON.
 *   2. Idempotence: running the batch a second time (or any number of
 *      times) against already-JSON data is a no-op — the data does not
 *      mutate and shouldBeSkipped() flips to true.
 */
class EncodeSettingsAsJsonTest extends IntegrationTestCase {

	/**
	 * @var string[] Settings the batch operates on, mirrored from the SUT.
	 */
	private const KEYS = ['type_subtype_pairs', 'granular_criteria'];

	public function up() {
		// Save current setting values so each test starts from a known state
		// and can restore the production-like baseline at tear-down.
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$this->saved = [];
		foreach (self::KEYS as $k) {
			$this->saved[$k] = $plugin->getSetting($k);
		}
	}

	public function down() {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		foreach ($this->saved ?? [] as $k => $v) {
			if ($v === null || $v === false) {
				$plugin->unsetSetting($k);
			} else {
				$plugin->setSetting($k, $v);
			}
		}
	}

	/** @var array<string,mixed> */
	private $saved = [];

	public function getPluginID(): string {
		return 'elgg_stars';
	}

	public function testGetVersionIsDateStamp(): void {
		$u = new EncodeSettingsAsJson();
		// yyyymmddnn — eight digits + optional NN counter; allow 8-10 digits.
		// PHPUnit 8 uses assertRegExp(); 9+ renamed it to
		// assertMatchesRegularExpression. Stick with the 8.x name since the
		// Elgg 3.x test stack pins phpunit ^7.5 || ^8.5.
		$this->assertRegExp('/^\d{8,10}$/', (string) $u->getVersion());
	}

	public function testCountItemsMatchesSettingsCount(): void {
		$u = new EncodeSettingsAsJson();
		$this->assertSame(count(self::KEYS), $u->countItems());
	}

	public function testNeedsIncrementOffsetIsFalse(): void {
		$u = new EncodeSettingsAsJson();
		$this->assertFalse($u->needsIncrementOffset());
	}

	public function testShouldBeSkippedWhenAllSettingsAreJson(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('type_subtype_pairs', json_encode(['object:blog']));
		$plugin->setSetting('granular_criteria', json_encode(['object:blog' => ['accuracy']]));

		$u = new EncodeSettingsAsJson();
		$this->assertTrue(
			$u->shouldBeSkipped(),
			'upgrade must skip when both settings already hold JSON'
		);
	}

	public function testShouldNotBeSkippedWhenLegacySerializedPresent(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->setSetting('type_subtype_pairs', serialize(['object:blog']));

		$u = new EncodeSettingsAsJson();
		$this->assertFalse(
			$u->shouldBeSkipped(),
			'upgrade must run when at least one setting is still PHP-serialized'
		);
	}

	public function testRunConvertsLegacySerializedToJson(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$legacy_pairs = ['object:blog', 'object:page'];
		$legacy_criteria = ['object:blog' => ['accuracy', 'clarity']];

		$plugin->setSetting('type_subtype_pairs', serialize($legacy_pairs));
		$plugin->setSetting('granular_criteria', serialize($legacy_criteria));

		$u = new EncodeSettingsAsJson();
		$result = $u->run(new Result(), 0);

		$this->assertInstanceOf(Result::class, $result);
		$this->assertSame(2, $result->getSuccessCount());
		$this->assertSame(0, $result->getFailureCount());

		// Re-load and confirm the stored payloads are now JSON.
		$pairs_after = $plugin->getSetting('type_subtype_pairs');
		$criteria_after = $plugin->getSetting('granular_criteria');

		$this->assertSame($legacy_pairs, json_decode($pairs_after, true));
		$this->assertSame($legacy_criteria, json_decode($criteria_after, true));
	}

	public function testRunIsIdempotent(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$pairs = ['object:blog'];
		$criteria = ['object:blog' => ['accuracy']];

		$plugin->setSetting('type_subtype_pairs', serialize($pairs));
		$plugin->setSetting('granular_criteria', serialize($criteria));

		$u = new EncodeSettingsAsJson();

		// First run: converts.
		$r1 = $u->run(new Result(), 0);
		$this->assertSame(2, $r1->getSuccessCount());
		$this->assertSame(0, $r1->getFailureCount());

		$pairs_after_first = $plugin->getSetting('type_subtype_pairs');
		$criteria_after_first = $plugin->getSetting('granular_criteria');

		// Second run on already-JSON data: no errors, all successes, content
		// untouched. This is the contract that lets an admin re-trigger the
		// upgrade or run a partial replay without data loss.
		$r2 = $u->run(new Result(), 0);
		$this->assertSame(2, $r2->getSuccessCount());
		$this->assertSame(0, $r2->getFailureCount());

		$this->assertSame(
			$pairs_after_first,
			$plugin->getSetting('type_subtype_pairs'),
			'type_subtype_pairs mutated on second run — upgrade not idempotent'
		);
		$this->assertSame(
			$criteria_after_first,
			$plugin->getSetting('granular_criteria'),
			'granular_criteria mutated on second run — upgrade not idempotent'
		);

		// Third run for good measure.
		$r3 = $u->run(new Result(), 0);
		$this->assertSame(2, $r3->getSuccessCount());
		$this->assertSame(0, $r3->getFailureCount());
		$this->assertSame($pairs_after_first, $plugin->getSetting('type_subtype_pairs'));
		$this->assertSame($criteria_after_first, $plugin->getSetting('granular_criteria'));
	}

	public function testRunSkipsEmptySettings(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$plugin->unsetSetting('type_subtype_pairs');
		$plugin->unsetSetting('granular_criteria');

		$u = new EncodeSettingsAsJson();
		$result = $u->run(new Result(), 0);
		// Empty settings count as success — there's nothing to convert.
		$this->assertSame(2, $result->getSuccessCount());
		$this->assertSame(0, $result->getFailureCount());
	}

	public function testRunRecordsFailureOnUndecodableLegacy(): void {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		// Garbage that's neither JSON nor valid serialize() output.
		$plugin->setSetting('type_subtype_pairs', 'not-a-payload-' . uniqid());
		$plugin->setSetting('granular_criteria', json_encode(['object:blog' => ['accuracy']]));

		$u = new EncodeSettingsAsJson();
		$result = $u->run(new Result(), 0);
		$this->assertSame(1, $result->getFailureCount());
		$this->assertSame(1, $result->getSuccessCount());
	}

	public function testDecoderUsedByPluginRoundtripsAfterUpgrade(): void {
		// End-to-end: store legacy, run upgrade, then read via the plugin's
		// own helper (elgg_stars_get_rateable_type_subtype_pairs) to confirm
		// the readable shape matches the original.
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		$legacy = ['object:blog', 'object:page', 'group:default'];
		$plugin->setSetting('type_subtype_pairs', serialize($legacy));

		$u = new EncodeSettingsAsJson();
		$u->run(new Result(), 0);

		$pairs = elgg_stars_get_rateable_type_subtype_pairs();
		$this->assertArrayHasKey('object', $pairs);
		$this->assertArrayHasKey('group', $pairs);
		$this->assertContains('blog', $pairs['object']);
		$this->assertContains('page', $pairs['object']);
	}
}
