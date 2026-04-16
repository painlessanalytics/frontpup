<?php
/**
 * Cache Control Admin Class
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'base.class.php';

class FrontPup_Admin_Cache_Control extends FrontPup_Admin_Base {

    protected $settings_key = 'frontpup_plugin_settings';
    protected $settings = [];
    protected $settings_defaults = [
        'maxage' => 31536000, // 1 year
        'smaxage' => 31536000, // 1 year
        'custom_smaxage_enabled' => 0,
        'cachecontrol' => 0,
        'cache_unique_visitors_enabled' => 0,
        'cache_unique_visitors_cookie_name' => 'cf_cache',
        'cache_unique_visitors_commenters_enabled' => 0,
    ];

    protected $booleanFields = ['custom_smaxage_enabled', 'cache_unique_visitors_enabled', 'cache_unique_visitors_commenters_enabled'];
    protected $numericFields = ['maxage', 'smaxage', 'cachecontrol'];
    protected $stringFields = ['cache_unique_visitors_cookie_name'];
    protected $view = 'cache-control-settings';

    /**
     * Constructor
     */
    // public function __construct() {
    //     parent::__construct();
    // }

    /**
     * Sanitize settings before saving
     * Overrides parent to add custom cookie name validation
     * 
     * @param array $input Raw input from form submission
     * @return array Sanitized settings
     */
    public function sanitize_settings( $input ) {
        // Call parent sanitize_settings to handle standard field sanitization
        $output = parent::sanitize_settings( $input );

        // Add custom sanitization for cache_unique_visitors_cookie_name
        if ( isset( $output['cache_unique_visitors_cookie_name'] ) ) {
            // Remove any characters that are not alphanumeric, underscore, or hyphen
            $sanitized_cookie_name = preg_replace( '/[^a-zA-Z0-9_-]/', '', $output['cache_unique_visitors_cookie_name'] );
            
            // If sanitized cookie name is empty, set to default 'cf_cache'
            if ( empty( $sanitized_cookie_name ) ) {
                $sanitized_cookie_name = 'cf_cache';
            }
            
            $output['cache_unique_visitors_cookie_name'] = $sanitized_cookie_name;
        }

        return $output;
    }
}

// eof