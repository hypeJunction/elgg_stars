# Changelog

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
