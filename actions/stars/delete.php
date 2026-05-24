<?php

$annotation_id = (int) get_input('annotation_id');
$annotation = elgg_get_annotation_from_id($annotation_id);

if ($annotation && $annotation->delete()) {
	return elgg_ok_response('', elgg_echo('stars:delete:success'));
}

return elgg_error_response(elgg_echo('stars:delete:error'));
