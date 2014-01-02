<?php
/**
 * starrating annotation view
 * @uses $vars['annotation']
 */

$annotation = $vars['annotation'];

$owner = get_entity($annotation->owner_guid);
if (!$owner) {
	return true;
}

$icon = elgg_view_entity_icon($owner, 'tiny');
$owner_link = elgg_view('output/url', array(
	'href' => $owner->getURL(),
	'text' => $owner->name,
));

$menu = elgg_view_menu('annotation', array(
	'annotation' => $annotation,
	'sort_by' => 'priority',
	'class' => 'elgg-menu-hz float-alt',
));

$rating = '<label>' . elgg_echo("rating_name:$annotation->name") . '</label>';
$rating .= elgg_view("output/stars", array("value" => $annotation->value));

$friendlytime = elgg_view_friendly_time($annotation->time_created);

$body = <<<HTML
<div class="mbn">
	$owner_link
	$rating
	<span class="elgg-subtext">
		$friendlytime
	</span>
</div>
HTML;

echo elgg_view_image_block($icon, $body, array(
	'image_alt' => $menu
));