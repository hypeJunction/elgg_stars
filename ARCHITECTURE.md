# elgg_stars — Architecture

## Summary

**Name**: elgg_stars (Stars)
**Version**: 6.0.0 — migrated to Elgg 6.x on 2026-05-24
**Purpose**: Star rating widget for Elgg entities.

elgg_stars annotates Elgg objects (and any registered type/subtype pair) with
"starrating" annotations, surfaces an interactive rating widget (jQuery RateIt),
and aggregates ratings into average / count statistics. Rating annotations are
load-bearing for downstream consumers (bodyology_library, bodyology_feedback).

## Directory Structure

```
elgg_stars/
├── elgg-plugin.php           # Declarative plugin manifest (plugin, bootstrap, actions, widgets, events, settings, upgrades)
├── composer.json             # Plugin metadata; elgg/elgg ~6.1.0, php >=8.2
├── classes/
│   └── ElggStars/
│       ├── Bootstrap.php     # \Elgg\PluginBootstrap — load(), init(), activate(); registers ESM modules
│       ├── Events.php        # \Elgg\Event handlers (permissions, view replacement, criteria, comments addon)
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
│   ├── js/stars/{init,lib}.js   # ES modules — registered via elgg_register_esm()
│   └── widgets/highestrating/{content,edit}.php
├── languages/
│   └── en.php                # return [...] format (Elgg 5.x+ auto-discovers)
├── docker/
│   ├── elgg3/                # 3.x verification stack
│   ├── elgg4/                # 4.x verification stack
│   ├── elgg5/                # 5.x verification stack
│   └── elgg6/                # 6.x verification stack (PHP 8.2, MySQL 8.0, MariaDB 10.6+)
└── vendors/
    └── rateit/               # jQuery RateIt JS library (bundled, non-ESM jQuery plugin)
```

## Registered Events

Declared in `elgg-plugin.php` under the `events` key — handlers use the Elgg 5.x+
single-argument `\Elgg\Event` signature.

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
- `elgg_extend_view('elgg.css', 'stars/css')`.
- `elgg_register_external_file('js', 'jquery.rateit', …)` — the third-party
  jQuery rateit plugin (a non-ESM jQuery plugin) loaded as a regular `<script>`
  tag wherever `elgg_load_external_file('js', 'jquery.rateit')` is called.
- `elgg_register_esm('stars/lib', …)` and `elgg_register_esm('stars/init', …)` —
  the two stars ES modules (the files use a `.js` extension, so they need
  explicit importmap entries; `.mjs` files auto-register from their view name).
- `elgg_import_esm('stars/init')` — pulls the init module on every page so the
  rateit widget binds on load and re-binds after AJAX responses.

## Entities, Annotations, Routes, Actions

- **Annotations**: `starrating` (default) — float value, owner = voter, access = container access. Configurable via plugin setting `criteria` (space-separated additional annotation names).
- **Routes**: none registered; uses standard Elgg action endpoints.
- **Actions** (declared in `elgg-plugin.php`):
  - `elgg_stars/settings/save` (admin)
  - `stars/rate`
  - `stars/delete`
- **Widgets**: `highestrating` — top-rated entities listing (context: `all`).
- **JS**: ES modules `stars/init` (auto-imported) and `stars/lib` (imported by init), plus vendored non-ESM `jquery.rateit`.

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

- `elgg/elgg`: `~6.1.0`
- `composer/installers`: `^2.0`
- `ext-intl`: `*` (required by Elgg 6.x)
- PHP: `>=8.2` (Docker image targets 8.2)
- MySQL `>=8.0` or MariaDB `>=10.6` (Elgg 6.x baseline)
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

## Migration Notes (5.x → 6.x)

Applied 2026-05-24 by skills/elgg-migrate (rules/5x-to-6x/manifest.json).

Automated rule output:
- `add-docblocks`: skipped — every function/method/property already documented from prior major.

Manual fixes:
- **`composer.json`**: `elgg/elgg` `~5.1.0` → `~6.1.0`, `php` `>=8.1` → `>=8.2`. `ext-intl` retained.
- **`elgg-plugin.php`**: `version` `5.0.0` → `6.0.0`. Everything else unchanged — the `'events'` key, handler signatures, and settings are 6.x-compatible without edits.
- **AMD → ES modules** (the single load-bearing 5→6 change for this plugin):
  - `classes/ElggStars/Bootstrap.php::init()`:
    - `elgg_define_js('jquery.rateit', [...])` → `elgg_register_external_file('js', 'jquery.rateit', elgg_normalize_url(...))`. The rateit plugin is a non-ESM jQuery plugin, so it stays a regular `<script>` tag served via the external-files registry. `elgg_register_external_file()` is still available in 6.x (signature changes to `void` return only in 7.x).
    - `elgg_require_js('stars/init')` → `elgg_register_esm('stars/lib', elgg_get_simplecache_url('js/stars/lib.js'))` + `elgg_register_esm('stars/init', elgg_get_simplecache_url('js/stars/init.js'))` + `elgg_import_esm('stars/init')`. Both modules need importmap registration because they keep `.js` extensions (Elgg 6.x auto-registers `.mjs` files but not `.js`).
  - `views/default/output/stars.php`:
    - `elgg_require_js('stars/init')` → `elgg_import_esm('stars/init')`.
    - `elgg_load_external_file('js', 'jquery.rateit')` kept as-is — the function is still available in 6.x and is the right call to lazily emit the script only on pages that render the widget.
  - `views/default/js/stars/init.js`: AMD `define(function(require) { ... })` → ES module with `import 'jquery'` (side-effect import for `$`) and `import { init } from 'stars/lib'`. The `if ($('.rateit').length)` bootstrap and the `$(document).ajaxSuccess(...)` rebind are now executed at module load time (ESM modules execute their top-level body synchronously after dependencies resolve).
  - `views/default/js/stars/lib.js`: AMD `define(['elgg', 'jquery', 'jquery.rateit'], function(...))` → ES module with `import 'jquery'`, `import Ajax from 'elgg/Ajax'`, `import i18n from 'elgg/i18n'`, and an exported `init()` function. `elgg.action(...)` (the 4.x global) was replaced with `new Ajax().action(...)` since `elgg.action` is not part of the ESM `elgg` module. `i18n.echo(...)` is now imported explicitly (was a global in AMD).

Gate results (all PASS):
- PostMigrationVerifier (no 6.x→7.x+ API leakage)
- SecuritySweep (clean)
- DependencyAudit (skipped — no `composer.lock`; matches prior majors)
- PHP syntax (excl. vendor/vendors/tests)
- Docker activation in elgg6 (plugin activates without throwing; importmap contains `stars/init` and `stars/lib` entries)
- Homepage render (13886 bytes), login render (13981 bytes), no PHP Fatal/Error in Apache log
- PHP_CodeSniffer (Elgg standard) — clean (one trailing-blank-line violation in `views/default/js/stars/init.js` fixed)

### Known carry-forwards (deferred to 6→7)

- `vendors/rateit/jquery.rateit.{js,min.js}` is still a non-ESM jQuery plugin from the rateit upstream. Elgg 7.x may bump jQuery or further restrict global scripts; revisit the third-party dep then (potentially port to an ESM-friendly fork or replace).
- `elgg_register_external_file()` returns `void` in 7.x (was `bool` in 6.x) — the call site in `Bootstrap::init()` doesn't use the return value, so the change is mechanical but will need to be re-verified.
- No `Seeder` subclass — documented above. No change expected in 6→7.
- No PHPUnit suite on this branch. A test scaffold exists on the side branch `tests/elgg_stars-coverage` (off `migrate/elgg-3.x`); bead `7tkhb` tracks forward-merging it through the version chain.

## Data Preservation Notes

- The `starrating` annotation subtype is preserved unchanged across the 5.x → 6.x boundary. Annotation storage shape (name, value, owner_guid, access_id) is identical. The 6.x annotation join-alias change (`n_table` → `a_table`) does not affect this plugin because all annotation queries go through `elgg_get_annotations()` (no raw SQL or WHERE-closure use of `n_table`).
- The Elgg 6.x annotation `enabled` column removal does not affect this plugin — it never called `enable()`/`disable()` on annotations or used `elgg_disable_annotations()`/`elgg_enable_annotations()`.
- Entity icon coordinate / `icontime` metadata removal in 6.x does not affect this plugin — it owns no entity types and does not handle entity icons.
- `bodyology_library/lib/events.php::bodyology_library_update_rating()` and `bodyology_feedback` continue to operate unchanged.
- Plugin-setting array storage shape unchanged from 5.x (JSON). The upgrade batch (`EncodeSettingsAsJson`) remains registered for any 2.x/3.x site that hasn't yet run it.
