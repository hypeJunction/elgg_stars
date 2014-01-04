<?php

$english = array(

	'stars:settings:ranges' => 'Rating Ranges',
	'stars:settings:ranges:min_value' => 'Minimum value (default 0)',
	'stars:settings:ranges:max_value' => 'Maximum value (default 5)',
	'stars:settings:ranges:step' => 'Step (default 1)',

	'stars:settings:entity_ratings' => 'Entity Ratings',
	'stars:settings:entity_ratings:extend_menu' => 'Add rating widget to the entity menu',
	'stars:settings:entity_ratings:extend_comments' => 'Add rating widget to the comments block',
	'stars:settings:entity_ratings:criteria' => 'Comma-separated list of rating criteria',

	'stars:settings:type_subtype_pairs' => 'Rateable entity type-subtype pairs',
	'stars:settings:granular_criteria' => 'Rating criteria that applies to this type subtype pair',

	'stars:widget:highestrating' => 'Highest Rating',
	'stars:widget:highestrating:desc' => 'Displays content with the highest rating',
	'stars:widget:numbertodisplay' => 'Number of items to display',
	'stars:widget:types' => 'Rating table to display',
	'stars:widget:types:object' => 'Most rated content',
	'stars:widget:types:user' => 'Most rated users',
	'stars:widget:types:group' => 'Most rated groups',

	'stars:rate' => 'Rate',
    'stars:stats' => '%s/%s (%s votes)',
	'stars:stats:totals' => 'Overall Rating',
    'stars:saving' => 'Saving ...',
    'stars:rate:error' => 'There was a problem saving your rating',
    'stars:rate:success' => 'Your rating was successfully saved',
    'stars:rate:alreadyrated' => 'Sorry, you can\'t rate the same item twice',
	'stars:delete:success' => 'Rating was successfully removed',
	'stars:delete:error' => 'Rating could not be removed',

	'stars:ratings' => 'Ratings',

	'rating_name:starrating' => 'Rating',

	'stars:river:summary' => '%s rated %s\'s %s with %s stars',

);

add_translation("en", $english);
?>