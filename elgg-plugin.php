<?php

use ElggStars\Bootstrap;
use ElggStars\Hooks;
use ElggStars\Menus;
use ElggStars\Upgrades\EncodeSettingsAsJson;

return [
	'plugin' => [
		'name' => 'Stars',
		'version' => '4.0.0',
	],
	'bootstrap' => Bootstrap::class,
	'settings' => [
		'min_value' => 0,
		'max_value' => 5,
		'step' => 1,
	],
	'actions' => [
		'elgg_stars/settings/save' => [
			'access' => 'admin',
		],
		'stars/rate' => [],
		'stars/delete' => [],
	],
	'widgets' => [
		'highestrating' => [
			'context' => ['all'],
			'multiple' => false,
		],
	],
	'hooks' => [
		'register' => [
			'menu:entity' => [
				Menus::class . '::entityMenu' => [],
			],
			'menu:annotation' => [
				Menus::class . '::annotationMenu' => [],
			],
		],
		'permissions_check:annotate' => [
			'all' => [
				Hooks::class . '::canAnnotate' => [],
			],
		],
		'view' => [
			'annotation/default' => [
				Hooks::class . '::annotationViewReplacement' => [],
			],
			'page/elements/comments' => [
				Hooks::class . '::commentsRatingAddon' => ['priority' => 900],
			],
		],
		'comments' => [
			'all' => [
				Hooks::class . '::commentsRatingAddon' => ['priority' => 900],
			],
		],
		'criteria' => [
			'stars' => [
				Hooks::class . '::criteria' => [],
			],
		],
	],
	'upgrades' => [
		EncodeSettingsAsJson::class,
	],
];
