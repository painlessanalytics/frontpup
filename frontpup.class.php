<?php
/**
 * FrontPup class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FrontPup {

    private static $instance = null;
    private $settings = [];

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->settings = get_option( 'frontpup_plugin_settings', [] );
        add_filter( 'send_headers', [ $this, 'send_headers' ] );
    }

    /**
     * Get plugin settings
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * send_headers filter
     */
    public function send_headers() {
        global $wp_query;

        // If headers already sent, do nothing
        if ( headers_sent() ) {
            return;
        }

        // Set the unique visitor cookie for authenticated users (if enabled)
        $this->set_unique_visitor_cookie();

        // If this is 404 page, do not change the cache headers
        if ( is_404() ) {
            if( defined('FRONTPUP_DEBUG') && FRONTPUP_DEBUG ) {
                header( 'X-Front-Pup: 404-page' );
            }
            return;
        }

        // If this is an error page, do not change the cache headers
        if ( is_wp_error( $wp_query ) || is_wp_error( $wp_query->get_queried_object() ) ) {
            if( defined('FRONTPUP_DEBUG') && FRONTPUP_DEBUG ) {
                header( 'X-Front-Pup: error-page' );
            }
            return;
        }

        // Send cache tag header if tag-based caching is enabled
        $clear_cache_settings = get_option( 'frontpup_clear_cache', [] );
        if ( ! empty( $clear_cache_settings['tag_based_caching_enabled'] ) ) {
            $cache_tag = $this->get_cache_tag();
            if ( ! empty( $cache_tag ) ) {
                header( "x-amz-meta-cache-tag: $cache_tag" );
                // Send debug header if FRONTPUP_DEBUG is enabled
                if( defined('FRONTPUP_DEBUG') && FRONTPUP_DEBUG ) {
                    header( "X-Front-Pup-Cache-Tag: $cache_tag" );
                }
            }
        }

        // If the logged in cookie is set
        if( defined('LOGGED_IN_COOKIE') && !empty($_COOKIE[LOGGED_IN_COOKIE]) ) {
            if( defined('FRONTPUP_DEBUG') && FRONTPUP_DEBUG ) {
                header( 'X-Front-Pup: logged-in-cookie' );
            }
            $headers = headers_list();
            foreach ($headers as $header) {
                if (stripos($header, 'Cache-Control:') !== false) {
                    if (stripos($header, 'no-cache') !== false ) {
                        // Cache-Control header already set to no-cache
                        return;
                    }
                    break;
                }
            }
            // Use the WordPress function to send no-cache headers
            nocache_headers();
            return;
        }

        // If cachecontrol is set to anything other than 0, set the appropriate headers
        if( !empty($this->settings['cachecontrol']) ) {
            
            $maxage = $this->settings['maxage'] ?? 31536000; // Default to 1 year
            $smaxage = $this->settings['smaxage'] ?? 31536000; // Default to 1 year
            switch( $this->settings['cachecontrol'] ) {
                case 1: // 'no-cache'
                case 'no-cache':
                    // Use the WordPress function to send no-cache headers
                    header( 'X-Front-Pup: no-cache' );
                    nocache_headers();
                    break;
                case 2: // 'browser only'
                case 'browser-only':
                    header( 'X-Front-Pup: browser-only' );
                    header( "Cache-Control: max-age=$maxage, private" ); // 5 minutes
                    header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $maxage ) . ' GMT' );
                    break;
                case 3: // 'browser and CDN'
                case 'browser-and-cdn':
                    header( 'X-Front-Pup: browser-and-cdn' );
                    if( !empty($this->settings['custom_smaxage_enabled']) && $smaxage != $maxage) {
                        header( "Cache-Control: max-age=$maxage, s-maxage=$smaxage, public" ); // 1 day
                    } else {
                        header( "Cache-Control: max-age=$maxage, public" ); // 1 day    
                    }
                    header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $maxage ) . ' GMT' );
                    break;
                default:
                    // Do nothing
                    
                    break;
            }
        } else {
            if( defined('FRONTPUP_DEBUG') && FRONTPUP_DEBUG ) {
                header( 'X-Front-Pup: none' );
            }
        }
    }

    public function get_clear_cache_instance( $clearCacheSettings = [] ) {
        require_once plugin_dir_path( __FILE__ ) . 'clear-cache.class.php';
        if( empty($clearCacheSettings) ) {
            $clearCacheSettings = get_option( 'frontpup_clear_cache', [] ); // Clear cache settings
        }
        
        if( empty($clearCacheSettings) ) {
            return null;
        }
        return new FrontPup_Clear_Cache( $clearCacheSettings );
    }

    /**
     * Set unique visitor cookie for authenticated users
     * Called during send_headers filter before cache headers are set
     * 
     * @return void
     */
    private function set_unique_visitor_cookie(): void {
        // Check if cache_unique_visitors_enabled setting is truthy, return early if not
        if ( empty( $this->settings['cache_unique_visitors_enabled'] ) ) {
            return;
        }

        // Check if headers already sent using headers_sent(), return early if true
        if ( headers_sent() ) {
            return;
        }

        // Get cookie name from settings with fallback to 'cf_cache'
        $cookie_name = $this->settings['cache_unique_visitors_cookie_name'] ?? 'cf_cache';

        // Check if user is authenticated
        $is_authenticated = defined( 'LOGGED_IN_COOKIE' ) && ! empty( $_COOKIE[LOGGED_IN_COOKIE] );
        
        // Check if commenters feature is enabled and user has comment author cookie
        $is_commenter = ! empty( $this->settings['cache_unique_visitors_commenters_enabled'] ) && 
                        ! empty( $_COOKIE['comment_author_' . COOKIEHASH] );

        // If neither authenticated nor commenter, return early
        if ( ! $is_authenticated && ! $is_commenter ) {
            return;
        }

        // Generate cookie value
        if ( $is_authenticated ) {
            $cookie_value = $this->generate_unique_visitor_value();
            $cookie_expire = 0; // Session cookie for authenticated users
        } else {
            // For commenters, generate a hash based on comment author cookie
            $cookie_value = md5( $_COOKIE['comment_author_' . COOKIEHASH] );
            // Match WordPress comment cookie expiration (YEAR_IN_SECONDS seconds = 365 days)
            $comment_cookie_lifetime = (int) apply_filters( 'comment_cookie_lifetime', YEAR_IN_SECONDS );
            $cookie_expire = time() + $comment_cookie_lifetime;
        }

        // Check if cookie already exists with same value to avoid redundant setcookie calls
        if ( isset( $_COOKIE[$cookie_name] ) && $_COOKIE[$cookie_name] === $cookie_value ) {
            return;
        }

        // Determine secure flag using is_ssl()
        $secure = is_ssl();

        // Call setcookie() with parameters: name, value, expire, '/', '', secure, true (httponly)
        setcookie( $cookie_name, $cookie_value, $cookie_expire, COOKIEPATH, COOKIE_DOMAIN, $secure, true );
    }

    /**
     * Generate unique visitor cookie value
     * Creates a deterministic hash based on user ID and session token
     * 
     * @return string Cookie value (32-character hex string)
     */
    private function generate_unique_visitor_value(): string {
        $user_id = get_current_user_id();
        $session_token = wp_get_session_token();

        // Combine user ID and session token
        $data = $user_id . '|' . $session_token;

        // Generate hash (md5 is sufficient for cache differentiation, not security)
        $hash = md5( $data );

        return $hash;
    }

    /**
     * Get cache tag for current request
     * 
     * Detects the WordPress post type for the current request and returns a sanitized
     * cache tag value suitable for CloudFront's tag-based invalidation feature.
     * 
     * Detection strategy (in priority order):
     * 1. 404 or error pages → return 'error'
     * 2. Homepage (is_home) → return 'home'
     * 3. Search results (is_search) → return 'search'
     * 4. Single post/page/CPT (is_singular) → return post type from queried object
     * 5. Post type archive (is_post_type_archive) → return post type from query var
     * 6. Category/tag/taxonomy archive → return 'archive'
     * 7. Author archive (is_author) → return 'author'
     * 8. Unknown context → return 'unknown'
     * 
     * @return string Sanitized cache tag value, or empty string for 404/error pages
     */
    private function get_cache_tag(): string {
        global $wp_query;

        // Check for 404 pages - no header should be sent
        if ( is_404() ) {
            return 'error';
        }

        // Check for WordPress error pages - no header should be sent
        if ( is_wp_error( $wp_query ) || is_wp_error( $wp_query->get_queried_object() ) ) {
            return 'error';
        }

        // Check for homepage
        if ( is_home() ) {
            return $this->sanitize_cache_tag( 'home' );
        }

        // Check for search results
        if ( is_search() ) {
            return $this->sanitize_cache_tag( 'search' );
        }

        // Check for singular posts/pages/CPTs
        if ( is_singular() ) {
            $queried_object = get_queried_object();
            if ( $queried_object ) {
                $post_type = get_post_type( $queried_object );
                if ( $post_type ) {
                    return $this->sanitize_cache_tag( $post_type );
                }
            }
        }

        // Check for post type archive
        if ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
            if ( $post_type ) {
                // get_query_var can return an array for multiple post types
                if ( is_array( $post_type ) ) {
                    $post_type = reset( $post_type );
                }
                return $this->sanitize_cache_tag( $post_type );
            }
        }

        // Check for category, tag, or taxonomy archives
        if ( is_category() || is_tag() || is_tax() ) {
            return $this->sanitize_cache_tag( 'archive' );
        }

        // Check for author archive
        if ( is_author() ) {
            return $this->sanitize_cache_tag( 'author' );
        }

        // Default fallback for unknown contexts
        return $this->sanitize_cache_tag( 'unknown' );
    }

    /**
     * Sanitize cache tag value for CloudFront compliance
     * 
     * CloudFront tag-based invalidation requires cache tags to meet specific format requirements:
     * - Only lowercase alphanumeric characters, hyphens, and underscores are allowed
     * - Maximum length of 256 characters
     * - Tags are case-insensitive (we normalize to lowercase)
     * 
     * @param string $tag Raw tag value (typically a WordPress post type slug)
     * @return string Sanitized tag value, or 'unknown' if empty after sanitization
     */
    private function sanitize_cache_tag( string $tag ): string {
        // Convert to lowercase
        $tag = strtolower( $tag );
        
        // Remove invalid characters (keep alphanumeric, hyphens, underscores)
        $tag = preg_replace( '/[^a-z0-9\-_]/', '', $tag );
        
        // Truncate to 256 characters
        $tag = substr( $tag, 0, 256 );
        
        // Return 'unknown' if empty after sanitization
        return empty( $tag ) ? 'unknown' : $tag;
    }
};

// eof