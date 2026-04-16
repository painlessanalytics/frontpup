# Requirements Document

## Introduction

The Cache Unique Visitors feature enables CloudFront to cache different versions of pages for authenticated vs. anonymous users by setting a unique cookie value when a user is signed in to WordPress. This improves cache hit rates while maintaining personalized content for logged-in users.

## Glossary

- **Cache_Unique_Visitors_Feature**: The system component that manages the unique visitor cookie functionality
- **Settings_Manager**: The FrontPup_Admin_Cache_Control class that manages Cache Settings page configuration
- **Cookie_Handler**: The component responsible for setting and managing the unique visitor cookie
- **CloudFront**: AWS CloudFront CDN service that caches content based on cache policies
- **Cache_Policy**: CloudFront configuration that defines which cookies are included in the cache key
- **Authenticated_User**: A WordPress user who is currently signed in (has LOGGED_IN_COOKIE set)
- **Anonymous_User**: A visitor who is not signed in to WordPress

## Requirements

### Requirement 1: Enable/Disable Cache Unique Visitors Feature

**User Story:** As a WordPress site administrator, I want to enable or disable the Cache Unique Visitors feature, so that I can control whether CloudFront caches different versions for authenticated users.

#### Acceptance Criteria

1. THE Settings_Manager SHALL display a toggle switch for enabling/disabling the Cache Unique Visitors feature on the Cache Settings page
2. WHEN the toggle is disabled, THE Cookie_Handler SHALL NOT set any unique visitor cookie
3. WHEN the toggle is enabled, THE Cookie_Handler SHALL set the unique visitor cookie for authenticated users
4. THE Settings_Manager SHALL store the toggle state in the frontpup_plugin_settings WordPress option
5. THE Settings_Manager SHALL preserve the toggle state across page reloads and WordPress sessions

### Requirement 2: Configure Cookie Name

**User Story:** As a WordPress site administrator, I want to specify a custom cookie name, so that I can match my CloudFront cache policy configuration.

#### Acceptance Criteria

1. THE Settings_Manager SHALL display a text input field for the cookie name on the Cache Settings page
2. WHEN the cookie name field is left blank, THE Cookie_Handler SHALL use "cf_cache" as the default cookie name
3. WHEN a custom cookie name is provided, THE Cookie_Handler SHALL use the provided name
4. THE Settings_Manager SHALL sanitize the cookie name input to allow only alphanumeric characters, underscores, and hyphens
5. THE Settings_Manager SHALL store the cookie name in the frontpup_plugin_settings WordPress option

### Requirement 3: Display Configuration Help Text

**User Story:** As a WordPress site administrator, I want to see help text explaining the CloudFront configuration requirement, so that I understand how to configure my CloudFront cache policy.

#### Acceptance Criteria

1. THE Settings_Manager SHALL display help text below the cookie name input field
2. THE help text SHALL explain that enabling this option requires configuring a CloudFront cache policy with the cookie name as part of the cache key
3. THE help text SHALL be visible whenever the Cache Unique Visitors feature toggle is enabled

### Requirement 4: Set Unique Cookie for Authenticated Users

**User Story:** As a WordPress site administrator, I want a unique cookie set for signed-in users, so that CloudFront can differentiate cached content between authenticated and anonymous visitors.

#### Acceptance Criteria

1. WHEN the Cache Unique Visitors feature is enabled AND a user is authenticated, THE Cookie_Handler SHALL set the unique visitor cookie
2. WHEN a user is not authenticated, THE Cookie_Handler SHALL NOT set the unique visitor cookie
3. THE Cookie_Handler SHALL set the cookie value to a unique identifier per user session
4. THE Cookie_Handler SHALL set the cookie before HTTP headers are sent to the browser
5. WHEN the cookie is already set for an authenticated user, THE Cookie_Handler SHALL preserve the existing cookie value for the duration of the session

### Requirement 5: Cookie Attributes and Lifetime

**User Story:** As a WordPress site administrator, I want the unique visitor cookie to have appropriate security attributes and lifetime, so that it works correctly with CloudFront and protects user privacy.

#### Acceptance Criteria

1. THE Cookie_Handler SHALL set the cookie path to "/" to apply site-wide
2. THE Cookie_Handler SHALL set the cookie to expire at the end of the browser session (session cookie)
3. WHERE the site uses HTTPS, THE Cookie_Handler SHALL set the Secure flag on the cookie
4. THE Cookie_Handler SHALL set the HttpOnly flag to prevent JavaScript access
5. THE Cookie_Handler SHALL set the SameSite attribute to "Lax" to balance security and functionality

### Requirement 6: Cookie Value Generation

**User Story:** As a WordPress site administrator, I want the cookie value to be unique per user session, so that CloudFront can maintain separate cache entries for different authenticated users.

#### Acceptance Criteria

1. THE Cookie_Handler SHALL generate a unique cookie value for each authenticated user session
2. THE cookie value SHALL be deterministic based on the user ID and session token
3. THE cookie value SHALL be different for different users
4. THE cookie value SHALL remain constant for the same user during their session
5. THE cookie value SHALL NOT contain personally identifiable information in plain text

### Requirement 7: Integration with Existing Cache Control Logic

**User Story:** As a WordPress site administrator, I want the Cache Unique Visitors feature to work seamlessly with existing cache control settings, so that my cache configuration remains consistent.

#### Acceptance Criteria

1. THE Cookie_Handler SHALL execute during the send_headers filter hook
2. THE Cookie_Handler SHALL execute before Cache-Control headers are set
3. WHEN the Cache Unique Visitors feature is disabled, THE existing cache control behavior SHALL remain unchanged
4. WHEN the Cache Unique Visitors feature is enabled, THE existing Cache-Control header logic SHALL continue to function normally
5. THE Cookie_Handler SHALL NOT interfere with the LOGGED_IN_COOKIE detection logic for no-cache headers

### Requirement 8: Settings Persistence and Validation

**User Story:** As a WordPress site administrator, I want my Cache Unique Visitors settings to be validated and persisted correctly, so that the feature works reliably.

#### Acceptance Criteria

1. THE Settings_Manager SHALL validate the cookie name contains only alphanumeric characters, underscores, and hyphens
2. WHEN an invalid cookie name is provided, THE Settings_Manager SHALL sanitize it to remove invalid characters
3. THE Settings_Manager SHALL save the Cache Unique Visitors settings using the WordPress Settings API
4. THE Settings_Manager SHALL load the Cache Unique Visitors settings from the frontpup_plugin_settings option on page load
5. THE Settings_Manager SHALL apply default values when settings are not yet configured
