# Requirements Document

## Introduction

This document specifies the requirements for adding a "Clear Cache by Post Type" feature to the FrontPup WordPress plugin. This feature builds on the existing tag-based caching infrastructure (implemented in tag-based-caching spec) which sends `x-amz-meta-cache-tag` headers with post type values. The new feature will provide a WordPress admin interface for selectively invalidating CloudFront cache by specific tags, enabling more granular cache management than the existing "clear entire cache" functionality.

## Glossary

- **FrontPup**: The WordPress plugin that integrates with AWS CloudFront for cache management
- **CloudFront**: Amazon's content delivery network (CDN) service
- **Cache_Tag**: A custom identifier sent via HTTP header to categorize cached content (e.g., 'post', 'page', 'home')
- **Post_Type**: WordPress content type (e.g., 'post', 'page', 'product')
- **Public_Post_Type**: A WordPress post type registered with 'public' => true
- **Special_Tag**: Predefined cache tags for non-post-type pages: 'error', 'home', 'search', 'archive', 'author', 'unknown'
- **Admin_Page**: The "Clear Cache by Post Type" submenu page under the FrontPup admin menu
- **Admin_User**: WordPress user with 'manage_options' capability
- **Clear_Cache_Function**: The FrontPup_Clear_Cache::clear_cache() method that creates CloudFront invalidations
- **Invalidation_Path**: CloudFront path pattern used for cache invalidation (e.g., '/tag:post/*', '/*')
- **Settings_Error**: WordPress admin notice displayed using add_settings_error() function

## Requirements

### Requirement 1: Admin Submenu Page

**User Story:** As a WordPress administrator, I want to access a dedicated page for clearing cache by tag, so that I can selectively invalidate cached content.

#### Acceptance Criteria

1. THE FrontPup SHALL create a submenu page under the 'frontpup-plugin' menu using add_submenu_page
2. THE FrontPup SHALL set the submenu page title to "Clear Cache by Post Type"
3. THE FrontPup SHALL set the submenu menu title to "Clear Cache by Post Type"
4. THE FrontPup SHALL require 'manage_options' capability to access the submenu page
5. WHEN the Admin_User clicks the submenu item, THE FrontPup SHALL display the Admin_Page

### Requirement 2: Public Post Types Display

**User Story:** As a WordPress administrator, I want to see all public post types with checkboxes, so that I can select which post types to invalidate.

#### Acceptance Criteria

1. THE Admin_Page SHALL display a column labeled "Public Post Types"
2. THE Admin_Page SHALL retrieve all Public_Post_Types using get_post_types with 'public' => true
3. FOR ALL Public_Post_Types, THE Admin_Page SHALL display a checkbox with the post type name as the label
4. THE Admin_Page SHALL display the post type slug as the checkbox value
5. THE Admin_Page SHALL allow multiple Public_Post_Type checkboxes to be selected simultaneously

### Requirement 3: Special Tags Display

**User Story:** As a WordPress administrator, I want to see special tags with checkboxes, so that I can invalidate non-post-type pages.

#### Acceptance Criteria

1. THE Admin_Page SHALL display a column labeled "Special Tags"
2. THE Admin_Page SHALL display checkboxes for the following Special_Tags: 'error', 'home', 'search', 'archive', 'author', 'unknown'
3. FOR ALL Special_Tags, THE Admin_Page SHALL display a checkbox with the tag name as the label
4. THE Admin_Page SHALL display the tag value as the checkbox value
5. THE Admin_Page SHALL allow multiple Special_Tag checkboxes to be selected simultaneously

### Requirement 4: Two-Column Layout

**User Story:** As a WordPress administrator, I want the page organized in two columns, so that I can easily distinguish between post types and special tags.

#### Acceptance Criteria

1. THE Admin_Page SHALL display Public_Post_Types in the first column
2. THE Admin_Page SHALL display Special_Tags in the second column
3. THE Admin_Page SHALL position the first column to the left of the second column
4. THE Admin_Page SHALL align both columns horizontally at the top
5. THE Admin_Page SHALL use consistent spacing between checkboxes within each column

### Requirement 5: Submit Button and Notice

**User Story:** As a WordPress administrator, I want a submit button with a clear notice, so that I understand what happens when no tags are selected.

#### Acceptance Criteria

1. THE Admin_Page SHALL display a submit button labeled "Submit"
2. THE Admin_Page SHALL position the submit button below both columns
3. THE Admin_Page SHALL display a note stating "When no tags above are selected the entire cache will be cleared."
4. THE Admin_Page SHALL position the note directly above the submit button
5. THE Admin_Page SHALL display the note in a visually distinct style from other text

### Requirement 6: Form Submission Handling

**User Story:** As a WordPress administrator, I want the form to process my tag selections, so that the correct cache invalidation occurs.

#### Acceptance Criteria

1. WHEN the Admin_User clicks the submit button, THE FrontPup SHALL process the form submission
2. WHEN the form is submitted, THE FrontPup SHALL collect all checked Public_Post_Type values
3. WHEN the form is submitted, THE FrontPup SHALL collect all checked Special_Tag values
4. WHEN the form is submitted, THE FrontPup SHALL combine Public_Post_Type and Special_Tag values into a single array
5. WHEN no checkboxes are selected, THE FrontPup SHALL create an empty array

### Requirement 7: Clear Cache Function Parameter

**User Story:** As a WordPress administrator, I want the clear cache function to accept tag parameters, so that selective invalidation is possible.

#### Acceptance Criteria

1. THE Clear_Cache_Function SHALL accept a parameter named $tags
2. THE Clear_Cache_Function SHALL set the $tags parameter default value to null
3. WHEN $tags is null, THE Clear_Cache_Function SHALL set the invalidation paths to ['/*']
4. WHEN $tags is a PHP array, THE Clear_Cache_Function SHALL prefix each tag value with "tag:"
5. WHEN $tags is a PHP array, THE Clear_Cache_Function SHALL append "/*" to each prefixed tag value

### Requirement 8: Tag-Based Invalidation Path Construction

**User Story:** As a WordPress administrator, I want tags converted to CloudFront invalidation paths, so that CloudFront can invalidate the correct cached objects.

#### Acceptance Criteria

1. WHEN $tags contains ['post', 'page'], THE Clear_Cache_Function SHALL create Invalidation_Paths ['tag:post/*', 'tag:page/*']
2. WHEN $tags contains ['home'], THE Clear_Cache_Function SHALL create Invalidation_Paths ['tag:home/*']
3. WHEN $tags is an empty array, THE Clear_Cache_Function SHALL set $tags to null
4. WHEN $tags is null after empty array conversion, THE Clear_Cache_Function SHALL create Invalidation_Paths ['/*']
5. THE Clear_Cache_Function SHALL pass the Invalidation_Paths array to the CloudFront invalidation API

### Requirement 9: Success Feedback

**User Story:** As a WordPress administrator, I want to see a success message after cache invalidation, so that I know the operation completed.

#### Acceptance Criteria

1. WHEN the Clear_Cache_Function returns true, THE FrontPup SHALL call add_settings_error with type 'updated'
2. WHEN the Clear_Cache_Function returns true, THE FrontPup SHALL display a message stating "Cache invalidation request completed successfully."
3. WHEN the Clear_Cache_Function returns true, THE FrontPup SHALL re-display the Admin_Page
4. THE FrontPup SHALL display the Settings_Error at the top of the Admin_Page
5. THE FrontPup SHALL display the Settings_Error with a green background indicating success

### Requirement 10: Error Feedback

**User Story:** As a WordPress administrator, I want to see an error message if cache invalidation fails, so that I can troubleshoot the issue.

#### Acceptance Criteria

1. WHEN the Clear_Cache_Function returns false, THE FrontPup SHALL call add_settings_error with type 'error'
2. WHEN the Clear_Cache_Function returns false, THE FrontPup SHALL retrieve the error message using get_last_error()
3. WHEN the Clear_Cache_Function returns false, THE FrontPup SHALL display a message stating "Error occurred while clearing CloudFront cache."
4. WHEN the Clear_Cache_Function returns false, THE FrontPup SHALL append the error message from get_last_error() to the displayed message
5. THE FrontPup SHALL display the Settings_Error with a red background indicating error

### Requirement 11: Page State Preservation

**User Story:** As a WordPress administrator, I want the page to remain displayed after submission, so that I can perform multiple cache operations without navigation.

#### Acceptance Criteria

1. WHEN the form is submitted, THE FrontPup SHALL process the invalidation request
2. WHEN the invalidation request completes, THE FrontPup SHALL re-display the Admin_Page
3. WHEN the Admin_Page is re-displayed, THE FrontPup SHALL reset all checkboxes to unchecked state
4. WHEN the Admin_Page is re-displayed, THE FrontPup SHALL display the Settings_Error above the form
5. WHEN the Admin_Page is re-displayed, THE FrontPup SHALL maintain the same URL without redirecting

### Requirement 12: CloudFront API Integration

**User Story:** As a WordPress administrator, I want the feature to use the existing CloudFront integration, so that credentials and SDK configuration are consistent.

#### Acceptance Criteria

1. THE Clear_Cache_Function SHALL use the existing FrontPup_Clear_Cache class
2. THE Clear_Cache_Function SHALL retrieve settings from the 'frontpup_clear_cache' WordPress option
3. THE Clear_Cache_Function SHALL use the configured credentials_mode (policy, wpconfig, or database)
4. THE Clear_Cache_Function SHALL use the configured full_aws_sdk setting (lightweight or full SDK)
5. THE Clear_Cache_Function SHALL use the configured distribution_id for the invalidation request

### Requirement 13: Invalidation Paths Format

**User Story:** As a WordPress administrator, I want invalidation paths formatted correctly, so that CloudFront processes them properly.

#### Acceptance Criteria

1. THE Clear_Cache_Function SHALL format each Invalidation_Path as a string starting with "tag:"
2. THE Clear_Cache_Function SHALL format each Invalidation_Path ending with "/*"
3. THE Clear_Cache_Function SHALL create Invalidation_Paths without spaces or special characters except colon, forward slash, and asterisk
4. THE Clear_Cache_Function SHALL pass Invalidation_Paths as an array to the CloudFront createInvalidation method
5. THE Clear_Cache_Function SHALL set the CallerReference to a unique value using time()

### Requirement 14: Empty Selection Behavior

**User Story:** As a WordPress administrator, I want the entire cache cleared when no tags are selected, so that I have a quick way to clear everything.

#### Acceptance Criteria

1. WHEN no checkboxes are selected AND the submit button is clicked, THE FrontPup SHALL call Clear_Cache_Function with null parameter
2. WHEN Clear_Cache_Function receives null parameter, THE Clear_Cache_Function SHALL create Invalidation_Paths ['/*']
3. WHEN Invalidation_Paths is ['/*'], THE Clear_Cache_Function SHALL invalidate the entire CloudFront cache
4. WHEN the entire cache is cleared, THE FrontPup SHALL display a success message stating "Cache invalidation request completed successfully."
5. THE FrontPup SHALL display the same success message format regardless of whether tags were selected or entire cache was cleared

### Requirement 15: Lightweight SDK Path Support

**User Story:** As a WordPress administrator, I want the feature to work with the lightweight SDK, so that I don't need the full AWS SDK installed.

#### Acceptance Criteria

1. WHEN full_aws_sdk setting is empty or false, THE Clear_Cache_Function SHALL use LightAWS_CloudFront class
2. WHEN using LightAWS_CloudFront, THE Clear_Cache_Function SHALL pass Invalidation_Paths array to createInvalidation method
3. THE LightAWS_CloudFront::createInvalidation method SHALL accept an array of paths as the second parameter
4. THE LightAWS_CloudFront::createInvalidation method SHALL construct the InvalidationBatch XML with multiple Path elements
5. THE LightAWS_CloudFront::createInvalidation method SHALL set the Quantity element to the count of Invalidation_Paths

### Requirement 16: Full AWS SDK Path Support

**User Story:** As a WordPress administrator, I want the feature to work with the full AWS SDK, so that I have flexibility in SDK choice.

#### Acceptance Criteria

1. WHEN full_aws_sdk setting is truthy, THE Clear_Cache_Function SHALL use Aws\CloudFront\CloudFrontClient class
2. WHEN using CloudFrontClient, THE Clear_Cache_Function SHALL pass Invalidation_Paths array in the InvalidationBatch Paths Items parameter
3. THE CloudFrontClient createInvalidation call SHALL set the Quantity parameter to the count of Invalidation_Paths
4. THE CloudFrontClient createInvalidation call SHALL set the Items parameter to the Invalidation_Paths array
5. THE CloudFrontClient createInvalidation call SHALL use the same error handling as the existing clear_cache implementation

### Requirement 17: Security and Capability Checks

**User Story:** As a WordPress administrator, I want the feature to be secure, so that unauthorized users cannot clear the cache.

#### Acceptance Criteria

1. THE Admin_Page SHALL verify the Admin_User has 'manage_options' capability before displaying
2. THE FrontPup SHALL verify the Admin_User has 'manage_options' capability before processing form submission
3. THE FrontPup SHALL verify the WordPress nonce before processing form submission
4. WHEN capability check fails, THE FrontPup SHALL display a WordPress error message
5. WHEN nonce verification fails, THE FrontPup SHALL display a WordPress error message

### Requirement 18: Input Sanitization

**User Story:** As a WordPress administrator, I want user input sanitized, so that the feature is secure against injection attacks.

#### Acceptance Criteria

1. THE FrontPup SHALL sanitize all checkbox values using sanitize_text_field before processing
2. THE FrontPup SHALL validate that each tag value matches the expected format (alphanumeric, hyphens, underscores)
3. WHEN a tag value contains invalid characters, THE FrontPup SHALL remove that tag from the array
4. THE FrontPup SHALL limit the maximum number of tags to 50 (CloudFront limit)
5. WHEN more than 50 tags are selected, THE FrontPup SHALL display an error message and not process the invalidation

### Requirement 19: Compatibility with Existing Clear Cache

**User Story:** As a WordPress administrator, I want the new feature to coexist with existing clear cache functionality, so that I don't lose current capabilities.

#### Acceptance Criteria

1. THE FrontPup SHALL maintain the existing "Clear CloudFront Cache" admin bar action
2. THE FrontPup SHALL maintain the existing "Test credentials, clear cache upon saving" checkbox in Clear Cache Settings
3. WHEN the existing clear cache actions are used, THE FrontPup SHALL call Clear_Cache_Function with null parameter
4. THE Clear_Cache_Function SHALL maintain backward compatibility with existing calls that do not pass the $tags parameter
5. THE FrontPup SHALL not modify the behavior of existing clear cache functionality

### Requirement 20: Admin Menu Integration

**User Story:** As a WordPress administrator, I want the new page integrated into the FrontPup menu, so that I can easily find it.

#### Acceptance Criteria

1. THE FrontPup SHALL add the "Clear Cache by Post Type" submenu after the existing FrontPup submenu items
2. THE FrontPup SHALL use the same menu icon and styling as other FrontPup submenu items
3. WHEN the Admin_User hovers over the FrontPup menu, THE FrontPup SHALL display "Clear Cache by Post Type" in the submenu list
4. THE FrontPup SHALL highlight the "Clear Cache by Post Type" submenu item when the Admin_Page is active
5. THE FrontPup SHALL use the slug 'frontpup-clear-cache-by-tag' for the submenu page
