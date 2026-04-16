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

        // Set unique visitor cookie for authenticated users (if enabled)
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
        $is_commentor = ! empty( $this->settings['cache_unique_visitors_commenters_enabled'] ) && 
                        ! empty( $_COOKIE['comment_author_' . COOKIEHASH] );

        // If neither authenticated nor commentor, return early
        if ( ! $is_authenticated && ! $is_commentor ) {
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
            $cookie_expire = time() + YEAR_IN_SECONDS;
        }

        // Check if cookie already exists with same value to avoid redundant setcookie calls
        if ( isset( $_COOKIE[$cookie_name] ) && $_COOKIE[$cookie_name] === $cookie_value ) {
            return;
        }

        // Determine secure flag using is_ssl()
        $secure = is_ssl();

        // Call setcookie() with parameters: name, value, expire, '/', '', secure, true (httponly), 'Lax' (samesite)
        setcookie( $cookie_name, $cookie_value, $cookie_expire, '/', '', $secure, true, 'Lax' );
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
};

// eof