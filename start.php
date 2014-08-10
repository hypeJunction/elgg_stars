<?php

/* Elgg Stars
 *
 * Content Rating
 *
 * @author Ismayil Khayredinov <ismayil.khayredinov@gmail.com>
 * @copyright Copyrigh (c) 2011-2014, Ismayil Khayredinov
 */

// Load libraries
require_once(dirname(__FILE__) . "/lib/functions.php");
require_once(dirname(__FILE__) . "/lib/hooks.php");

elgg_register_event_handler('init', 'system', 'elgg_stars_init');

function elgg_stars_init() {

	// Register valid annotaiton names
	$criteria = elgg_get_plugin_setting('criteria', 'elgg_stars');
	if (!$criteria) {
		elgg_stars_register_rating_annotation_name('starrating');
	} else {
		$criteria = string_to_tag_array($criteria);
		foreach ($criteria as $criterion) {
			elgg_stars_register_rating_annotation_name($criterion);
		}
	}
	
	$root = dirname(__FILE__);

	// Register actions
	elgg_register_action('elgg_stars/settings/save', "{$root}/actions/settings/elgg_stars.php", 'admin');
	elgg_register_action('stars/rate', "{$root}/actions/stars/rate.php");
	elgg_register_action('stars/delete', "{$root}/actions/stars/delete.php");

	// Stars JS and CSS
	elgg_extend_view('css/elgg', 'stars/css');

	elgg_define_js('jquery.rateit', array(
		'src' => '/mod/elgg_stars/vendors/rateit/jquery.rateit.min.js',
		'deps' => array('jquery'),
	));

	elgg_require_js('stars/init');

	// Setup menus
	elgg_register_plugin_hook_handler('register', 'menu:entity', 'elgg_stars_menu_setup');
	elgg_register_plugin_hook_handler('register', 'menu:annotation' , 'elgg_stars_annotation_menu_setup');

	// Permissions
	elgg_register_plugin_hook_handler('permissions_check:annotate', 'all', 'elgg_stars_can_annotate');

	// Add an annotation view for registered ratings
	elgg_register_plugin_hook_handler('view', 'annotation/default', 'elgg_stars_annotation_view_replacement');

	// Extend the sidebar with the ratings module
	elgg_register_plugin_hook_handler('view', 'page/elements/comments', 'elgg_stars_comments_rating_addon', 900);
	elgg_register_plugin_hook_handler('comments', 'all', 'elgg_stars_comments_rating_addon', 900);
	
	// Get rating criteria that applies to an entity
	elgg_register_plugin_hook_handler('criteria', 'stars', 'elgg_stars_rating_criteria_hook');

	// Setup widgets
	elgg_register_widget_type('highestrating', elgg_echo('stars:widget:highestrating'), elgg_echo('stars:widget:highestrating:desc'), array('all'), false);

}