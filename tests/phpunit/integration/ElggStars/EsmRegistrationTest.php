<?php

namespace ElggStars;

use Elgg\IntegrationTestCase;

/**
 * Regression coverage for the Elgg 7 JavaScript migration.
 *
 *   86c13c8 — the stars/init and stars/lib modules moved from AMD to ESM and
 *             are registered in the importmap via elgg_register_esm() under
 *             names that do NOT match their view path. stars/init is
 *             auto-imported on every page via elgg_import_esm().
 *
 *   3bd0888 — the vendored classic (non-ESM) jquery.rateit plugin is served
 *             through the importmap as elgg_stars/rateit (pointing at the
 *             vendored file) rather than a plain <script> tag, because Elgg 7
 *             jQuery is itself an ES module and a bare script throws
 *             "jQuery is not defined".
 *
 * Bootstrap::init() performs these registrations, so on a booted Elgg 7 the
 * ESM service must expose them.
 */
class EsmRegistrationTest extends IntegrationTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'elgg_stars';
	}

	public function testStarsModulesRegisteredInImportMap(): void {
		$imports = _elgg_services()->esm->getImportMapData()['imports'] ?? [];

		foreach (['stars/init', 'stars/lib', 'elgg_stars/rateit'] as $module) {
			$this->assertArrayHasKey(
				$module,
				$imports,
				"ESM module '{$module}' was not registered in the importmap by Bootstrap::init()"
			);
		}

		$this->assertStringContainsString(
			'vendors/rateit/jquery.rateit.min.js',
			$imports['elgg_stars/rateit'],
			'elgg_stars/rateit must resolve to the vendored jquery.rateit plugin file'
		);
	}

	public function testStarsInitIsAutoImported(): void {
		$this->assertContains(
			'stars/init',
			_elgg_services()->esm->getImports(),
			'stars/init must be auto-imported on every page via elgg_import_esm()'
		);
	}
}
