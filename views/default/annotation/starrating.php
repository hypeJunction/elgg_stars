<?php
/**
 * starrating annotation view
 * @uses $vars['annotation']
 */

$annotation = $vars['annotation'];

$owner = $annotation->owner_guid ? get_entity((int) $annotation->owner_guid) : null;
if (!$owner) {
	return true;
}

$icon = elgg_view_entity_icon($owner, 'tiny');
$owner_link = elgg_view('output/url', [
	'href' => $owner->getURL(),
	'text' => $owner->name,
]);

$menu = elgg_view_menu('annotation', [
	'annotation' => $annotation,
	'sort_by' => 'priority',
	'class' => 'elgg-menu-hz float-alt',
]);

$rating = '<label>' . elgg_echo("rating_name:$annotation->name") . '</label>';
$rating .= elgg_view('output/stars', ['value' => $annotation->value]);

$friendlytime = elgg_view_friendly_time($annotation->time_created);

$body = '<div class="mbn">'
	. $owner_link
	. $rating
	. "<span class=\"elgg-subtext\">{$friendlytime}</span>"
	. '</div>';

echo elgg_view_image_block($icon, $body, [
	'image_alt' => $menu
]);
