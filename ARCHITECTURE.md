# elgg_stars — Architecture

## Summary

**Name**: elgg_stars (Stars)
**Version**: 4.0.0 — migrated to Elgg 4.x on 2026-05-24
**Purpose**: Star rating widget for Elgg entities.

elgg_stars annotates Elgg objects (and any registered type/subtype pair) with
"starrating" annotations, surfaces an interactive rating widget (jQuery RateIt),
and aggregates ratings into average / count statistics. Rating annotations are
load-bearing for downstream consumers (bodyology_library, bodyology_feedback).

## Directory Structure

```
elgg_stars/
├── elgg-plugin.php           # Declarative plugin manifest (plugin, bootstrap, actions, widgets, hooks, settings, upgrades)
├── composer.json             # Plugin metadata; elgg/elgg ^4.0, php >=7.4
├── classes/
│   └── ElggStars/
│       ├── Bootstrap.php     # \Elgg\PluginBootstrap — load(), init(), activate()
│       ├── Hooks.php         # \Elgg\Hook handlers (permissions, view replacement, criteria, comments addon)
│       ├── Menus.php         # menu:entity / menu:annotation registrations
│       └── Upgrades/
│           └── EncodeSettingsAsJson.php   # AsynchronousUpgrade — JSON re-encode legacy settings
├── lib/
│   └── functions.php         # Helpers (settings, annotation-name registry, rating math, JSON encode/decode)
├── actions/
│   ├── settings/elgg_stars.php  # Admin settings save (ResponseBuilder)
│   └── stars/
│       ├── rate.php          # Cast a vote — $entity->annotate() + elgg_create_river_item()
│       └── delete.php        # Delete a rating annotation
├── views/default/
│   ├── annotation/starrating.php
│   ├── forms/stars/rate.php
│   ├── input/stars.php       # Interactive rating widget (jQuery RateIt)
│   ├── output/stars.php      # Read-only display
│   ├── plugins/elgg_stars/settings.php  # Admin settings UI
│   ├── stars/{css,ratings}.php
│   ├── stars/river/rating.php
│   └── widgets/highestrating/{content,edit}.php
├── languages/
├── docker/
│   ├── elgg3/                # 3.x verification stack
│   └── elgg4/                # 4.x verification stack
└── vendors/
    └── rateit/               # jQuery RateIt JS library (bundled)
```

## Registered Hooks / Events

Declared in `elgg-plugin.php` under the `hooks` key — handlers use the Elgg 4.x
single-argument `\Elgg\Hook` signature.

| Type | Trigger | Handler | Purpose |
|------|---------|---------|---------|
| hook | `register,menu:entity` | `ElggStars\Menus::entityMenu` | Append rating widget to entity menus |
| hook | `register,menu:annotation` | `ElggStars\Menus::annotationMenu` | Delete-rating menu item |
| hook | `permissions_check:annotate,all` | `ElggStars\Hooks::canAnnotate` | Block double-voting |
| hook | `view,annotation/default` | `ElggStars\Hooks::annotationViewReplacement` | Use starrating view for rating annotations |
| hook | `view,page/elements/comments` | `ElggStars\Hooks::commentsRatingAddon` (priority 900) | Append ratings module to comments page |
| hook | `comments,all` | `ElggStars\Hooks::commentsRatingAddon` (priority 900) | Same for comments hook |
| hook | `criteria,stars` | `ElggStars\Hooks::criteria` | Resolve granular per-type/subtype criteria |

`Bootstrap::init()` additionally registers (imperatively — these can't be expressed declaratively):
- The `elgg_stars_annotation_names` registry of valid rating annotation names (from the `criteria` setting).
- `elgg_extend_view('elgg.css', 'stars/css')` and `elgg_define_js('jquery.rateit', ...)` + `elgg_require_js('stars/init')`.

## Entities, Annotations, Routes, Actions

- **Annotations**: `starrating` (default) — float value, owner = voter, access = container access. Configurable via plugin setting `criteria` (space-separated additional annotation names).
- **Routes**: none registered; uses standard Elgg action endpoints.
- **Actions** (declared in `elgg-plugin.php`):
  - `elgg_stars/settings/save` (admin)
  - `stars/rate`
  - `stars/delete`
- **Widgets**: `highestrating` — top-rated entities listing (context: `all`).
- **JS**: AMD modules `stars/init` and `stars/lib`, plus vendored `jquery.rateit`.

## Plugin Settings

Declared in `elgg-plugin.php` (defaults: `min_value=0`, `max_value=5`, `step=1`).
`Bootstrap::activate()` writes them on first activation as a safety net.

| Setting | Type | Storage | Notes |
|---------|------|---------|-------|
| `min_value` | int | scalar | default 0 |
| `max_value` | int | scalar | default 5 |
| `step` | int | scalar | default 1 |
| `criteria` | string | scalar | space-separated annotation names |
| `extend_menu` | bool | scalar | append widget to entity menu |
| `extend_comments` | bool | scalar | append ratings module to comments |
| `type_subtype_pairs` | array | **JSON** (was PHP-serialized in pre-3.0) | rateable type:subtype list |
| `granular_criteria` | array | **JSON** (was PHP-serialized in pre-3.0) | per-type/subtype criterion lists |

## Dependencies

- `elgg/elgg`: `^4.0`
- `composer/installers`: `^2.0`
- PHP: `>=7.4` (Docker image targets 7.4)
- No plugin-level dependencies.

## Seeding

No seeder. elgg_stars does not own any entity types or subtypes — it produces
annotations on entities owned by other plugins. Seeding is the responsibility
of those plugins (bodyology_library, bodyology_feedback). A future test suite
should generate annotations against fixture entities in PHPUnit setup rather
than via `database:seed`.

## Upgrade Batches

| Class | Version | Purpose |
|-------|---------|---------|
| `\ElggStars\Upgrades\EncodeSettingsAsJson` | 2026052400 | Re-encodes legacy PHP-serialized `type_subtype_pairs` and `granular_criteria` plugin settings as JSON, so the runtime serialize-fallback in `elgg_stars_decode_setting()` can be removed in a later major. Idempotent — skips when settings are already valid JSON. |

## Migration Notes (3.x → 4.x)

Applied 2026-05-24 by skills/elgg-migrate (rules/3x-to-4x/manifest.json).

Automated rule output:
- `update-manifest-version`: `manifest.xml` bumped 3.0 → 4.0 (later deleted), `composer.json` `elgg/elgg` bumped to `^4.0`.
- `amd-removed-apis-4x`: `views/default/js/stars/lib.js` — dropped `elgg/init` require, `elgg.echo()` → `i18n.echo()`, removed `elgg.provide()`.
- `elgg-instanceof-4x`: `views/default/widgets/highestrating/content.php` — `elgg_instanceof()` → native `instanceof`.
- `jquery-deprecated-apis-4x`: `vendors/rateit/jquery.rateit.js` — `.bind()/.unbind()` → `.on()/.off()`, `$.parseJSON` → `JSON.parse`, `$.isArray` → `Array.isArray`.

Manual fixes (carried-forward residuals from 2→3):
- **`start.php` → `elgg-plugin.php` + Bootstrap**: introduced `\ElggStars\Bootstrap` extending `\Elgg\PluginBootstrap`. `init()` registers the annotation-name registry, extends CSS, and defines `jquery.rateit` JS. `activate()` writes default min/max/step settings.
- **`activate.php` removed**: logic moved to `Bootstrap::activate()`.
- **`manifest.xml` removed**: Elgg 4.x reads metadata from `composer.json` and `elgg-plugin.php`.
- **Hook signatures**: all handlers in `lib/hooks.php` migrated to static methods on `\ElggStars\Hooks` / `\ElggStars\Menus`, using single-argument `\Elgg\Hook` (gets `$hook->getValue()`, `$hook->getParam(...)`, `$hook->getParams()`).
- **`elgg_get_entities_from_annotation_calculation()`** (deprecated in 3.x) → `elgg_get_entities()` with `annotation_name_value_pairs` + `OrderByClause` closure invoking `joinAnnotationTable()` + `AVG(value) DESC`, plus `group_by` on `e.guid`.

Additional 4.x API fixes:
- `forward(REFERER)` removed in 4.x — actions now `return elgg_ok_response()` / `elgg_error_response()` (ResponseBuilder pattern).
- `create_annotation()` → `$entity->annotate()` in `actions/stars/rate.php`.
- `ElggPlugin::getManifest()->getName()` → `$plugin->getDisplayName()` in admin settings save action.
- `elgg_load_js()` removed in 4.x — replaced with `elgg_load_external_file('js', $name)` + `elgg_require_js()`.
- `elgg_instanceof()` removed in 4.x — replaced with native PHP `instanceof` in `lib/functions.php`, `views/default/forms/stars/rate.php`, `views/default/stars/ratings.php`.

Gate results (all PASS):
- PostMigrationVerifier (no 4.x→5.x+ API leakage)
- SecuritySweep (clean)
- Docker activation in elgg4 (plugin activates, isActive=true)
- Homepage render (7338 bytes), login render (7338 bytes), no PHP Fatal/Error
- Simplecache CSS render (74140 bytes)
- PHP_CodeSniffer (Elgg standard) — 0 errors after `phpcbf`
- PHP syntax — clean

### Known carry-forwards (deferred to 4→5)

- Hook callbacks use 4.x `\Elgg\Hook` typed signature; converts to `\Elgg\Event` in 5.x (the `\Elgg\Hook`/`\Elgg\Event` split lands in 5.x).
- `elgg_get_plugin_setting` callsites are already lowercase (`elgg_stars`) — no action required.
- jQuery vendored RateIt may need further updates if 5.x bumps jQuery beyond 3.5.x.

## Data Preservation Notes

- The `starrating` annotation subtype is preserved unchanged across the 3.x → 4.x boundary. Annotation storage shape (name, value, owner_guid, access_id) is identical.
- `bodyology_library/lib/events.php::bodyology_library_update_rating()` and `bodyology_feedback` continue to operate unchanged.
- Plugin-setting array storage shape unchanged from 3.x (JSON). The upgrade batch (`EncodeSettingsAsJson`) remains registered for any 2.x site that hasn't yet run it.
