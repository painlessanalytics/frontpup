<?php
/**
 * FrontPup Admin Class
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FrontPup_Admin {

    private static $instance = null;
    private $settings_key = 'frontpup_plugin_settings';
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
        $this->settings = get_option( $this->settings_key, [] );

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Add top-level admin menu
     */
    public function register_menu() {

        add_options_page(
            'FrontPup Settings',       // Page title
            'FrontPup',                      // Menu title
            'manage_options',                 // Capability required to access
            'frontpup-plugin',        // Menu slug
            [ $this, 'settings_page' ]  // Callback function to render the page content
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {

        register_setting(
            'frontpup_plugin_settings_group',
            $this->settings_key,
            [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ]
        );
    }
 
    /**
     * Settings content HTML
     */
    public function settings_content( ) {
        // Set default values if not set
        $settings = $this->settings;
        if( !isset( $settings['custom_smaxage_enabled'] ) ) {
            $settings['custom_smaxage_enabled'] = 0; // Default value
        }
        if( !isset( $settings['smaxage'] ) ) {
            $settings['smaxage'] = 31536000; // Default value
        }
        if( !isset( $settings['maxage'] ) ) {
            $settings['maxage'] = 31536000; // Default value
        }
        if( !isset( $settings['cachecontrol'] ) ) {
            $settings['cachecontrol'] = 0; // Default value
        }
?>
<p><?php echo esc_html(__('The caching settings control how your pages are cached by CloudFront.', 'frontpup')); ?></p>
<p><?php echo esc_html(__('The following settings only apply to public pages.', 'frontpup')); ?></p>
<p><?php echo esc_html(__('Enable the options below if your `Minimum TTL` cache policy setting in CloudFront is set to 0 seconds.', 'frontpup')); ?></p>
<h2><?php echo esc_html(__('Page Caching Settings', 'frontpup')); ?></h2>
<table class="form-table permalink-structure" role="presentation">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html(__( 'Cache-Control', 'frontpup' )); ?></th>
	<td>
		<fieldset class="structure-selection">
			<legend class="screen-reader-text"><?php echo 'test'; ?></legend>
			<div class="row">
				<input id="cachecontrol-input-none"
					name="<?php echo esc_attr($this->settings_key); ?>[cachecontrol]" aria-describedby="cachecontrol-input-none-description"
					type="radio" value="0"
					<?php checked( $settings['cachecontrol'], 0 ); ?>
				/>
				<div>
                    <label for="cachecontrol-input-none"><?php esc_html( 'None', 'frontpup' ); ?></label>
					<p id="cachecontrol-input-none-description">
						<?php echo esc_html(__( 'No Cache-Control header is added.', 'frontpup' )); ?>
					</p>
				</div>
			</div><!-- .row -->

            <legend class="screen-reader-text"><?php echo 'test'; ?></legend>
			<div class="row">
				<input id="cachecontrol-input-nocache"
					name="<?php echo esc_attr($this->settings_key); ?>[cachecontrol]" aria-describedby="cachecontrol-input-nocache-description"
					type="radio" value="1"
					<?php checked( $settings['cachecontrol'], 1 ); ?>
				/>
				<div>
                    <label for="cachecontrol-input-nocache"><?php esc_html( 'No-cache', 'frontpup' ); ?></label>
					<p id="cachecontrol-input-nocache-description">
						<code><?php echo esc_html(__( 'Cache-Control: no-cache', 'frontpup' )); ?></code> 
					</p>
                    <p style="margin-top:10px;">
                        <?php echo esc_html(__( 'No-cache headers.', 'frontpup' )); ?>
                    </p>
				</div>
			</div><!-- .row -->

            <legend class="screen-reader-text"><?php echo 'test'; ?></legend>
			<div class="row">
				<input id="cachecontrol-input-browser"
					name="<?php echo esc_attr($this->settings_key); ?>[cachecontrol]" aria-describedby="cachecontrol-input-browser-description"
					type="radio" value="2"
					<?php checked( $settings['cachecontrol'], 2 ); ?>
                    
				/>
				<div>
                    <label for="cachecontrol-input-browser"><?php esc_html( 'Browser Cache Only', 'frontpup'  ); ?></label>
					<p id="cachecontrol-input-browser-description">
						<code><?php echo esc_html(__( 'Cache-Control: private, max-age=VALUE', 'frontpup' )); ?></code> 
					</p>
                    <p style="margin-top:10px;">
                        <?php echo esc_html(__( 'CloudFront will not cache content, only the browser..', 'frontpup' )); ?>
                    </p>
				</div>
			</div><!-- .row -->

            <legend class="screen-reader-text"><?php echo 'test'; ?></legend>
			<div class="row">
				<input id="cachecontrol-input-browser-cloudfront"
					name="<?php echo esc_attr($this->settings_key); ?>[cachecontrol]" aria-describedby="cachecontrol-input-browser-cloudfront-description"
					type="radio" value="3"
					<?php checked( $settings['cachecontrol'], 3 ); ?>
				/>
				<div>
                    <label for="cachecontrol-input-browser-cloudfront"><?php esc_html( 'Browser and CloudFront Cache', 'frontpup' ); ?></label>
					<p id="cachecontrol-input-browser-cloudfront-description">
                        <code><?php echo esc_html(__( 'Cache-Control: public, max-age=VALUE', 'frontpup' )); ?></code>
					</p>
                    <p style="margin-top:10px;">
                        <?php echo esc_html(__( 'CloudFront and browser caching headers are added.', 'frontpup' )); ?>
                    </p>
				</div>
			</div><!-- .row -->
</fieldset><!-- .structure-selection -->
	</td>
</tr>
</tbody>
</table>

<div id="frontpup-ttl-input-container">
<table class="form-table permalink-structure" role="presentation">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html(__( 'Max Age', 'frontpup' )); ?></th>
	<td>
        <p>
            <input name="<?php echo esc_attr($this->settings_key); ?>[maxage]" id="frontpup-maxage"
                type="number" value="<?php echo esc_attr( $settings['maxage'] ); ?>"
                min="0" max="31536000"
                aria-describedby="permalink-custom" class="medium-text"
            />
        </p>
        <p>
            <?php echo esc_html(__( 'Specify the max age (in seconds) to cache content.', 'frontpup' )); ?>
        </p>
        <p id="frontpup-smaxage-checkbox">
        <label>
            <input 
                type="hidden" 
                name="<?php echo esc_attr($this->settings_key); ?>[custom_smaxage_enabled]"
                value="0" />
            <input
                type="checkbox"
                name="<?php echo esc_attr($this->settings_key); ?>[custom_smaxage_enabled]" 
                value="1"
                onclick="document.getElementById('frontpup-smaxage-input-container').style.display = this.checked ? '' : 'none';"
                <?php checked( isset( $settings['custom_smaxage_enabled'] ) && $settings['custom_smaxage_enabled'] ); ?> 
            />
            <?php echo esc_html(__( 'Set a specific max age for CloudFront caching.', 'frontpup' )); ?>
            
        </label>
        </p>
	</td>
</tr>
</tbody>
</table>
<div id="frontpup-smaxage-input-container" style="<?php echo ( isset( $settings['custom_smaxage_enabled'] ) && $settings['custom_smaxage_enabled'] ) ? '' : 'display:none;'; ?>">
<table class="form-table" role="presentation">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html(__( 'CloudFront Max Age', 'frontpup' )); ?></th>
	<td>
        <p>
            <input name="<?php echo esc_attr($this->settings_key); ?>[smaxage]" id="frontpup-smaxage"
                type="number" value="<?php echo esc_attr( $settings['smaxage'] ); ?>"
                min="0" max="31536000"
                aria-describedby="permalink-custom" class="medium-text"
            />
        </p>
        <p>
            <?php echo esc_html(__( 'Specify the max age (in seconds) for CloudFront to cache content.', 'frontpup' )); ?>
        </p>
        <p>
            <?php echo esc_html(__( 'Adds s-maxage=VALUE to Cache-Control header.', 'frontpup' )); ?>
        </p>
    </td>
</tr>
</tbody>
</table>
</div><!-- end of frontpup-smaxage-input-container -->
</div><!-- end of frontpup-ttl-input-container -->
<?php
    }

    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings( $input ) {
        $output = [];

        // boolean values
        foreach ( ['custom_smaxage_enabled'] as $field ) {
            $output[$field] = isset( $input[$field] ) ? boolval( $input[$field] ) : 0;
        }

        // Numeric values
        foreach ( ['maxage', 'smaxage', 'cachecontrol'] as $field ) {
            if ( isset( $input[$field] ) ) {
                $output[$field] = intval( $input[$field] );
            } else {
                $output[$field] = 0;
            }
        }

        return $output;
    }

    /**
     * Settings page HTML
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('FrontPup, your CloudFront companion', 'frontpup')); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'frontpup_plugin_settings_group' );
                do_settings_sections( 'frontpup-plugin' );
                $this->settings_content();
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

FrontPup_Admin::get_instance();