<?php

namespace ElggStars\Upgrades;

use Elgg\Upgrade\AsynchronousUpgrade;
use Elgg\Upgrade\Result;

/**
 * Re-encodes legacy serialize()-stored plugin settings as JSON.
 *
 * elgg_stars 3.x replaced serialize()/unserialize() of plugin settings with
 * json_encode()/json_decode() to avoid PHP object injection risk. A runtime
 * decoder (elgg_stars_decode_setting) accepts both formats so existing sites
 * keep working until this upgrade runs. After it completes, all settings are
 * stored as JSON and the legacy fallback path is unused.
 */
class EncodeSettingsAsJson extends AsynchronousUpgrade {

	/**
	 * Settings that historically held PHP-serialized arrays.
	 *
	 * @var string[]
	 */
	private static $arraySettings = [
		'type_subtype_pairs',
		'granular_criteria',
	];

	/**
	 * {@inheritdoc}
	 */
	public function getVersion(): int {
		return 2026052400;
	}

	/**
	 * {@inheritdoc}
	 */
	// Elgg only treats a needsIncrementOffset() === false batch as finished when
	// countItems() SHRINKS to zero (Upgrade\Loop::isCompleted). countItems() here
	// is a constant, so returning false made the runner call run() forever — the
	// upgrade never completed, and every later upgrade (including core's
	// MigratePageTop) stayed pending behind it. run() does all of its work in one
	// pass, so let the loop finish on processed >= count instead.
	public function needsIncrementOffset(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function shouldBeSkipped(): bool {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		if (!$plugin instanceof \ElggPlugin) {
			return true;
		}

		foreach (self::$arraySettings as $key) {
			$raw = $plugin->getSetting($key);
			if ($raw && json_decode($raw, true) === null && $raw !== 'null') {
				return false;
			}
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function countItems(): int {
		return count(self::$arraySettings);
	}

	/**
	 * {@inheritdoc}
	 */
	public function run(Result $result, $offset): Result {
		$plugin = elgg_get_plugin_from_id('elgg_stars');
		if (!$plugin instanceof \ElggPlugin) {
			$result->addError('elgg_stars plugin entity not found');
			$result->addFailures(count(self::$arraySettings));
			return $result;
		}

		foreach (self::$arraySettings as $key) {
			$raw = $plugin->getSetting($key);

			if (!$raw) {
				$result->addSuccesses();
				continue;
			}

			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				$result->addSuccesses();
				continue;
			}

			$decoded = @unserialize($raw, ['allowed_classes' => false]);
			if (!is_array($decoded)) {
				$result->addError("elgg_stars: could not decode setting '{$key}' — skipping");
				$result->addFailures();
				continue;
			}

			if ($plugin->setSetting($key, json_encode($decoded))) {
				$result->addSuccesses();
			} else {
				$result->addError("elgg_stars: failed to save re-encoded setting '{$key}'");
				$result->addFailures();
			}
		}

		return $result;
	}
}
