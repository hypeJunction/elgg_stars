<?php

if (is_null(elgg_get_plugin_setting('min_value', 'elgg_stars'))) {
	elgg_set_plugin_setting('min_value', 0, 'elgg_stars');
}

if (is_null(elgg_get_plugin_setting('max_value', 'elgg_stars'))) {
	elgg_set_plugin_setting('max_value', 5, 'elgg_stars');
}

if (is_null(elgg_get_plugin_setting('step', 'elgg_stars'))) {
	elgg_set_plugin_setting('step', 1, 'elgg_stars');
}