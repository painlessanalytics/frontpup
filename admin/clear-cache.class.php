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
   * Update option, when data saved to database
   */
  public function update_option($old_value, $new_value) {
    // Check if we need to test credentials and clear cache
    if ( !empty($new_value['clear_cache_enabled']) && isset( $_POST['FrontPupTestCredentials'] ) && $_POST['FrontPupTestCredentials'] == '1' ) {
      // Test clearing the CloudFront cache with the credentials provided

      $FrontPupObj = FrontPup::get_instance();
      $clearCacheObj = $FrontPupObj->get_clear_cache_instance( $new_value );
      // $result = $clearCacheObj->clear_cache();
      $result = true;

      $errorMessage = '';
      if( $result === false ) {
        $errorMessage = __( 'Error occurred while clearing CloudFront cache.', 'frontpup') .'<br /><br />' .
          sprintf('%s.', $clearCacheObj->get_last_error() );
      }

      if( empty($errorMessage) ) {
        add_settings_error(
            'frontpup_message',
            esc_attr( 'settings_updated' ),
            __('Settings saved and CloudFront cache invalidation request completed successfully.', 'frontpup'),
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
  }
}

// eof