# elgg_stars — Architecture

## Summary

**Name**: elgg_stars (Stars)
**Version**: 5.0.0 — migrated to Elgg 5.x on 2026-05-24
**Purpose**: Star rating widget for Elgg entities.

elgg_stars annotates Elgg objects (and any registered type/subtype pair) with
"starrating" annotations, surfaces an interactive rating widget (jQuery RateIt),
and aggregates ratings into average / count statistics. Rating annotations are
load-bearing for downstream consumers (bodyology_library, bodyology_feedback).

## Directory Structure

```
elgg_stars/
├── elgg-plugin.php           # Declarative plugin manifest (plugin, bootstrap, actions, widgets, events, settings, upgrades)
├── composer.json             # Plugin metadata; elgg/elgg ~5.1.0, php >=8.1
├── classes/
│   └── ElggStars/
│       ├── Bootstrap.php     # \Elgg\PluginBootstrap — load(), init(), activate()
│       ├── Events.php        # \Elgg\Event handlers (permissions, view replacement, criteria, comments addon) — renamed from Hooks.php
│       ├── Menus.php         # menu:entity / menu:annotation registrations (\Elgg\Event signature)
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
│   └── en.php                # return [...] format (Elgg 5.x auto-discovers; add_translation() removed)
├── docker/
│   ├── elgg3/                # 3.x verification stack
│   ├── elgg4/                # 4.x verification stack
│   └── elgg5/                # 5.x verification stack (PHP 8.2, MySQL 8.0)
└── vendors/
    └── rateit/               # jQuery RateIt JS library (bundled)
```

## Registered Events

Declared in `elgg-plugin.php` under the `events` key — handlers use the Elgg 5.x
single-argument `\Elgg\Event` signature. (The 4.x `'hooks'` key was merged into
`'events'` in 5.x — same shape, same semantics, unified type.)

| Trigger | Handler | Purpose |
|---------|---------|---------|
| `register,menu:entity` | `ElggStars\Menus::entityMenu` | Append rating widget to entity menus |
| `register,menu:annotation` | `ElggStars\Menus::annotationMenu` | Delete-rating menu item |
| `permissions_check:annotate,all` | `ElggStars\Events::canAnnotate` | Block double-voting |
| `view,annotation/default` | `ElggStars\Events::annotationViewReplacement` | Use starrating view for rating annotations |
| `view,page/elements/comments` | `ElggStars\Events::commentsRatingAddon` (priority 900) | Append ratings module to comments page |
| `comments,all` | `ElggStars\Events::commentsRatingAddon` (priority 900) | Same for comments event |
| `criteria,stars` | `ElggStars\Events::criteria` | Resolve granular per-type/subtype criteria |

Handler references in `elgg-plugin.php` are string literals
(`'ElggStars\\Events::canAnnotate'`) rather than `Events::class . '::canAnnotate'`
expressions — this keeps the declarative manifest fully serializable across the
plugin-config cache. Iron Law 5 (no closures in elgg-plugin.php) still applies.

`Bootstrap::init()` additionally registers (imperatively — these can't be expressed declaratively):
- The `elgg_stars_annotation_names` registry of valid rating annotation names (from the `criteria` setting).
- `elgg_extend_view('elgg.css', 'stars/css')` and `elgg_define_js('jquery.rateit', ...)` + `elgg_require_js('stars/init')`.

Note: `elgg_define_js()` / `elgg_require_js()` / `elgg_load_external_file()` are still valid in 5.x and removed in 6.x — defer ES-module migration to the 5→6 step.

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

- `elgg/elgg`: `~5.1.0`
- `composer/installers`: `^2.0`
- `ext-intl`: `*` (required by Elgg 5.x)
- PHP: `>=8.1` (Docker image targets 8.2)
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

## Migration Notes (4.x → 5.x)

Applied 2026-05-24 by skills/elgg-migrate (rules/4x-to-5x/manifest.json).

Automated rule output:
- `update-manifest-version-5x`: `composer.json` `elgg/elgg` bumped to `~5.1.0`, `php` to `>=8.1`, added `ext-intl: *` (required by Elgg 5.x).

Manual fixes (Elgg 5.x hooks-events merge):
- **`elgg-plugin.php` `'hooks'` → `'events'`**: keys merged into a single `events` array; `version` bumped to `5.0.0`. Handler references switched from `Class::class . '::method'` PHP expressions to string literals (cross-plugin learning — avoids any chance of the const expression mis-rendering across the plugin-config cache).
- **`classes/ElggStars/Hooks.php` → `Events.php`** (`git mv`): file renamed; class renamed `Hooks` → `Events`; `\Elgg\Hook` → `\Elgg\Event` type hints; parameter renamed `$hook` → `$event`. Same `getValue` / `getParam` / `getParams` / `getName` surface — the 5.x `Event` object preserves the 4.x `Hook` API.
- **`classes/ElggStars/Menus.php`**: same `\Elgg\Hook` → `\Elgg\Event` migration; menu factory + filter logic unchanged.
- **`lib/functions.php`**: `elgg_trigger_plugin_hook('criteria', 'stars', ...)` → `elgg_trigger_event_results('criteria', 'stars', ...)`. Same return semantics.
- **`languages/en.php`**: `add_translation('en', $english)` removed in 5.x; converted to `return $english_array_literal` (Elgg 5.x auto-discovers the returned array). This was caught at install time — the previous-version skill missed it.

Gate results (all PASS):
- PostMigrationVerifier (no 5.x→6.x+ API leakage)
- SecuritySweep (clean)
- Docker activation in elgg5 (plugin activates, listed in activation log as `+ elgg_stars`)
- Homepage render (8906 bytes), login render (8993 bytes), no PHP Fatal/Error
- Simplecache CSS render (75716 bytes)
- PHP_CodeSniffer (Elgg standard) — clean
- PHP syntax — clean (excl. vendor)

### Known carry-forwards (deferred to 5→6)

- `Bootstrap::init()` still uses `elgg_define_js('jquery.rateit', ...)` + `elgg_require_js('stars/init')`, and `views/default/output/stars.php` still uses `elgg_load_external_file('js', 'jquery.rateit')`. These are valid in 5.x; the 5→6 migration must convert to `elgg_register_esm()` / `elgg_import_esm()` and rewrite `views/default/js/stars/*.js` from AMD to ES modules.
- `vendors/rateit/jquery.rateit.js` may need further updates if 6.x bumps jQuery.
- No `Seeder` subclass — documented above. Reconsider in 5→6 if the bodyology test suite needs annotation fixtures from this plugin.
- No PHPUnit suite on this branch. A test scaffold exists on the side branch `tests/elgg_stars-coverage` (off `migrate/elgg-3.x`); bead `7tkhb` tracks forward-merging it through the version chain.

## Data Preservation Notes

- The `starrating` annotation subtype is preserved unchanged across the 4.x → 5.x boundary. Annotation storage shape (name, value, owner_guid, access_id) is identical.
- `bodyology_library/lib/events.php::bodyology_library_update_rating()` and `bodyology_feedback` continue to operate unchanged.
- Plugin-setting array storage shape unchanged from 4.x (JSON). The upgrade batch (`EncodeSettingsAsJson`) remains registered for any 2.x site that hasn't yet run it.
