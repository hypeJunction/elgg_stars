<?php

/**
 * PHPUnit bootstrap for elgg_stars plugin tests.
 *
 * Path layout (Docker test stack):
 *   /var/www/html/                          <- $elggRoot
 *   /var/www/html/mod/elgg_stars/           <- $pluginRoot
 *   /var/www/html/mod/elgg_stars/tests/     <- __DIR__
 *
 * tests/ -> mod/elgg_stars/ -> mod/ -> elgg_root/  (3 levels up)
 */

$elggRoot = dirname(__DIR__, 3);
$pluginRoot = dirname(__DIR__);

require_once $elggRoot . '/vendor/autoload.php';

// Make the framework's test base classes (UnitTestCase, IntegrationTestCase) autoloadable.
$testClassesDir = $elggRoot . '/vendor/elgg/elgg/engine/tests/classes';
spl_autoload_register(function ($class) use ($testClassesDir) {
	$file = $testClassesDir . '/' . str_replace('\\', '/', $class) . '.php';
	if (file_exists($file)) {
		require_once $file;
	}
});

// Ensure the plugin's classes/functions are available even when the test DB
// has not (yet) registered the plugin entity. The plugin's PSR-4 namespace
// gets registered by Elgg's plugin loader once the plugin is active in the
// production prefix; the integration-test prefix is a separate sandbox.
spl_autoload_register(function ($class) use ($pluginRoot) {
	$prefix = 'ElggStars\\';
	if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
		return;
	}
	$relative = substr($class, strlen($prefix));
	$file = $pluginRoot . '/classes/ElggStars/' . str_replace('\\', '/', $relative) . '.php';
	if (file_exists($file)) {
		require_once $file;
	}
});

// Load the procedural API the tests exercise (encode/decode/is_valid_rating)
// without waiting for the plugin's init handler — the integration suite uses
// the production-prefix Elgg, which already has the plugin booted, but unit
// helper tests can also rely on the file existing on disk.
require_once $pluginRoot . '/lib/functions.php';

\Elgg\Application::loadCore();
