# FrontPup – Coding Conventions

## PHP

- Minimum PHP version: **8.0**. Use typed properties, named arguments, and null-safe operators where appropriate.
- All files must begin with `if ( ! defined( 'ABSPATH' ) ) exit;` to prevent direct access.
- All files end with `// eof` on the last line.
- Classes use **PascalCase** with the `FrontPup_` prefix (e.g. `FrontPup_Clear_Cache`).
- Lightweight AWS classes use the `LightAWS_` prefix (e.g. `LightAWS_CloudFront`).
- Methods and functions use **snake_case**.
- Constants use **SCREAMING_SNAKE_CASE** with the `FRONTPUP_` prefix.
- WordPress option keys use **snake_case** with the `frontpup_` prefix.
- All classes are **singletons** where a single shared instance is appropriate; use `get_instance()` / `private __construct()`.
- Admin setting classes extend `FrontPup_Admin_Base` and override `$settings_key`, `$settings_defaults`, `$booleanFields`, `$numericFields`, `$stringFields`, and `$view`.
- Views are plain PHP templates in `admin/views/`. They receive `$this` (the controller) and `$settings` (current values).
- Always use WordPress escaping functions in views: `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`, `esc_xml()`.
- Always use `__()` / `esc_html__()` with the `'frontpup'` text domain for translatable strings.
- Use `wp_send_json_success()` / `wp_send_json_error()` for AJAX responses.
- Nonces: create with `wp_create_nonce('frontpup_clear_cache_nonce')`, verify with `check_ajax_referer()`.
- Capability check for admin actions: `current_user_can( 'manage_options' )`.
- Settings registration follows the WordPress Settings API: `register_setting()` → `sanitize_settings()` → `update_option` action hook.

## JavaScript

- **No jQuery** in new code. The legacy `admin/js/clear-cache.js` uses jQuery but has been superseded by `js/admin-bar.js`.
- Use `document.addEventListener('DOMContentLoaded', ...)` for initialization.
- Use the native `fetch()` API with `FormData` for AJAX calls.
- Localized PHP data is passed via `wp_localize_script()` into a global object (e.g. `frontpupClearCache`).
- Files end with `// eof`.

## CSS

- Scoped under `.frontpup-settings` or `.frontpup-admin-welcome` to avoid conflicts with WordPress core styles.
- Utility classes follow a simple pattern: `frontpup-{property}-{value}` (e.g. `frontpup-mt-4`, `frontpup-mb-0`).
- The "Recommended" badge style (`.recommended`) is defined inline in view files where needed.

## File Naming

- PHP include files (inside `includes/`):
  - Standard includes: `class-{kebab-case-name}.php`
  - Lightweight AWS includes: `lightaws-*.php`
- PHP include files: `class-{kebab-case-name}.php` (inside `includes/`)
- JS files: `{kebab-case-name}.js`
- CSS files: `{kebab-case-name}.css`
- View templates: `{kebab-case-name}.php` inside `admin/views/`

## Versioning & Changelog

- Version numbers follow loose **Semantic Versioning** (`MAJOR.MINOR[.PATCH]`), so releases may be tagged as `1.4` or `1.4.0`.
- Every release gets an entry in both `CHANGELOG.md` (Markdown, GitHub) and `readme.txt` (WordPress.org).
- `CHANGELOG.md` format: `## [X.Y[.Z]] - YYYY-MM-DD` with a bullet list of changes.
- `readme.txt` format: `= X.Y[.Z] =` with `Released: YYYY-MM-DD` and a bullet list.
- Update the `FRONTPUP_VERSION` constant in `frontpup.php` and the `Stable tag` in `readme.txt` to match the release version for each release.
