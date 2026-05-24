<?php

use ElggStars\Bootstrap;
use ElggStars\Upgrades\EncodeSettingsAsJson;

return [
	'plugin' => [
		'name' => 'Stars',
		'version' => '5.0.0',
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
	'events' => [
		'register' => [
			'menu:entity' => [
				'ElggStars\Menus::entityMenu' => [],
			],
			'menu:annotation' => [
				'ElggStars\Menus::annotationMenu' => [],
			],
		],
		'permissions_check:annotate' => [
			'all' => [
				'ElggStars\Events::canAnnotate' => [],
			],
		],
		'view' => [
			'annotation/default' => [
				'ElggStars\Events::annotationViewReplacement' => [],
			],
			'page/elements/comments' => [
				'ElggStars\Events::commentsRatingAddon' => ['priority' => 900],
			],
		],
		'comments' => [
			'all' => [
				'ElggStars\Events::commentsRatingAddon' => ['priority' => 900],
			],
		],
		'criteria' => [
			'stars' => [
				'ElggStars\Events::criteria' => [],
			],
		],
	],
	'upgrades' => [
		EncodeSettingsAsJson::class,
	],
];
