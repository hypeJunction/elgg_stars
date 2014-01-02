Elgg Stars
==========

Star rating for Elgg

## Usage

### Adding a rating form

1. First, either define your rating criteria using the plugin setting, or by
calling ```elgg_stars_register_rating_annotation_name($criteria1);```
for each rating criteria you are intending to use.

2. Add a form to rate an existing entity:
```
echo elgg_view_form('stars/rate', array(), array(
	'entity' => $entity,
	'annotation_names' => array($criteria1, $criteria2)
));
```
This will display a form with 2 star rating modules.
User input will be processed in real time. No need for any further coding
on your side.

### Adding a static star input to a form

To add a star input to your form, simple call:
```
echo elgg_view('input/stars', array(
	'name' => 'stars'
));
```

In your action, you would then use standard Elgg API:
```
$stars = get_input('stars');
```
See ```input/stars``` for a list of additional parameters, including minimum
and maximum values;


### Getting rating values

To get a value for a single or multiple criteria, you can call
```
$ratings = elgg_stars_get_entity_rating_values($entity, array($criteria1, $criteria2);
```

You can leave the second parameter empty, to get a total value for all
registered rating criteria.

Note that this function will return an associative array, where:
```$ratings['value']``` is an actual average value of all ratings.
Ratings are not weighed, so if you are planning to use multiple rating scales,
you need to add your own weighing algorithms.


### Displaying star ratings without user input

To display any value on a star rating scale:

```
echo elgg_view('output/stars', array(
	'value' => $my_value
));
```

You can also specify, 'min', 'max', and 'step' parameters to configure the
scale.


## Credits / Acknowledgements

### RateIt - a jQuery star rating plugin http://rateit.codeplex.com/
Fast, Progressive enhancement, touch support, customizable
(just swap out the images, or change some CSS), Unobtrusive JavaScript
(using HTML5 data-* attributes), RTL support, ARIA & keyboard support.
Use as many stars as you'd like, and also any step size.


## Notes

* This is a framework-agnostic adaptation of discontinued hypeStarRating

