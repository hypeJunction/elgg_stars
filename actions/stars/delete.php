<?php

$annotation_id = get_input('annotation_id');
$annotation = elgg_get_annotation_from_id($annotation_id);

if ($annotation && $annotation->delete()) {
	system_message(elgg_echo('stars:delete:success'));
	forward(REFERER);
}

register_error(elgg_echo('stars:delete:error'));
forward(REFERER);