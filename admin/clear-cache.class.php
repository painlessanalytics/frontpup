<?php
/**
 * Clear Cache Admin Class
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'base.class.php';

class FrontPup_Admin_Clear_Cache extends FrontPup_Admin_Base {

    protected $settings_key = 'frontpup_clear_cache';
    protected $settings = [];
    protected $settings_defaults = [
      'clear_cache_enabled' => 0,
      'distribution_id' => '',
      'credentials_mode' => 'policy', // 'policy' or 'wpconfig' or 'database'
      'access_key_id' => '',
      'secret_access_key' => '',
    ];

    protected $booleanFields = ['clear_cache_enabled'];
    protected $numericFields = [];
    protected $stringFields = ['distribution_id', 'credentials_mode', 'access_key_id', 'secret_access_key'];
    protected $view = 'clear-cache-settings';

    /**
     * Constructor
     */
    // public function __construct() {
    //     parent::__construct();
    // }

    /**
     * Saving settings
     * We also want to handle if the variable FrontPupTestCredentials is set to test credentials and clear cache
     */
    public function sanitize_settings( $input ) {
      $output = parent::sanitize_settings( $input );

      // Check if we need to test credentials and clear cache
      if ( isset( $_POST['FrontPupTestCredentials'] ) && $_POST['FrontPupTestCredentials'] == '1' ) {
        // Test clearing the cache with the credentials provided

        // Temporary simulate success or failure (FOR TESTING)
        $errorMessage = '';
        if( rand() % 2 == 0 ) {
          //$errorMessage = 'Simulated error: Unable to connect to AWS CloudFront.';
          $errorMessage = 'Error occurred while clearing cache. Please check your AWS credentials and distribution ID.';
        }
        
        if( empty($errorMessage) ) {
          add_settings_error(
              'frontpup_message',
              esc_attr( 'settings_updated' ),
              __('Settings saved and cache cleared successfully.', 'frontpup'),
              'updated'
          );
        } else {
          add_settings_error(
              'frontpup_message',
              esc_attr( 'settings_updated' ),
              $errorMessage,
              'error'
          );
          add_settings_error(
              'frontpup_message',
              esc_attr( 'settings_updated' ),
              __('Settings saved.', 'frontpup'),
              'updated'
          );
          
        }

      }

      return $output;
    }
}

// eof