# Changelog

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
