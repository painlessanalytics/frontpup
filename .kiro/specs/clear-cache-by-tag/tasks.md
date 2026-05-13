# Implementation Plan: Clear Cache by Post Type

## Overview

This implementation plan breaks down the "Clear Cache by Post Type" feature into discrete coding tasks. The feature adds a new admin page that allows WordPress administrators to selectively invalidate CloudFront cache by specific tags (post types and special tags) rather than clearing the entire cache.

The implementation follows the existing FrontPup architecture:
- Creates a new admin controller class extending `FrontPup_Admin_Base`
- Creates a new view template with two-column checkbox layout
- Modifies the admin bootstrap to register the new submenu page
- Enhances the `FrontPup_Clear_Cache::clear_cache()` method to accept a `$tags` parameter
- Implements tag-to-path conversion logic for CloudFront invalidation
- Uses WordPress security best practices (nonces, capability checks, input sanitization)
- Follows WordPress coding standards and plugin conventions

## Tasks

- [x] 1. Create admin controller class for Clear Cache by Post Type page
  - Create new file `admin/clear-cache-by-tag.class.php`
  - Define class `FrontPup_Admin_Clear_Cache_By_Tag` extending `FrontPup_Admin_Base`
  - Set `$settings_key = ''` (no settings stored)
  - Set `$view = 'clear-cache-by-tag-settings'`
  - Set `$page_title = ''` (will be set by admin bootstrap)
  - Set empty arrays for `$settings_defaults`, `$booleanFields`, `$numericFields`, `$stringFields`
  - Add file header with ABSPATH check and file footer with `// eof`
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 2. Implement helper methods in admin controller
  - [x] 2.1 Add `get_public_post_types()` private method
    - Method signature: `private function get_public_post_types(): array`
    - Call `get_post_types(['public' => true], 'objects')`
    - Return array of WP_Post_Type objects
    - Add PHPDoc comment
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 2.2 Add `get_special_tags()` private method
    - Method signature: `private function get_special_tags(): array`
    - Return hardcoded array: `['error', 'home', 'search', 'archive', 'author', 'unknown']`
    - Add PHPDoc comment explaining these match the tags from `FrontPup::get_cache_tag()`
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 2.3 Add `validate_tag()` private method
    - Method signature: `private function validate_tag( string $tag )`
    - Sanitize using `sanitize_text_field($tag)`
    - Validate format with regex: `/^[a-z0-9\-_]+$/i`
    - Convert to lowercase using `strtolower()`
    - Return sanitized tag or false if invalid
    - Add PHPDoc comment
    - _Requirements: 18.1, 18.2, 18.3_

- [x] 3. Implement form submission processing
  - [x] 3.1 Add `process_form_submission()` private method
    - Method signature: `private function process_form_submission(): void`
    - Check if form submitted: `isset($_POST['frontpup_clear_cache_by_tag_nonce'])`
    - Verify nonce using `check_admin_referer('frontpup_clear_cache_by_tag_action', 'frontpup_clear_cache_by_tag_nonce')`
    - Check capability using `current_user_can('manage_options')`
    - Collect tags from `$_POST['frontpup_tags']` array (if set)
    - Validate each tag using `validate_tag()` method
    - Remove invalid tags from array
    - Check if more than 50 tags selected, display error if so
    - Convert empty array to null
    - Call `FrontPup::get_clear_cache_instance()->clear_cache($tags)`
    - Display success or error message using `add_settings_error()`
    - Add PHPDoc comment
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 17.1, 17.2, 17.3, 18.4, 18.5_

  - [ ]* 3.2 Write unit tests for form submission processing
    - Test form submission with selected tags calls clear_cache with tags array
    - Test form submission with no tags calls clear_cache with null
    - Test form submission sanitizes tag values
    - Test form submission rejects more than 50 tags
    - Test nonce verification prevents invalid submissions
    - Test capability check prevents unauthorized access
    - _Requirements: 6.1, 6.2, 6.3, 17.1, 17.2, 17.3, 18.1, 18.2, 18.3_

- [x] 4. Override view() method to handle form submission
  - Add `view()` public method to `FrontPup_Admin_Clear_Cache_By_Tag` class
  - Method signature: `public function view(): void`
  - Call `process_form_submission()` at the beginning
  - Get public post types using `get_public_post_types()`
  - Get special tags using `get_special_tags()`
  - Include view template: `require_once FRONTPUP_PLUGIN_PATH . 'admin/views/' . $this->view . '.php'`
  - Add PHPDoc comment
  - _Requirements: 1.5, 6.1, 11.1, 11.2_

- [x] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Create view template for Clear Cache by Post Type page
  - Create new file `admin/views/clear-cache-by-tag-settings.php`
  - Add ABSPATH check at top
  - Create wrapper div with class `wrap frontpup-settings`
  - Add h1 heading: "Clear Cache by Post Type"
  - Call `settings_errors()` to display feedback messages
  - Create form with method="post" and action=""
  - Add nonce field using `wp_nonce_field('frontpup_clear_cache_by_tag_action', 'frontpup_clear_cache_by_tag_nonce')`
  - Add file footer with `// eof`
  - _Requirements: 1.1, 1.2, 1.3, 1.5, 5.1, 5.2, 17.3_

- [x] 7. Implement two-column checkbox layout in view template
  - [x] 7.1 Create two-column container
    - Add div with class `frontpup-two-column-layout`
    - Create first column div with class `frontpup-column`
    - Create second column div with class `frontpup-column`
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 7.2 Add public post types column
    - Add h2 heading: "Public Post Types"
    - Loop through `$public_post_types` array
    - For each post type, create checkbox with name `frontpup_tags[]`
    - Set checkbox value to `$post_type->name`
    - Set checkbox label to `$post_type->label`
    - Use `esc_attr()` for value, `esc_html()` for label
    - Wrap each checkbox in label tag for better UX
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 4.1_

  - [x] 7.3 Add special tags column
    - Add h2 heading: "Special Tags"
    - Loop through `$special_tags` array
    - For each tag, create checkbox with name `frontpup_tags[]`
    - Set checkbox value to tag value
    - Set checkbox label to ucfirst(tag value)
    - Use `esc_attr()` for value, `esc_html()` for label
    - Wrap each checkbox in label tag for better UX
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 4.2_

  - [x] 7.4 Add notice and submit button
    - Add paragraph with class `frontpup-notice`
    - Set notice text: "When no tags above are selected the entire cache will be cleared."
    - Use `esc_html__()` with 'frontpup' text domain for translation
    - Call `submit_button('Submit')` to render submit button
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 8. Add CSS styling for two-column layout
  - [x] 8.1 Add inline styles to view template
    - Create style tag in view template head
    - Define `.frontpup-two-column-layout` with flexbox display and gap
    - Define `.frontpup-column` with flex: 1
    - Define `.frontpup-column h2` with font size and margin
    - Define `.frontpup-column label` with display block and margin
    - Define `.frontpup-notice` with background, border, padding, and font style
    - _Requirements: 4.3, 4.4, 4.5, 5.5_

  - [ ]* 8.2 Write visual regression tests
    - Test two-column layout renders correctly
    - Test checkboxes are properly aligned
    - Test notice styling is visually distinct
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 9. Register submenu page in admin bootstrap
  - Modify `frontpup-admin.class.php` file
  - In `__construct()` method, add new admin view to `$this->admin_views` array
  - Key: `'clear-cache-by-tag'`, Value: `new FrontPup_Admin_Clear_Cache_By_Tag()`
  - In `admin_menu()` method, add new submenu page using `add_submenu_page()`
  - Parent slug: `'frontpup-plugin'`
  - Page title: `__('Clear Cache by Post Type', 'frontpup')`
  - Menu title: `__('Clear Cache by Post Type', 'frontpup')`
  - Capability: `'manage_options'`
  - Menu slug: `'frontpup-clear-cache-by-tag'`
  - Callback: `[$this->admin_views['clear-cache-by-tag'], 'view']`
  - In `admin_init()` method, set page title using `set_page_title()`
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 20.1, 20.2, 20.3, 20.4, 20.5_

- [x] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Modify clear_cache() method to accept $tags parameter
  - Modify `clear-cache.class.php` file
  - Update `clear_cache()` method signature to: `public function clear_cache( ?array $tags = null ): bool`
  - Add PHPDoc comment documenting the new parameter
  - Add logic at beginning of method to convert empty array to null
  - _Requirements: 7.1, 7.2, 8.3, 19.4_

- [x] 12. Implement tag-to-path conversion logic
  - [x] 12.1 Add path building logic in clear_cache() method
    - After converting empty array to null, check if `$tags === null`
    - If null, set `$paths = ['/*']` (clear entire cache)
    - If not null, initialize empty `$paths` array
    - Loop through `$tags` array
    - For each tag, append `'tag:' . $tag . '/*'` to `$paths` array
    - _Requirements: 7.3, 7.4, 7.5, 8.1, 8.2, 8.4, 13.1, 13.2, 13.3_

  - [ ]* 12.2 Write unit tests for path conversion
    - Test null creates ['/*'] path
    - Test empty array creates ['/*'] path
    - Test ['post', 'page'] creates ['tag:post/*', 'tag:page/*']
    - Test single tag creates single path
    - Test path format is correct (starts with 'tag:', ends with '/*')
    - _Requirements: 7.3, 7.4, 7.5, 8.1, 8.2, 13.1, 13.2, 13.3_

- [ ] 13. Update lightweight SDK path to use multiple paths
  - [x] 13.1 Modify lightweight SDK createInvalidation call
    - In `clear_cache()` method, locate the lightweight SDK path
    - Update `$cf->createInvalidation()` call to pass `$paths` array instead of `['/*']`
    - Ensure the method signature accepts array of paths
    - _Requirements: 8.5, 13.4, 15.1, 15.2, 15.3_

  - [x] 13.2 Verify LightAWS_CloudFront::createInvalidation() supports multiple paths
    - Check `includes/lightaws-cloudfront.php` file
    - Verify `createInvalidation()` method accepts array of paths as second parameter
    - Verify method constructs InvalidationBatch XML with multiple Path elements
    - Verify Quantity element is set to count of paths
    - If modifications needed, update the method accordingly
    - _Requirements: 15.3, 15.4, 15.5_

  - [ ]* 13.3 Write unit tests for lightweight SDK path
    - Test createInvalidation receives multiple paths array
    - Test createInvalidation receives single /* path
    - Test InvalidationBatch XML structure is correct
    - Test Quantity matches path count
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

- [x] 14. Update full AWS SDK path to use multiple paths
  - [x] 14.1 Modify full SDK createInvalidation call
    - In `clear_cache()` method, locate the full AWS SDK path
    - Update `$client->createInvalidation()` call to pass `$paths` in InvalidationBatch structure
    - Set `'Paths' => ['Quantity' => count($paths), 'Items' => $paths]`
    - _Requirements: 8.5, 13.4, 13.5, 16.1, 16.2, 16.3, 16.4_

  - [ ]* 14.2 Write unit tests for full SDK path
    - Test createInvalidation receives paths in Items parameter
    - Test Quantity matches path count
    - Test InvalidationBatch structure is correct
    - Test error handling works with multiple paths
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5_

- [x] 15. Implement success and error feedback
  - [x] 15.1 Add success message in process_form_submission()
    - After calling `clear_cache()`, check if return value is true
    - If true, call `add_settings_error()` with type 'updated'
    - Message: `__('Cache invalidation request completed successfully.', 'frontpup')`
    - Settings error ID: `'frontpup_clear_cache_by_tag'`
    - Message ID: `'cache_cleared'`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 14.4_

  - [x] 15.2 Add error message in process_form_submission()
    - After calling `clear_cache()`, check if return value is false
    - If false, get error message using `FrontPup::get_clear_cache_instance()->get_last_error()`
    - Call `add_settings_error()` with type 'error'
    - Message: `sprintf(__('Error occurred while clearing CloudFront cache: %s', 'frontpup'), $error_message)`
    - Settings error ID: `'frontpup_clear_cache_by_tag'`
    - Message ID: `'cache_clear_failed'`
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [x] 15.3 Add tag limit error message
    - In `process_form_submission()`, after collecting tags, check if count > 50
    - If true, call `add_settings_error()` with type 'error'
    - Message: `__('Maximum 50 tags can be selected at once. Please reduce your selection.', 'frontpup')`
    - Return early without calling `clear_cache()`
    - _Requirements: 18.4, 18.5_

  - [ ]* 15.4 Write unit tests for feedback messages
    - Test success message displayed when clear_cache returns true
    - Test error message displayed when clear_cache returns false
    - Test error message includes details from get_last_error()
    - Test tag limit error message displayed when > 50 tags
    - _Requirements: 9.1, 9.2, 9.3, 10.1, 10.2, 10.3, 18.4, 18.5_

- [x] 16. Implement page state preservation
  - Verify that `view()` method re-renders the page after form submission (no redirect)
  - Verify that checkboxes are not pre-checked (reset to unchecked state)
  - Verify that settings_errors() displays messages at top of page
  - Verify that URL remains the same after submission
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [x] 17. Add debug logging support (optional enhancement)
  - In `process_form_submission()`, add debug logging when `FRONTPUP_DEBUG` is defined
  - Log selected tags before processing: `error_log('FrontPup Clear Cache by Post Type: Selected tags = ' . print_r($tags, true))`
  - In `clear_cache()`, log converted invalidation paths when `FRONTPUP_DEBUG` is defined
  - Log: `error_log('FrontPup Clear Cache by Post Type: Invalidation paths = ' . print_r($paths, true))`
  - _Requirements: N/A (enhancement for debugging)_

- [x] 18. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ]* 19. Write integration tests
  - Test end-to-end tag-based invalidation with lightweight SDK
  - Test end-to-end tag-based invalidation with full AWS SDK
  - Test end-to-end full cache clear (no tags selected)
  - Test menu integration (submenu appears in FrontPup menu)
  - Test page renders correctly with public post types and special tags
  - Test form submission with valid tags creates correct invalidation
  - Test form submission with no tags creates /* invalidation
  - Test success and error messages display correctly
  - Test nonce verification prevents CSRF attacks
  - Test capability check prevents unauthorized access
  - Test tag limit enforcement (> 50 tags)
  - Test compatibility with existing clear cache functionality
  - _Requirements: 1.5, 6.1, 9.1, 10.1, 11.1, 12.1, 12.2, 12.3, 12.4, 12.5, 14.1, 14.2, 14.3, 17.1, 17.2, 17.3, 18.4, 19.1, 19.2, 19.3, 19.4, 19.5, 20.1, 20.2, 20.3, 20.4, 20.5_

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Unit tests validate specific examples and edge cases
- The implementation leverages existing FrontPup admin patterns and CloudFront integration
- No database migrations needed - this page does not store settings
- Feature is backward compatible - existing clear cache functionality unchanged
- All code follows WordPress coding standards and FrontPup conventions

## Testing Strategy

This feature uses **example-based unit tests** (not property-based testing) because:
- Heavily dependent on WordPress admin infrastructure (Settings API, nonces, capabilities)
- Integrates with external AWS CloudFront API (side effects)
- Tests specific admin page rendering and form handling
- Validates specific tag-to-path conversion patterns

Manual testing checklist:
- [ ] Submenu "Clear Cache by Post Type" appears in FrontPup admin menu
- [ ] Page displays all public post types with checkboxes
- [ ] Page displays all special tags with checkboxes
- [ ] Two-column layout renders correctly
- [ ] Notice text is visible and styled correctly
- [ ] Submit button is present and functional
- [ ] Selecting tags and submitting creates tag-based invalidation in CloudFront
- [ ] Submitting with no tags creates /* invalidation (entire cache)
- [ ] Success message displays after successful invalidation
- [ ] Error message displays after failed invalidation
- [ ] Page state is preserved after submission (no redirect)
- [ ] Checkboxes reset to unchecked after submission
- [ ] Nonce verification prevents CSRF attacks
- [ ] Non-admin users cannot access page
- [ ] Works with lightweight SDK (default)
- [ ] Works with full AWS SDK (when enabled)
- [ ] Works with all three credential modes (policy, wpconfig, database)
- [ ] Selecting more than 50 tags displays error message
- [ ] Invalid tag characters are filtered out
- [ ] Existing "Clear CloudFront Cache" admin bar action still works
- [ ] Existing clear cache settings page still works
