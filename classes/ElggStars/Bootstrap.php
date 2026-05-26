<?php

namespace ElggStars;

use Elgg\PluginBootstrap;

/**
 * elgg_stars plugin bootstrap.
 *
 * Wires up the rating annotation registry and JS dependencies that cannot be
 * expressed declaratively in elgg-plugin.php. Hooks, events, actions, widgets
 * and view extensions are declared in elgg-plugin.php and routed to the
 * static handlers in {@see \ElggStars\Menus} / {@see \ElggStars\Hooks}.
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
		$criteria = \elgg_get_plugin_setting('criteria', 'elgg_stars');
		if (!$criteria) {
			\elgg_stars_register_rating_annotation_name('starrating');
		} else {
			$criteria = string_to_tag_array($criteria);
			foreach ($criteria as $criterion) {
				\elgg_stars_register_rating_annotation_name($criterion);
			}
		}

		// CSS extension and JS dependencies (cannot be declarative in 4.x for AMD modules).
		\elgg_extend_view('elgg.css', 'stars/css');
		\elgg_define_js('jquery.rateit', [
			'src' => '/mod/elgg_stars/vendors/rateit/jquery.rateit.min.js',
			'deps' => ['jquery'],
		]);
		\elgg_require_js('stars/init');
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
