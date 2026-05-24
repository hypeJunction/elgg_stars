<?php

$params = (array) get_input('params');
$plugin_id = (string) get_input('plugin_id');
$plugin = elgg_get_plugin_from_id($plugin_id);

if (!($plugin instanceof ElggPlugin)) {
	return elgg_error_response(elgg_echo('plugins:settings:save:fail', [$plugin_id]));
}

$plugin_name = $plugin->getDisplayName();

foreach ($params as $k => $v) {
	if (is_array($v)) {
		$v = elgg_stars_encode_setting($v);
	}

	if (!$plugin->setSetting($k, $v)) {
		return elgg_error_response(elgg_echo('plugins:settings:save:fail', [$plugin_name]));
	}
}

return elgg_ok_response('', elgg_echo('plugins:settings:save:ok', [$plugin_name]));
