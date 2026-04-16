# Implementation Plan: Cache Unique Visitors

## Overview

This implementation adds the Cache Unique Visitors feature to FrontPup, enabling CloudFront to cache different versions of pages for authenticated vs. anonymous users. The feature integrates seamlessly with existing cache control logic by extending the `FrontPup_Admin_Cache_Control` settings class and adding cookie handling to the `FrontPup::send_headers()` method.

## Tasks

- [x] 1. Extend settings management for Cache Unique Visitors
  - [x] 1.1 Add new settings fields to `FrontPup_Admin_Cache_Control`
    - Update `$settings_defaults` to include `cache_unique_visitors_enabled` (default: 0) and `cache_unique_visitors_cookie_name` (default: 'cf_cache')
    - Add `cache_unique_visitors_enabled` to `$booleanFields` array
    - Add `cache_unique_visitors_cookie_name` to `$stringFields` array
    - _Requirements: 1.4, 1.5, 2.5, 8.4, 8.5_

  - [x] 1.2 Override `sanitize_settings()` method to add cookie name validation
    - Call parent `sanitize_settings()` to handle standard field sanitization
    - Add custom sanitization for `cache_unique_visitors_cookie_name` using regex pattern `/[^a-zA-Z0-9_-]/`
    - If sanitized cookie name is empty, set to default 'cf_cache'
    - _Requirements: 2.4, 8.1, 8.2_

  - [ ]* 1.3 Write property test for cookie name sanitization
    - **Property 1: Cookie Name Sanitization**
    - **Validates: Requirements 2.4, 8.1, 8.2**
    - Generate random strings with various character sets
    - Assert sanitized output matches `/^[a-zA-Z0-9_-]*$/`

  - [ ]* 1.4 Write unit tests for settings sanitization
    - Test cookie name with invalid characters is sanitized correctly
    - Test empty cookie name falls back to 'cf_cache'
    - Test valid cookie names pass through unchanged
    - Test boolean toggle values are cast correctly
    - _Requirements: 2.4, 8.1, 8.2, 8.5_

- [x] 2. Update Cache Settings view template
  - [x] 2.1 Add Cache Unique Visitors section to `admin/views/cache-control-settings.php`
    - Add new `<h2>` heading "Cache Unique Visitors" after existing cache control settings
    - Create form table with two rows: enable toggle and cookie name input
    - Use `esc_attr($this->settings_key)` for input names
    - Use `checked()` helper for checkbox state
    - Add help text explaining CloudFront cache policy configuration requirement
    - _Requirements: 1.1, 2.1, 2.2, 3.1, 3.2, 3.3_

  - [x] 2.2 Add JavaScript for conditional cookie name field visibility
    - Add inline `<script>` tag at end of view file
    - Show/hide cookie name row based on toggle state using `getElementById()` and `style.display`
    - Follow existing pattern from `custom_smaxage_enabled` checkbox
    - Use `DOMContentLoaded` event for initialization
    - _Requirements: 1.1, 2.1_

- [x] 3. Checkpoint - Verify settings UI and persistence
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement cookie value generator
  - [x] 4.1 Add `generate_unique_visitor_value()` method to `FrontPup` class
    - Create private method with return type `string`
    - Get user ID using `get_current_user_id()`
    - Get session token using `wp_get_session_token()`
    - Combine user ID and session token with pipe separator: `$user_id . '|' . $session_token`
    - Generate MD5 hash of combined data
    - Return 32-character hex string
    - _Requirements: 4.3, 4.5, 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ]* 4.2 Write property test for cookie value determinism
    - **Property 5: Cookie Value Determinism**
    - **Validates: Requirements 4.5, 6.2, 6.4**
    - Generate random user ID and session token pairs
    - Call `generate_unique_visitor_value()` multiple times with same inputs
    - Assert all outputs are identical

  - [ ]* 4.3 Write property test for cookie value uniqueness
    - **Property 6: Cookie Value Uniqueness Across Sessions**
    - **Validates: Requirements 4.3, 6.1, 6.3**
    - Generate two different user sessions (different user ID or session token)
    - Assert generated cookie values are different

  - [ ]* 4.4 Write property test for PII protection
    - **Property 7: Cookie Value Does Not Contain PII**
    - **Validates: Requirements 6.5**
    - Generate random user IDs
    - Assert user ID does not appear as plain text substring in cookie value

  - [ ]* 4.5 Write unit tests for cookie value generation
    - Test same user ID + session token produces same hash
    - Test different user IDs produce different hashes
    - Test different session tokens produce different hashes
    - Test hash is 32 characters (MD5 format)
    - Test hash does not contain user ID in plain text
    - _Requirements: 4.3, 4.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 5. Implement cookie handler logic
  - [x] 5.1 Add `set_unique_visitor_cookie()` method to `FrontPup` class
    - Create private method with return type `void`
    - Check if `cache_unique_visitors_enabled` setting is truthy, return early if not
    - Check if `LOGGED_IN_COOKIE` constant is defined and cookie is set, return early if not
    - Check if headers already sent using `headers_sent()`, return early if true
    - Get cookie name from settings with fallback to 'cf_cache'
    - Check if cookie already exists with same value to avoid redundant setcookie calls
    - Generate cookie value using `generate_unique_visitor_value()`
    - Determine secure flag using `is_ssl()`
    - Call `setcookie()` with parameters: name, value, 0 (session), '/', '', secure, true (httponly), 'Lax' (samesite)
    - _Requirements: 1.2, 1.3, 4.1, 4.2, 4.4, 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 5.2 Integrate cookie handler into `send_headers()` method
    - Add call to `set_unique_visitor_cookie()` at the beginning of `send_headers()` method
    - Place before existing cache header logic to ensure cookie is set first
    - Ensure existing cache control logic continues unchanged
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ]* 5.3 Write property test for feature disabled prevents cookie
    - **Property 2: Feature Disabled Prevents Cookie**
    - **Validates: Requirements 1.2**
    - Generate random request states with feature disabled
    - Assert cookie is not set in response

  - [ ]* 5.4 Write property test for authenticated user gets cookie
    - **Property 3: Authenticated User Gets Cookie When Enabled**
    - **Validates: Requirements 1.3, 4.1**
    - Generate random authenticated user states with feature enabled
    - Assert cookie is present in response

  - [ ]* 5.5 Write property test for unauthenticated user never gets cookie
    - **Property 4: Unauthenticated User Never Gets Cookie**
    - **Validates: Requirements 4.2**
    - Generate random unauthenticated states
    - Assert cookie is not set regardless of feature state

  - [ ]* 5.6 Write property test for custom cookie name usage
    - **Property 8: Custom Cookie Name Usage**
    - **Validates: Requirements 2.3**
    - Generate random valid cookie names
    - Assert cookie name in response matches input

  - [ ]* 5.7 Write property test for secure flag on HTTPS
    - **Property 9: Secure Flag on HTTPS**
    - **Validates: Requirements 5.3**
    - Generate random HTTPS/HTTP states
    - Assert Secure flag present when is_ssl=true, absent when is_ssl=false

  - [ ]* 5.8 Write property test for default values applied
    - **Property 10: Default Values Applied**
    - **Validates: Requirements 8.5**
    - Generate random missing setting keys
    - Assert missing keys get default values from `$settings_defaults`

  - [ ]* 5.9 Write unit tests for cookie setting logic
    - Test cookie is set when feature enabled and user authenticated
    - Test cookie is not set when feature disabled
    - Test cookie is not set when user not authenticated
    - Test cookie attributes (path, httponly, samesite) are correct
    - Test secure flag set on HTTPS, not set on HTTP
    - Test headers already sent prevents cookie setting
    - _Requirements: 1.2, 1.3, 4.1, 4.2, 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Integration testing and validation
  - [ ]* 7.1 Write integration tests for WordPress Settings API
    - Test settings are saved to database via WordPress Settings API
    - Test settings persist across page reloads
    - Test settings are loaded correctly in `FrontPup` constructor
    - _Requirements: 8.3, 8.4_

  - [ ]* 7.2 Write integration tests for send_headers filter
    - Test cookie setting executes during send_headers filter
    - Test existing cache header logic continues to work
    - Test LOGGED_IN_COOKIE detection is not affected
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 8. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property-based tests use PHPUnit with Eris library (minimum 100 iterations per property)
- All code follows FrontPup coding conventions (PHP 8.1+, snake_case methods, WordPress escaping)
- Cookie setting is best-effort; failures are logged but do not break page rendering
- Feature is disabled by default for backward compatibility
- No database migration required; new settings use defaults for existing installations
