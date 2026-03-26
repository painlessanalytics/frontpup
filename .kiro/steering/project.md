# FrontPup – Project Overview

FrontPup is a WordPress plugin that integrates with AWS CloudFront to manage cache headers and invalidations. It is developed and maintained by Painless Analytics (Angelo Mandato).

- Plugin slug: `frontpup`
- Text domain: `frontpup`
- Current version: `1.4`
- Requires WordPress: 6.0+, PHP: 8.1+
- License: GPL v2 or later
- Plugin URI: https://www.painlessanalytics.com/frontpup-cloudfront-wordpress-plugin/
- GitHub: https://github.com/painlessanalytics/frontpup

## Core Features

- Set HTTP Cache-Control headers (no-cache, browser-only, browser+CDN) via the `send_headers` filter
- Clear CloudFront cache (create invalidation) from the admin settings page or the admin bar
- Non-intrusive admin bar "Clear CloudFront Cache" action using AJAX (no page reload)
- Supports three AWS credential modes: IAM policy/role (`policy`), `wp-config.php` constants (`wpconfig`), or database-stored keys (`database`)
- Two CloudFront SDK paths: a bundled lightweight custom implementation (default) and the full AWS SDK for PHP (optional)

## Directory Structure

```
frontpup.php                  # Plugin entry point, defines constants
frontpup.class.php            # Core class: send_headers filter, get_clear_cache_instance()
frontpup-admin.class.php      # Admin bootstrap: registers admin views
frontpup-admin-bar.class.php  # Admin bar menu + AJAX clear-cache handler
clear-cache.class.php         # FrontPup_Clear_Cache: orchestrates cache invalidation

admin/
  base.class.php              # FrontPup_Admin_Base: shared settings page logic
  cache-control.class.php     # FrontPup_Admin_Cache_Control: Cache Settings page
  clear-cache.class.php       # FrontPup_Admin_Clear_Cache: Clear Cache Settings page
  welcome.class.php           # FrontPup_Admin_Welcome: Welcome page
  views/                      # PHP view templates for each settings page
  js/clear-cache.js           # Legacy JS (superseded by js/admin-bar.js)

includes/
  lightaws-base.php           # LightAWS_Base: SigV4 signing + HTTP helpers
  lightaws-cloudfront.php     # LightAWS_CloudFront: createInvalidation, getInvalidation, listInvalidations
  lightaws-cloudfront-wp.php   # LightAWS_CloudFront_WP
  lightaws-http-wp-trait.php  # LightAWS_HTTP_WP_Trait: optional WP HTTP API transport

aws/                          # Full AWS SDK for PHP (optional, only loaded when full_aws_sdk enabled)
css/                          # Plugin stylesheets (admin-bar.css, etc.)
js/                           # Plugin scripts (admin-bar.js)
images/                       # Plugin images
```

## Key Constants

| Constant | Default | Description |
|---|---|---|
| `FRONTPUP_VERSION` | `'1.4'` | Plugin version |
| `FRONTPUP_REGION` | `'us-east-1'` | Default AWS region |
| `FRONTPUP_PLUGIN_PATH` | `plugin_dir_path(__FILE__)` | Absolute path to plugin root |
| `FRONTPUP_DEBUG` | _(undefined)_ | Enable debug `X-Front-Pup` response headers |
| `FRONTPUP_ACCESS_KEY_ID` | _(undefined)_ | AWS key for `wpconfig` credential mode |
| `FRONTPUP_SECRET_ACCESS_KEY` | _(undefined)_ | AWS secret for `wpconfig` credential mode |

## WordPress Options (Database)

| Option key | Class | Description |
|---|---|---|
| `frontpup_plugin_settings` | `FrontPup_Admin_Cache_Control` | Cache-Control settings |
| `frontpup_clear_cache` | `FrontPup_Admin_Clear_Cache` | Clear cache / credentials settings |

## Class Hierarchy

- `FrontPup` – singleton, core plugin logic
- `FrontPup_AdminBar` – singleton, admin bar + AJAX
- `FrontPup_Admin` – singleton, admin menu bootstrap
  - `FrontPup_Admin_Base` – base settings page
    - `FrontPup_Admin_Cache_Control`
    - `FrontPup_Admin_Clear_Cache`
    - `FrontPup_Admin_Welcome`
- `FrontPup_Clear_Cache` – cache invalidation logic
- `LightAWS_Base` – SigV4 signing + HTTP
  - `LightAWS_CloudFront` – CloudFront API calls
