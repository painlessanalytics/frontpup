# Requirements Document

## Introduction

This document specifies the requirements for adding tag-based caching to the FrontPup WordPress plugin. Amazon CloudFront released a feature called "Tag-based invalidation in Amazon CloudFront" that allows cache invalidation based on custom cache tags sent via the `x-amz-meta-cache-tag` header. This feature will enable FrontPup to automatically tag cached content based on WordPress post types, allowing for more granular cache invalidation strategies.

## Glossary

- **FrontPup**: The WordPress plugin that integrates with AWS CloudFront for cache management
- **CloudFront**: Amazon's content delivery network (CDN) service
- **Cache_Tag**: A custom identifier sent via HTTP header to categorize cached content
- **Post_Type**: WordPress content type (e.g., 'post', 'page', 'product')
- **Settings_Page**: The "Clear Cache Settings" admin page in WordPress
- **Response_Header**: HTTP header sent from WordPress to CloudFront with the response
- **Admin_User**: WordPress user with 'manage_options' capability
- **Tag_Based_Caching_Setting**: Boolean setting that enables/disables the tag-based caching feature

## Requirements

### Requirement 1: Enable Tag-Based Caching Feature

**User Story:** As a WordPress administrator, I want to enable tag-based caching for my CloudFront distribution, so that I can use CloudFront's tag-based invalidation feature.

#### Acceptance Criteria

1. THE Settings_Page SHALL display a checkbox labeled "Tag-based Caching"
2. WHEN the Admin_User checks the "Tag-based Caching" checkbox, THE Settings_Page SHALL save the Tag_Based_Caching_Setting as enabled
3. WHEN the Admin_User unchecks the "Tag-based Caching" checkbox, THE Settings_Page SHALL save the Tag_Based_Caching_Setting as disabled
4. THE Settings_Page SHALL display a description stating "The plugin will send the x-amz-meta-cache-tag header with the post type"
5. THE Settings_Page SHALL persist the Tag_Based_Caching_Setting value across page reloads

### Requirement 2: Send Cache Tag Headers

**User Story:** As a WordPress administrator, I want the plugin to automatically send cache tag headers based on post type, so that CloudFront can categorize cached content.

#### Acceptance Criteria

1. WHEN Tag_Based_Caching_Setting is enabled AND a WordPress page is requested, THE FrontPup SHALL send the Response_Header "x-amz-meta-cache-tag" with the Post_Type value
2. WHEN Tag_Based_Caching_Setting is disabled, THE FrontPup SHALL NOT send the "x-amz-meta-cache-tag" Response_Header
3. WHEN the Post_Type cannot be determined, THE FrontPup SHALL send the Response_Header "x-amz-meta-cache-tag" with the value "unknown"
4. THE FrontPup SHALL send the Response_Header before the response is sent to the client
5. FOR ALL standard WordPress Post_Types (post, page, attachment, revision, nav_menu_item), THE FrontPup SHALL send the corresponding Post_Type value in the Response_Header
6. FOR ALL custom Post_Types registered via register_post_type, THE FrontPup SHALL send the corresponding Post_Type value in the Response_Header

### Requirement 3: Settings Page Integration

**User Story:** As a WordPress administrator, I want the tag-based caching option to be clearly visible and documented, so that I understand how to use the feature.

#### Acceptance Criteria

1. THE Settings_Page SHALL display the "Tag-based Caching" checkbox in the "Clear Cache Settings" section
2. THE Settings_Page SHALL position the "Tag-based Caching" option after the "Enable Clear Cache" option
3. WHEN the Admin_User hovers over the description text, THE Settings_Page SHALL display the full description without truncation
4. THE Settings_Page SHALL use the same visual styling as other checkbox options on the page
5. THE Settings_Page SHALL include the description text immediately below the checkbox label

### Requirement 4: Settings Persistence

**User Story:** As a WordPress administrator, I want my tag-based caching settings to be saved reliably, so that the feature works consistently.

#### Acceptance Criteria

1. WHEN the Admin_User saves the Settings_Page, THE FrontPup SHALL store the Tag_Based_Caching_Setting in the WordPress database
2. THE FrontPup SHALL store the Tag_Based_Caching_Setting in the 'frontpup_clear_cache' option
3. WHEN the Settings_Page loads, THE FrontPup SHALL retrieve the Tag_Based_Caching_Setting from the WordPress database
4. THE FrontPup SHALL sanitize the Tag_Based_Caching_Setting as a boolean value before saving
5. WHEN the Tag_Based_Caching_Setting is not set, THE FrontPup SHALL default to disabled (false)

### Requirement 5: Header Format Compliance

**User Story:** As a WordPress administrator, I want the cache tag headers to be formatted correctly, so that CloudFront can process them properly.

#### Acceptance Criteria

1. THE FrontPup SHALL send the Response_Header name as "x-amz-meta-cache-tag" in lowercase
2. THE FrontPup SHALL send the Response_Header value as a string containing only alphanumeric characters, hyphens, and underscores
3. WHEN the Post_Type contains characters other than alphanumeric, hyphens, or underscores, THE FrontPup SHALL sanitize the value by removing invalid characters
4. THE FrontPup SHALL limit the Response_Header value to 256 characters maximum
5. WHEN the Post_Type value exceeds 256 characters, THE FrontPup SHALL truncate it to 256 characters

### Requirement 6: Compatibility with Existing Features

**User Story:** As a WordPress administrator, I want tag-based caching to work alongside existing FrontPup features, so that I don't lose current functionality.

#### Acceptance Criteria

1. WHEN Tag_Based_Caching_Setting is enabled, THE FrontPup SHALL continue to send Cache-Control headers as configured
2. WHEN Tag_Based_Caching_Setting is enabled, THE FrontPup SHALL continue to support cache invalidation operations
3. WHEN the logged in cookie is set, THE FrontPup SHALL send the "x-amz-meta-cache-tag" Response_Header before sending no-cache headers
4. WHEN the page is a 404 error, THE FrontPup SHALL NOT send the "x-amz-meta-cache-tag" Response_Header
5. WHEN the page is a WordPress error, THE FrontPup SHALL NOT send the "x-amz-meta-cache-tag" Response_Header

### Requirement 7: Post Type Detection

**User Story:** As a WordPress administrator, I want the plugin to accurately detect the post type for all content, so that cache tags are correct.

#### Acceptance Criteria

1. WHEN a single post is displayed, THE FrontPup SHALL determine the Post_Type from the queried object
2. WHEN an archive page is displayed, THE FrontPup SHALL determine the Post_Type from the post type archive
3. WHEN the homepage is displayed, THE FrontPup SHALL send the Response_Header "x-amz-meta-cache-tag" with the value "home"
4. WHEN a search results page is displayed, THE FrontPup SHALL send the Response_Header "x-amz-meta-cache-tag" with the value "search"
5. WHEN a category or tag archive is displayed, THE FrontPup SHALL send the Response_Header "x-amz-meta-cache-tag" with the value "archive"
6. WHEN an author archive is displayed, THE FrontPup SHALL send the Response_Header "x-amz-meta-cache-tag" with the value "author"
