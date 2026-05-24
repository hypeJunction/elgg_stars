# elgg_stars — Architecture

## Summary

**Name**: elgg_stars (Stars)
**Version**: 7.0.0 — migrated to Elgg 7.x on 2026-05-24
**Purpose**: Star rating widget for Elgg entities.

elgg_stars annotates Elgg objects (and any registered type/subtype pair) with
"starrating" annotations, surfaces an interactive rating widget (jQuery RateIt),
and aggregates ratings into average / count statistics. Rating annotations are
load-bearing for downstream consumers (bodyology_library, bodyology_feedback).

## Directory Structure

```
elgg_stars/
├── elgg-plugin.php           # Declarative plugin manifest (plugin, bootstrap, actions, widgets, events, settings, upgrades)
├── composer.json             # Plugin metadata; elgg/elgg ~7.0.0, php >=8.3 (minimum-stability:dev, asset-packagist repo)
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
│   ├── elgg6/                # 6.x verification stack
│   └── elgg7/                # 7.x verification stack (PHP 8.3)
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

- `elgg/elgg`: `~7.0.0`
- `composer/installers`: `^2.0`
- `ext-intl`: `*` (required by Elgg 7.x)
- PHP: `>=8.3` (Docker image targets 8.3)
- MySQL `>=8.0` or MariaDB `>=10.6` (Elgg 7.x baseline)
- composer `minimum-stability: dev` + `prefer-stable: true` (required by Elgg 7.x stability profile).
- `asset-packagist.org` composer repository (required by Elgg 7.x asset deps).
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

## Migration Notes (6.x → 7.x)

Applied 2026-05-24 by skills/elgg-migrate (rules/6x-to-7x/manifest.json).

Automated rule output:
- `composer-stability-settings-7x`: PASS — added `minimum-stability: dev`, `prefer-stable: true`, and `asset-packagist.org` composer repository to `composer.json`.
- `reset-system-cache-7x`: skipped — no `elgg_reset_system_cache()` calls in the plugin.
- `add-docblocks`: skipped — every function/method/property already documented from prior majors.

LLM-guided rules surveyed (all NOT APPLICABLE — confirmed by grep):
- `001-elggobject-abstract`: no direct `new ElggObject()` instantiation. (Plugin owns no entity types.)
- `002-css-crush-removed`: no CSS Crush `$(varname)` syntax in `views/default/stars/css.php`.
- `003-cache-backends-removed`: no memcache/redis references.
- `004-mailer-laminas-to-symfony`: no `Elgg\Email\Address` / `Laminas\Mail` / `zend:message` / `emailer_*` references.
- `005-font-awesome-v7`: no `fa-*` icon usage and no `elgg_view_icon()` calls in plugin views.
- `006-notification-handler-renames`: no notification handler class references; no `elgg_register_notification_event()` / `elgg_unregister_notification_event()`.
- `007-form-action-renames`: no references to renamed core forms / actions.
- `008-response-event-changes`: no `ajax_response` / `forward` event handlers.
- `009-button-classes-removed`: no `elgg-button-special` / `elgg-button-action-done`.
- `010-group-route-changes`: no group collection route URL generation.
- `011-action-renames`: no `admin/site/flush_cache` references.
- `012-members-route-renames`: no `collection:user:user` / `search:user:user` references.
- `013-messages-parameter-rename`: no messages-recipient parameter use.
- `014-external-pages-rewrite`: no `expages` / `external_page` references.
- `015-min-password-length`: no password validation logic.
- `016-phpunit-12`: no PHPUnit suite on this branch (deferred to bead 7tkhb).
- `017-river-emittable-capability`: elgg_stars creates river items via `elgg_create_river_item()` in `actions/stars/rate.php`, but the river item's `object_guid` points to an entity *owned by a downstream consumer* (e.g. `bodyology_library` library_entry). The `river_emittable` capability must be registered on the *entity-owning plugin*, not here. Documented in `Data Preservation Notes` below.
- `018-entity-listing-limit-clamped`: no `elgg_list_entities()` calls; the `highestrating` widget uses `elgg_get_entities()` with an internally-set limit.
- `019-ckeditor-v47`: no CKEditor customization.
- `020-likes-visibility`: no likes interaction.
- `021-webservices-changes`: no webservices / REST exposure.
- `024-grid-css-extension-target`: `views/default/stars/css.php` is extended into `elgg.css` — not into `elements/grid`. Not affected.

Manual fixes:
- **`composer.json`**: `elgg/elgg` `~6.1.0` → `~7.0.0`, `php` `>=8.2` → `>=8.3`. Stability settings added by the AST rule above.
- **`elgg-plugin.php`**: `version` `6.0.0` → `7.0.0`. Everything else unchanged — the `'events'` key, handler signatures, settings, widgets, actions, and upgrades are all 7.x-compatible without edits.
- **`elgg_register_external_file()` in `Bootstrap::init()`**: signature change `bool → void` in 7.x. Call site does not use the return value, so no code change required; behaviour is identical.

Gate results (all PASS via `verify-fleet --version=elgg7 --only=elgg_stars`):
- PostMigrationVerifier (no 7.x→8.x+ API leakage) — PASS
- SecuritySweep (clean) — PASS
- PHP syntax (excl. vendor/vendors/tests) — PASS
- Docker activation in elgg7 (plugin activates without throwing) — PASS
- Homepage render (14496 bytes), login render (14591 bytes), no PHP Fatal/Error in Apache log — PASS
- PHP_CodeSniffer (Elgg standard) — PASS (clean, no fixups required)
- PHPUnit — SKIP (no test suite on this branch; tracked by bead 7tkhb)

### Known carry-forwards (deferred beyond 7.x)

- `vendors/rateit/jquery.rateit.{js,min.js}` — third-party non-ESM jQuery plugin still bundled in `vendors/`. Continues to work in 7.x via `elgg_register_external_file()` emitting a regular `<script>` tag. Long-term plan: when Elgg drops jQuery (post-7.x), replace with an ESM-friendly rating widget.
- No `Seeder` subclass — documented under "Seeding". elgg_stars produces annotations on entities owned by other plugins; it has no entity types of its own.
- No PHPUnit suite on this branch. A test scaffold exists on the side branch `tests/elgg_stars-coverage` (off `migrate/elgg-3.x`); bead `7tkhb` tracks forward-merging it through the version chain.

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

- The `starrating` annotation subtype is preserved unchanged across the 6.x → 7.x boundary (and across the full 2.x → 7.x chain). Annotation storage shape (name, value, owner_guid, access_id) is identical. No Elgg 7.x change affects annotation storage.
- The 6.x annotation join-alias change (`n_table` → `a_table`) does not affect this plugin because all annotation queries go through `elgg_get_annotations()` (no raw SQL or WHERE-closure use of `n_table`).
- The Elgg 6.x annotation `enabled` column removal does not affect this plugin — it never called `enable()`/`disable()` on annotations or used `elgg_disable_annotations()`/`elgg_enable_annotations()`.
- Entity icon coordinate / `icontime` metadata removal in 6.x does not affect this plugin — it owns no entity types and does not handle entity icons.
- `bodyology_library/lib/events.php::bodyology_library_update_rating()` and `bodyology_feedback` continue to operate unchanged on 7.x.
- Plugin-setting array storage shape unchanged from 5.x (JSON). The upgrade batch (`EncodeSettingsAsJson`) remains registered for any 2.x/3.x site that hasn't yet run it.

### Consumer-side note: river_emittable on 7.x

`actions/stars/rate.php` calls `elgg_create_river_item()` with `object_guid =
$entity->guid` where `$entity` is whatever entity is being rated — typically a
`library_entry` (bodyology_library) or `feedback` (bodyology_feedback). In
Elgg 7.x, river emission is gated by the `river_emittable` capability on the
*entity type/subtype declaration*. That capability MUST be set in
`elgg-plugin.php` on the entity-owning plugin — not on elgg_stars. If a
consumer plugin's entity is not declared `river_emittable`, ratings on it
will silently fail to produce river entries on 7.x. Consumer plugins
(bodyology_library, bodyology_feedback) need to declare this capability when
they migrate to 7.x; tracked separately under each plugin's 7.x migration bead.
