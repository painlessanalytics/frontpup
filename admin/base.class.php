<?php
/**
 * FrontPup Admin Base Class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FrontPup_Admin_Base {

  // Settings
  protected $settings = [];  
  protected $settings_key = ''; // Override in child class
  protected $settings_defaults = []; // Override in child class
  protected $page_title = '';
  protected $view = ''; // Override in child class


  // Settings validation fields
  protected $booleanFields = []; // Override in child class
  protected $numericFields = []; // Override in child class
  protected $stringFields = []; // Override in child class


  /**
   * Constructor
   */
  public function __construct() {
      if( !empty($this->settings_key) ) {
      $this->settings = get_option( $this->settings_key, [] );
    }
  }

  /**
   * Set page title
   */
  public function set_page_title( $title ) {
    $this->page_title = $title;
  }

  /**
   * Get plugin settings
   */
  public function get_settings() {
    return $this->settings;
  }

  /**
   * Register Settings
   */
  public function register_settings() {
    if( !empty($this->settings_key) ) {
      register_setting( $this->settings_key . '_group', $this->settings_key, [ $this, 'sanitize_settings' ] );
    }
  }

  /**
   * Sanitize settings before saving
   */
  public function sanitize_settings( $input ) {
    $output = [];

    // boolean values
    foreach ( $this->booleanFields as $field ) {
        $output[$field] = isset( $input[$field] ) ? boolval( $input[$field] ) : 0;
    }

    // Numeric values
    foreach ( $this->numericFields as $field ) {
      if ( isset( $input[$field] ) ) {
        $output[$field] = intval( $input[$field] );
      } else {
        $output[$field] = 0;
      }
    }

    // String values
    foreach ( $this->stringFields as $field ) {
      if ( isset( $input[$field] ) ) {
        $output[$field] = sanitize_text_field( $input[$field] );
      } else {
        $output[$field] = '';
      }
    }

    return $output;
  }

  /**
   * View: Settings Page Content
   */
  public function view() {
    ?>
    <div class="wrap frontpup-settings">
      <h1><?php echo esc_html($this->page_title); ?></h1>
      <?php
        // This is crucial for displaying the "Settings Saved" message
        settings_errors();
      ?>
      
      <form method="post" action="options.php">
          <?php
          settings_fields( $this->settings_key . '_group' );
          do_settings_sections( 'frontpup-plugin' );
          $this->loadSettingsView($this->settings);
          submit_button();
          ?>
      </form>
    </div>
    <?php
  }

  /**
   * Settings Page Content
   */
  public function loadSettingsView($settings = []) {
    foreach( $this->settings_defaults as $key => $value ) {
      if( !isset( $settings[$key] ) ) {
        $settings[$key] = $value;
      }
    }
    include plugin_dir_path( __FILE__ ) . 'views/' . $this->view .'.php';
  }

};

// eof