<?php

namespace ElggStars;

use Elgg\PluginBootstrap;

/**
 * elgg_stars plugin bootstrap.
 *
 * Wires up the rating annotation registry and JS dependencies that cannot be
 * expressed declaratively in elgg-plugin.php. Events, actions, widgets
 * and view extensions are declared in elgg-plugin.php and routed to the
 * static handlers in {@see \ElggStars\Menus} / {@see \ElggStars\Events}.
 *
 * 6.x: legacy AMD modules (elgg_define_js / elgg_require_js) are gone; the
 * stars init script is now an ES module registered via elgg_register_esm(),
 * while the third-party jQuery rateit plugin (a non-ESM jQuery plugin) is
 * served as a regular <script> tag via elgg_register_external_file().
 */
class Bootstrap extends PluginBootstrap {

	/**
	 * {@inheritdoc}
	 */
	public function load() {
		// Load procedural helpers (registry, settings encode/decode, rating math).
		require_once $this->plugin->getPath() . 'lib/functions.php';
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot() {
	}

	/**
	 * {@inheritdoc}
	 */
	public function init() {
		// Register valid annotation names from plugin settings.
		$criteria = elgg_get_plugin_setting('criteria', 'elgg_stars');
		if (!$criteria) {
			elgg_stars_register_rating_annotation_name('starrating');
		} else {
			$criteria = elgg_string_to_array((string) $criteria);
			foreach ($criteria as $criterion) {
				elgg_stars_register_rating_annotation_name($criterion);
			}
		}

		// CSS extension.
		elgg_extend_view('elgg.css', 'stars/css');

		// The third-party jQuery rateit plugin is not an ES module; register
		// it as a regular external file so a <script> tag is emitted globally.
		elgg_register_external_file(
			'js',
			'jquery.rateit',
			elgg_normalize_url('mod/elgg_stars/vendors/rateit/jquery.rateit.min.js')
		);

		// Register the stars/init and stars/lib modules as ESM. They live under
		// views/default/js/* (a path that doesn't match the desired module name),
		// so they need explicit importmap entries. Auto-import init on every page.
		elgg_register_esm(
			'stars/lib',
			elgg_get_simplecache_url('js/stars/lib.mjs')
		);
		elgg_register_esm(
			'stars/init',
			elgg_get_simplecache_url('js/stars/init.mjs')
		);
		elgg_import_esm('stars/init');
	}

	/**
	 * {@inheritdoc}
	 */
	public function ready() {
	}

	/**
	 * {@inheritdoc}
	 */
	public function shutdown() {
	}

	/**
	 * {@inheritdoc}
	 */
	public function activate() {
		$plugin = $this->plugin;
		if (is_null($plugin->getSetting('min_value'))) {
			$plugin->setSetting('min_value', 0);
		}

		if (is_null($plugin->getSetting('max_value'))) {
			$plugin->setSetting('max_value', 5);
		}

		if (is_null($plugin->getSetting('step'))) {
			$plugin->setSetting('step', 1);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function deactivate() {
	}

	/**
	 * {@inheritdoc}
	 */
	public function upgrade() {
	}
}
