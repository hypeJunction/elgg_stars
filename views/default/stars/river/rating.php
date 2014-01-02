<?php

$item = elgg_extract('item', $vars);

$subject = $item->getSubjectEntity();
$object = $item->getObjectEntity();
$target = $object->getContainerEntity();

$subject_link = elgg_view('output/url', array(
	'href' => $subject->getURL(),
	'text' => $subject->name,
	'class' => 'elgg-river-subject',
	'is_trusted' => true,
));

$object_text = $object->title ? $object->title : $object->name;
$object_link = elgg_view('output/url', array(
	'href' => $object->getURL(),
	'text' => elgg_get_excerpt($object_text, 100),
	'class' => 'elgg-river-object',
	'is_trusted' => true,
));

$rating = $vars['item']->getAnnotation();
$rating_name = elgg_echo("rating_name:$rating->name");

$stars = elgg_view('output/stars', array(
		'value' => $rating->value
	));

$summary = elgg_echo('stars:river:summary', array(
	$subject_link, $object_link, $rating_name, $rating->value
));

echo elgg_view('river/elements/layout', array(
	'item' => $vars['item'],
	'summary' => $summary,
	'attachments' => $stars
));
