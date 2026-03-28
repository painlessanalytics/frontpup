<?php
/**
 * Lightweight AWS CloudFront WordPress Class
 *
 * Extends LightAWS_CloudFront to provide CloudFront cache-management API calls
 * using the built-in http methods in WordPress.
 *
 * Supported operations:
 *   - createInvalidation()  – submit a new cache invalidation batch
 *   - getInvalidation()     – retrieve the status of an existing invalidation
 *   - listInvalidations()   – list invalidations for a distribution
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/lightaws-cloudfront.php';
require_once __DIR__ . '/lightaws-http-wp-trait.php';

/**
 * LightAWS_CloudFront_WP
 */
class LightAWS_CloudFront_WP extends LightAWS_CloudFront {
    use LightAWS_HTTP_WP_Trait;

}

// eof