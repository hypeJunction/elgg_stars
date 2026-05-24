# elgg_stars — Architecture

## Summary

**Name**: elgg_stars (Stars)
**Version**: 3.0.0 — migrated to Elgg 3.x on 2026-05-24 (from 1.8/1.9-era code)
**Purpose**: Star rating widget for Elgg entities.

elgg_stars annotates Elgg objects (and any registered type/subtype pair) with
"starrating" annotations, surfaces an interactive rating widget (jQuery RateIt),
and aggregates ratings into average / count statistics. Rating annotations are
load-bearing for downstream consumers (bodyology_library, bodyology_feedback).

## Directory Structure

```
elgg_stars/
├── elgg-plugin.php           # NEW in 3.x — declarative upgrades registration
├── start.php                 # Procedural init (kept for 3.x; replaced by Bootstrap in 4.x)
├── activate.php              # Sets default plugin settings (min/max/step) on activation
├── composer.json             # Plugin metadata with elgg/elgg ^3.0 constraint
├── manifest.xml              # Legacy metadata (kept for 3.x; removed in 4.x)
├── classes/
│   └── ElggStars/
│       └── Upgrades/
│           └── EncodeSettingsAsJson.php   # AsynchronousUpgrade — JSON re-encode legacy settings
├── lib/
│   ├── functions.php         # Helpers (settings, annotation-name registry, rating math, JSON encode/decode)
│   └── hooks.php             # Hook callbacks (menu, annotate permission, view replacement)
├── actions/
│   ├── settings/elgg_stars.php  # Admin settings save (now JSON-encodes array params)
│   └── stars/
│       ├── rate.php          # Cast a vote (creates annotation + river item)
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
└── vendors/
    └── rateit/               # jQuery RateIt JS library (bundled)
```

## Registered Hooks / Events

Registered procedurally in `start.php::elgg_stars_init()`:

| Type | Trigger | Handler | Purpose |
|------|---------|---------|---------|
| event | `init,system` | `elgg_stars_init` | Plugin bootstrap |
| hook | `register,menu:entity` | `elgg_stars_menu_setup` | Append rating widget to entity menus |
| hook | `register,menu:annotation` | `elgg_stars_annotation_menu_setup` | Delete-rating menu item |
| hook | `permissions_check:annotate,all` | `elgg_stars_can_annotate` | Block double-voting |
| hook | `view,annotation/default` | `elgg_stars_annotation_view_replacement` | Use starrating view for rating annotations |
| hook | `view,page/elements/comments` | `elgg_stars_comments_rating_addon` (priority 900) | Append ratings module to comments page |
| hook | `comments,all` | `elgg_stars_comments_rating_addon` (priority 900) | Same for comments hook |
| hook | `criteria,stars` | `elgg_stars_rating_criteria_hook` | Resolve granular per-type/subtype criteria |

## Entities, Annotations, Routes, Actions

- **Annotations**: `starrating` (default) — float value, owner = voter, access = container access. Configurable via plugin setting `criteria` (space-separated additional annotation names).
- **Routes**: none registered; uses standard Elgg action endpoints.
- **Actions**:
  - `elgg_stars/settings/save` (admin)
  - `stars/rate`
  - `stars/delete`
- **Widgets**: `highestrating` — top-rated entities listing.
- **JS**: AMD modules `stars/init` and `stars/lib`, plus vendored `jquery.rateit`.

## Plugin Settings

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

- `elgg/elgg`: `^3.0`
- `composer/installers`: `~1.0`
- PHP: `>=7.0` (Docker image targets 7.4)
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

## Migration Notes (2.x → 3.x)

Applied 2026-05-24 by skills/elgg-migrate (rules/2x-to-3x/manifest.json).

Automated rule output:
- `update-manifest-version`: `manifest.xml` `<version>` requirement bumped 1.9 → 3.0.
- `psr3-logging`: `elgg_log()` → `elgg()->logger->warning()` in `views/default/forms/stars/rate.php`.

Manual fixes:
- Generated `composer.json` with `elgg/elgg: ^3.0`, `composer/installers: ~1.0`, PSR-4 autoload for `ElggStars\\`, `config.allow-plugins`, `extra.elgg-plugin.id`.
- Created `elgg-plugin.php` registering the `EncodeSettingsAsJson` upgrade.
- Added `ElggStars\Upgrades\EncodeSettingsAsJson` (extends `\Elgg\Upgrade\AsynchronousUpgrade`) to migrate legacy PHP-serialized plugin settings to JSON.
- Replaced all `serialize()`/`unserialize()` of plugin settings with `elgg_stars_encode_setting()`/`elgg_stars_decode_setting()` helpers. The decoder accepts both JSON (preferred) and legacy serialize payloads (fallback with `allowed_classes => false`) so existing sites work until the upgrade batch runs.
- Replaced `md5(microtime())` (false-positive crypto-weak finding) DOM-id generator with `uniqid('', true)`.
- Coding-standards cleanup: short array syntax, `count()` instead of `sizeof()`, dropped heredoc, single-assignment-per-line, missing docblocks added.

Gate results (all PASS):
- PostMigrationVerifier (no 3.x→4.x+ API leakage)
- SecuritySweep (clean — no SQL/XSS/unserialize findings)
- DependencyAudit (no plugin-level composer deps; composer.json valid)
- Docker activation in elgg3 (plugin activates, isActive=true)
- Render gate (homepage 7011 bytes, login 7011 bytes)
- PHP_CodeSniffer (Elgg standard) — 0 errors after cleanup
- PHP syntax — clean

### Known carry-forwards (deferred to 3→4)

- `elgg_get_entities_from_annotation_calculation()` in `views/default/widgets/highestrating/content.php` — deprecated in 3.x but still functional; replace with QueryBuilder closure on `elgg_get_entities()` during 3→4.
- `start.php` procedural init — survives 3.x; converts to `elgg-plugin.php` + Bootstrap class in 4.x.
- `manifest.xml` retained per 3.x dual-config support; removed in 4.x.
- `lib/hooks.php` callbacks use old four-arg hook signature (`$hook, $type, $return, $params`); converts to `\Elgg\Hook` typed callbacks in 4.x.

## Data Preservation Notes

- The `starrating` annotation subtype is preserved unchanged across 2.x → 3.x. Annotation storage shape (name, value, owner_guid, access_id) is identical.
- `bodyology_library/lib/events.php::bodyology_library_update_rating()` and `bodyology_feedback` continue to operate unchanged.
- Plugin-setting array storage shape changed (serialize → JSON). The upgrade batch handles existing data; the runtime decoder handles unmigrated sites.
