# Implementation Plan: Tag-Based Caching

## Overview

This implementation plan breaks down the tag-based caching feature into discrete coding tasks. The feature adds a new setting to enable tag-based caching and modifies the `send_headers` filter to inject the `x-amz-meta-cache-tag` HTTP header with WordPress post type values for CloudFront's tag-based invalidation feature.

The implementation follows the existing FrontPup architecture:
- Extends the `FrontPup_Admin_Clear_Cache` settings class
- Modifies the `admin/views/clear-cache-settings.php` view template
- Enhances the `FrontPup::send_headers()` method with new helper methods
- Uses WordPress conditional tags for post type detection
- Follows WordPress coding standards and plugin conventions

## Tasks

- [x] 1. Add tag-based caching setting to Clear Cache admin class
  - Modify `admin/clear-cache.class.php` to add `tag_based_caching_enabled` to `$settings_defaults` array (default: 0)
  - Add `tag_based_caching_enabled` to `$booleanFields` array for sanitization
  - _Requirements: 1.2, 1.3, 4.1, 4.2, 4.4, 4.5_

- [x] 2. Add tag-based caching checkbox to Clear Cache Settings view
  - Modify `admin/views/clear-cache-settings.php` to add checkbox after "Enable Clear Cache" option
  - Use `checked()` helper to reflect current setting state
  - Add description text: "The plugin will send the x-amz-meta-cache-tag header with the post type"
  - Follow existing styling patterns (same visual style as other checkboxes)
  - Use proper escaping: `esc_attr()`, `esc_html()`, `__()` with 'frontpup' text domain
  - _Requirements: 1.1, 1.4, 1.5, 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 3. Implement cache tag sanitization helper method
  - [x] 3.1 Add `sanitize_cache_tag()` private method to `FrontPup` class in `frontpup.class.php`
    - Method signature: `private function sanitize_cache_tag( string $tag ): string`
    - Convert to lowercase using `strtolower()`
    - Remove invalid characters using `preg_replace('/[^a-z0-9\-_]/', '', $tag)`
    - Truncate to 256 characters using `substr($tag, 0, 256)`
    - Return 'unknown' if result is empty string
    - Add PHPDoc comment describing CloudFront compliance requirements
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ]* 3.2 Write unit tests for sanitization method
    - Test uppercase conversion: 'POST' → 'post'
    - Test space removal: 'my post type' → 'myposttype'
    - Test special character removal: 'my-post_type!@#' → 'my-post_type'
    - Test 256 character truncation
    - Test empty string returns 'unknown'
    - Test only invalid characters returns 'unknown'
    - _Requirements: 5.2, 5.3, 5.4, 5.5_

- [x] 4. Implement post type detection helper method
  - [x] 4.1 Add `get_cache_tag()` private method to `FrontPup` class in `frontpup.class.php`
    - Method signature: `private function get_cache_tag(): string`
    - Check `is_404()` → return empty string (no header)
    - Check `is_wp_error($wp_query)` or `is_wp_error($wp_query->get_queried_object())` → return empty string
    - Check `is_home()` → return 'home'
    - Check `is_search()` → return 'search'
    - Check `is_singular()` → get post type from `get_post_type()` on queried object
    - Check `is_post_type_archive()` → get post type from `get_query_var('post_type')`
    - Check `is_category() || is_tag() || is_tax()` → return 'archive'
    - Check `is_author()` → return 'author'
    - Default fallback → return 'unknown'
    - Call `sanitize_cache_tag()` on the detected post type before returning
    - Add PHPDoc comment describing detection strategy
    - _Requirements: 2.1, 2.3, 6.4, 6.5, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

  - [ ]* 4.2 Write unit tests for post type detection
    - Test single post returns 'post'
    - Test single page returns 'page'
    - Test custom post type returns sanitized CPT slug
    - Test homepage returns 'home'
    - Test search results returns 'search'
    - Test category archive returns 'archive'
    - Test author archive returns 'author'
    - Test 404 page returns empty string
    - Test WordPress error returns empty string
    - Test unknown context returns 'unknown'
    - _Requirements: 2.3, 6.4, 6.5, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

- [x] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Modify send_headers method to inject cache tag header
  - [x] 6.1 Update `FrontPup::send_headers()` method in `frontpup.class.php`
    - Load `frontpup_clear_cache` settings using `get_option('frontpup_clear_cache', [])`
    - Check if `tag_based_caching_enabled` is truthy
    - If disabled, skip cache tag logic (early return to existing logic)
    - If enabled, call `get_cache_tag()` to detect post type
    - If `get_cache_tag()` returns non-empty string, send header using `header("x-amz-meta-cache-tag: $cache_tag")`
    - Place cache tag header injection AFTER early returns for `headers_sent()`, `is_404()`, and `is_wp_error()` checks
    - Place cache tag header injection BEFORE logged-in user no-cache headers (requirement 6.3)
    - Place cache tag header injection BEFORE existing Cache-Control header logic
    - Ensure existing Cache-Control headers continue to be sent (requirement 6.1)
    - _Requirements: 2.1, 2.2, 2.4, 6.1, 6.3_

  - [ ]* 6.2 Write unit tests for header injection
    - Test header sent for single post when enabled
    - Test header sent for page when enabled
    - Test header sent for custom post type when enabled
    - Test header NOT sent when disabled
    - Test header sent for homepage
    - Test header sent for search results
    - Test header sent for category archive
    - Test header sent for author archive
    - Test header NOT sent for 404 page
    - Test header sent before no-cache headers for logged-in users
    - Test Cache-Control headers still sent when tag-based caching enabled
    - _Requirements: 2.1, 2.2, 6.1, 6.3_

- [x] 7. Add debug header support (optional enhancement)
  - When `FRONTPUP_DEBUG` is defined and truthy, add `X-Front-Pup-Cache-Tag` debug header showing detected post type
  - Format: `header("X-Front-Pup-Cache-Tag: $cache_tag")`
  - Place alongside existing `X-Front-Pup` debug headers
  - _Requirements: N/A (enhancement for debugging)_

- [x] 8. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ]* 9. Write integration tests
  - Test end-to-end WordPress request with tag-based caching enabled
  - Test settings page save and reload preserves checkbox state
  - Test HTTP response includes correct `x-amz-meta-cache-tag` header for different post types
  - _Requirements: 1.5, 2.1, 2.5, 2.6_

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Unit tests validate specific examples and edge cases
- The implementation leverages existing WordPress conditional tags and query functions
- No database migrations needed - new setting has safe default value (disabled)
- Feature is backward compatible - no impact when disabled
- All code follows WordPress coding standards and FrontPup conventions

## Testing Strategy

This feature uses **example-based unit tests** (not property-based testing) because:
- Heavily dependent on WordPress global state (`$wp_query`, conditional tags)
- Sends HTTP headers (side-effect-only operation)
- Tests specific WordPress post type configurations

Manual testing checklist:
- [ ] Checkbox appears in Clear Cache Settings page
- [ ] Checkbox state persists after save
- [ ] Header appears in HTTP response when enabled (use browser dev tools)
- [ ] Header does NOT appear when disabled
- [ ] Different post types produce different tag values
- [ ] Special characters in custom post types are sanitized
- [ ] 404 pages do not include cache tag header
- [ ] Logged-in users receive cache tag header before no-cache headers
- [ ] Existing cache control functionality continues to work
