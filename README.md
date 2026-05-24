# Stars

![Elgg 4.x](https://img.shields.io/badge/Elgg-4.x-orange.svg?style=flat-square)

Star rating widget for any Elgg entity — annotate, aggregate, and display.

## Features

- Drop-in interactive star rating widget (jQuery RateIt) for entities of any type/subtype.
- Multi-criterion ratings — register any number of annotation names.
- Aggregated stats: per-entity count, sum, and average.
- Admin UI to enable per-type/subtype, per-criterion rating.
- River entries for new ratings.
- "Highest Rated" widget for profile, group, and dashboard contexts.

## Installation

**Via Composer (recommended):**

```bash
composer require hypejunction/elgg_stars
```

**Manual:**

Download the zip, extract into your Elgg `mod/` directory, and activate in the admin panel.

## Usage

### Render a rating form

```php
echo elgg_view_form('stars/rate', [], [
    'entity' => $entity,
    'annotation_names' => ['starrating'],
]);
```

### Display a read-only rating

```php
echo elgg_view('output/stars', ['value' => $entity_rating]);
```

### Programmatic rating values

```php
$rating = elgg_stars_get_entity_rating_values($entity, ['starrating']);
// $rating = ['count' => N, 'sum' => N, 'value' => avg, 'min' => 0, 'max' => 5, 'step' => 1]
```

## Compatibility

| Plugin version | Elgg version |
|---|---|
| current | 4.x |
| 3.x | 3.x |

## Credits

- [RateIt](http://rateit.codeplex.com/) — jQuery star rating plugin (bundled in `vendors/rateit/`).

## License

GPL-2.0-or-later
