<?php
/**
 * Welcome Admin Class
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'base.class.php';

class FrontPup_Admin_Welcome extends FrontPup_Admin_Base {

    protected $settings_key = 'frontpup_plugin_welcome';
    protected $settings = [];

    protected $view = 'welcome';

    /**
   * View: Settings Page Content
   */
  public function view() {
    ?>
    <div class="wrap frontpup-admin-welcome">
      <div class="frontpup-welcome-heading">
        <div>
            <h1 class="frontpup-mb-0"><?php echo esc_html($this->page_title); ?></h1>
            <p class="frontpup-mt-0"><?php echo esc_html(__('Your AWS CloudFront companion', 'frontpup')); ?></p>
        </div>
        <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../images/frontpup-logo-225-160.png' ); ?>" alt="FrontPup Logo" class="frontpup-welcome-logo Xalignright frontpup-me-3 frontpup-dashicon" />
      </div>
      <div>
      <?php
        // This is crucial for displaying the "Settings Saved" message
        settings_errors();
        // Display content of welcome page
        $this->loadSettingsView($this->settings);
        ?>
      </div>
    </div>
    <?php
  }
}

// eof