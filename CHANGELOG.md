# Changelog

## 7.0.0 — 2026-05-24

Migrated to Elgg 7.x.

### Added
- `docker/elgg7/` — Elgg 7.x Docker infra template for migration verification (PHP 8.3).
- `composer.json` stability settings (applied by `ComposerStabilitySettings` AST rule): `minimum-stability: dev`, `prefer-stable: true`, and `asset-packagist.org` composer repository — required for Elgg 7.x.

### Changed
- `composer.json` — bumped to `elgg/elgg: ~7.0.0`, `php: >=8.3`.
- `elgg-plugin.php` — `version` bumped to `7.0.0`. Everything else unchanged — the `'events'` key, handler signatures, settings, widgets, actions, and upgrades are all 7.x-compatible without edits.

### Compatibility
- Requires Elgg 7.x, PHP 8.3+.
- `starrating` annotation subtype unchanged — downstream consumers (bodyology_library, bodyology_feedback) continue to work without changes.
- Plugin setting storage shape unchanged from 6.x (JSON).
- `elgg_register_external_file()` now returns `void` (was `bool` in 6.x). `Bootstrap::init()` does not use the return value, so the existing call is a no-op change.

### Known carry-forwards
- `vendors/rateit/jquery.rateit.{js,min.js}` — still a non-ESM jQuery plugin from upstream rateit. Elgg 7.x ships jQuery; the rateit widget continues to bind via the legacy `<script>` tag emitted by `elgg_register_external_file()`.
- No `Seeder` subclass — documented in `ARCHITECTURE.md`. elgg_stars produces annotations on entities owned by other plugins; it has no entity types of its own. The `river_emittable` capability (new in 7.x) is therefore the responsibility of the consumer plugin (the entity owner), not elgg_stars.
- No PHPUnit suite on this branch. Test scaffold lives on side branch `tests/elgg_stars-coverage`; bead `7tkhb` tracks forward-merging it through the version chain.

## 6.0.0 — 2026-05-24

Migrated to Elgg 6.x.

### Added
- `docker/elgg6/` — Elgg 6.x Docker infra template for migration verification (PHP 8.2, MySQL 8.0+ / MariaDB 10.6+).

### Changed
- `composer.json` — bumped to `elgg/elgg: ~6.1.0`, `php: >=8.2`.
- `elgg-plugin.php` — `version` bumped to `6.0.0`; everything else unchanged (declarative `events` config is forward-compatible with 6.x).
- `classes/ElggStars/Bootstrap.php::init()` — converted AMD JS registrations to ES module registrations:
  - `elgg_define_js('jquery.rateit', [...])` → `elgg_register_external_file('js', 'jquery.rateit', elgg_normalize_url(...))`. The third-party rateit plugin is a non-ESM jQuery plugin, served as a regular `<script>` tag.
  - `elgg_require_js('stars/init')` → `elgg_register_esm('stars/lib', ...)` + `elgg_register_esm('stars/init', ...)` + `elgg_import_esm('stars/init')`. Both modules need explicit importmap registration because they keep `.js` extensions.
- `views/default/output/stars.php` — `elgg_require_js('stars/init')` → `elgg_import_esm('stars/init')`; `elgg_load_external_file('js', 'jquery.rateit')` kept (still available in 6.x).
- `views/default/js/stars/init.js` — AMD `define(function(require) { ... })` → ES module with `import 'jquery'` (side-effect import for global `$`) and `import { init } from 'stars/lib'`.
- `views/default/js/stars/lib.js` — AMD `define(['elgg', 'jquery', 'jquery.rateit'], function(...))` → ES module: `import 'jquery'`, `import Ajax from 'elgg/Ajax'`, `import i18n from 'elgg/i18n'`, exported `init()`. `elgg.action(...)` (the 4.x global, unavailable in the ESM `elgg` module) replaced with `new Ajax().action(...)`.

### Removed
- `elgg_define_js()`, `elgg_require_js()` — removed in Elgg 6.0; replaced with `elgg_register_esm()` / `elgg_import_esm()` as documented above.

### Compatibility
- Requires Elgg 6.x, PHP 8.2+, MySQL 8.0+ or MariaDB 10.6+.
- `starrating` annotation subtype unchanged — downstream consumers (bodyology_library, bodyology_feedback) continue to work without changes. Elgg 6.x annotation `n_table` → `a_table` alias change does not affect this plugin (no raw SQL / no `n_table` WHERE-closure use). The annotation `enabled` column removal does not affect this plugin (no enable/disable use).
- Plugin setting storage shape unchanged from 5.x (JSON).

## 5.0.0 — 2026-05-24

Migrated to Elgg 5.x.

### Added
- `docker/elgg5/` — Elgg 5.x Docker infra template for migration verification (PHP 8.2, MySQL 8.0).
- `classes/ElggStars/Events.php` — renamed from `Hooks.php`; handlers now type-hint `\Elgg\Event` (hooks and events merged in 5.x).

### Changed
- `composer.json` — bumped to `elgg/elgg: ~5.1.0`, `php: >=8.1`; `ext-intl` added (required by Elgg 5.x).
- `elgg-plugin.php` — `version` bumped to `5.0.0`; `'hooks'` key renamed to `'events'`; handler refs converted to string literals (`'ElggStars\\Events::canAnnotate'`) so the declarative manifest stays serializable; `ElggStars\\Hooks` symbol references replaced with `ElggStars\\Events`.
- `classes/ElggStars/Menus.php` — `\Elgg\Hook` → `\Elgg\Event` type hints; parameter renamed `$hook` → `$event`. Same API surface (`getValue` / `getParam` / `getParams` / `getName`).
- `lib/functions.php` — `elgg_trigger_plugin_hook('criteria', 'stars', ...)` → `elgg_trigger_event_results('criteria', 'stars', ...)`.
- `languages/en.php` — `add_translation('en', $arr)` removed in 5.x; converted to `return [...]` format (auto-discovered by Elgg).

### Removed
- `classes/ElggStars/Hooks.php` — renamed to `Events.php` (git history preserved via `git mv`).

### Compatibility
- Requires Elgg 5.x and PHP 8.1+.
- `starrating` annotation subtype unchanged — downstream consumers (bodyology_library, bodyology_feedback) continue to work without changes.
- Plugin setting storage shape unchanged from 4.x (JSON).

## 4.0.0 — 2026-05-24

Migrated to Elgg 4.x.

### Added
- `classes/ElggStars/Bootstrap.php` — `\Elgg\PluginBootstrap` with `load()`/`init()`/`activate()` lifecycle.
- `classes/ElggStars/Menus.php` and `classes/ElggStars/Hooks.php` — converted hook handlers using the 4.x single-argument `\Elgg\Hook` signature.
- `docker/elgg4/` — Elgg 4.x Docker infra template for migration verification.

### Changed
- `composer.json` — bumped to `elgg/elgg: ^4.0`, `php: >=7.4`, `composer/installers: ^2.0`.
- `elgg-plugin.php` — now declarative: `plugin`, `bootstrap`, `actions`, `widgets`, `hooks`, `settings`, `upgrades` keys.
- `actions/stars/rate.php`, `actions/stars/delete.php`, `actions/settings/elgg_stars.php` — `forward()` removed in 4.x; returns `elgg_ok_response()` / `elgg_error_response()` (ResponseBuilder pattern).
- `actions/stars/rate.php` — `create_annotation()` → `$entity->annotate()`.
- `views/default/widgets/highestrating/content.php` — `elgg_get_entities_from_annotation_calculation()` (deprecated in 3.x) replaced with `elgg_get_entities()` + `QueryBuilder` closure on `joinAnnotationTable()` + `OrderByClause` for `AVG(value) DESC`.
- `views/default/output/stars.php` — `elgg_load_js()` removed in 4.x; replaced with `elgg_load_external_file('js', ...)` + `elgg_require_js('stars/init')`.
- `lib/functions.php`, `views/default/forms/stars/rate.php`, `views/default/stars/ratings.php` — `elgg_instanceof()` removed in 4.x; replaced with native PHP `instanceof`.
- `views/default/js/stars/lib.js` — `elgg.echo()` moved to `elgg/i18n` AMD module; `elgg/init` and `elgg.provide()` removed.
- `vendors/rateit/jquery.rateit.js` — jQuery 3.5.x compat: `.bind()/.unbind()` → `.on()/.off()`, `$.parseJSON` → `JSON.parse`, `$.isArray` → `Array.isArray`.

### Removed
- `start.php` — procedural init moved to `Bootstrap::init()`.
- `activate.php` — default-settings init moved to `Bootstrap::activate()`.
- `manifest.xml` — Elgg 4.x reads metadata from `composer.json` and `elgg-plugin.php`.
- `lib/hooks.php` — handlers re-implemented as static methods on `\ElggStars\Hooks` and `\ElggStars\Menus`.

### Compatibility
- Requires Elgg 4.x and PHP 7.4+.
- `starrating` annotation subtype unchanged — downstream consumers (bodyology_library, bodyology_feedback) continue to work without changes.

## 3.0.0 — 2026-05-24

Migrated to Elgg 3.x.

### Added
- `composer.json` with `elgg/elgg: ^3.0` and PSR-4 autoload for `ElggStars\\`.
- `elgg-plugin.php` registering the JSON re-encode upgrade.
- `ElggStars\Upgrades\EncodeSettingsAsJson` — `\Elgg\Upgrade\AsynchronousUpgrade` that
  re-encodes legacy PHP-serialized array plugin settings as JSON.
- `ARCHITECTURE.md` documenting the plugin layout, hooks, and 2.x → 3.x migration notes.

### Changed
- `manifest.xml` now requires `elgg_release` 3.0.
- All `serialize()` / `unserialize()` of plugin settings now use
  `elgg_stars_encode_setting()` / `elgg_stars_decode_setting()` helpers. The decoder
  accepts JSON (preferred) and legacy serialized payloads (`allowed_classes => false`)
  so existing sites work until the upgrade batch runs.
- `views/default/forms/stars/rate.php` — `elgg_log()` → `elgg()->logger->warning()` (PSR-3).
- `views/default/input/stars.php` — DOM id now `uniqid('', true)`-based (was
  `md5(microtime())`).
- Coding-standards cleanup: short array syntax, `count()` for `sizeof()`, heredoc
  removed, missing docblocks added.

### Security
- Removed `unserialize()` of plugin settings without `allowed_classes` (PHP object
  injection risk). The runtime fallback uses `allowed_classes => false`; the upgrade
  batch migrates stored data to JSON.

### Compatibility
- Requires Elgg 3.x and PHP 7.0+.
- `starrating` annotation subtype unchanged — downstream consumers
  (bodyology_library, bodyology_feedback) continue to work without changes.
