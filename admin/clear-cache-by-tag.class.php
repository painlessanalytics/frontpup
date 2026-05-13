<?php
/**
 * FrontPup Admin Clear Cache by Post Type Class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FrontPup_Admin_Clear_Cache_By_Tag extends FrontPup_Admin_Base {

	// Settings
	protected $settings_key = ''; // No settings stored for this page
	protected $settings = [];
	protected $settings_defaults = [];
	protected $page_title = '';
	protected $view = 'clear-cache-by-tag-settings';

	// Settings validation fields (not used for this page)
	protected $booleanFields = [];
	protected $numericFields = [];
	protected $stringFields = [];

	/**
	 * View: Display the Clear Cache by Post Type page
	 * 
	 * Overrides parent view() to handle form submission directly
	 * 
	 * @return void
	 */
	public function view() {
		// Process form submission if present
		$this->process_form_submission();

		// Get data for view
		$public_post_types = $this->get_public_post_types();
		$special_tags = $this->get_special_tags();

		// Include view template
		require_once FRONTPUP_PLUGIN_PATH . 'admin/views/' . $this->view . '.php';
	}

	/**
	 * Get all public post types
	 * 
	 * @return array Array of WP_Post_Type objects
	 */
	private function get_public_post_types(): array {
		return get_post_types( ['public' => true], 'objects' );
	}

	/**
	 * Get special cache tags
	 * 
	 * These match the special tags defined in FrontPup::get_cache_tag()
	 * 
	 * @return array Array of special tag strings
	 */
	private function get_special_tags(): array {
		return ['error', 'home', 'search', 'archive', 'author', 'unknown'];
	}

	/**
	 * Validate and sanitize a cache tag value
	 * 
	 * @param string $tag Raw tag value from form
	 * @return string|false Sanitized tag or false if invalid
	 */
	private function validate_tag( string $tag ) {
		// Sanitize
		$tag = sanitize_text_field( $tag );
		
		// Validate format (alphanumeric, hyphens, underscores)
		if ( ! preg_match( '/^[a-z0-9\-_]+$/i', $tag ) ) {
			return false;
		}
		
		// Convert to lowercase for consistency
		$tag = strtolower( $tag );
		
		return $tag;
	}

	/**
	 * Process form submission
	 * 
	 * Collects tags, validates input, calls clear_cache(), and displays feedback
	 * 
	 * @return void
	 */
	private function process_form_submission(): void {
		// Check if form was submitted
		if ( ! isset( $_POST['frontpup_clear_cache_by_tag_nonce'] ) ) {
			return;
		}

		// Verify nonce
		check_admin_referer( 'frontpup_clear_cache_by_tag_action', 'frontpup_clear_cache_by_tag_nonce' );

		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error(
				'frontpup_clear_cache_by_tag',
				'permission_denied',
				__( 'You do not have sufficient permissions to access this page.', 'frontpup' ),
				'error'
			);
			return;
		}

		// Collect tags from form
		$tags = [];
		if ( isset( $_POST['frontpup_tags'] ) && is_array( $_POST['frontpup_tags'] ) ) {
			foreach ( $_POST['frontpup_tags'] as $tag ) {
				$validated_tag = $this->validate_tag( $tag );
				if ( $validated_tag !== false ) {
					$tags[] = $validated_tag;
				}
			}
		}

		// Check tag limit (CloudFront maximum)
		if ( count( $tags ) > 50 ) {
			add_settings_error(
				'frontpup_clear_cache_by_tag',
				'too_many_tags',
				__( 'Maximum 50 tags can be selected at once. Please reduce your selection.', 'frontpup' ),
				'error'
			);
			return;
		}

		// Convert empty array to null (clear entire cache)
		if ( empty( $tags ) ) {
			$tags = null;
		}

		// Debug logging
		if ( defined( 'FRONTPUP_DEBUG' ) && FRONTPUP_DEBUG ) {
			error_log( 'FrontPup Clear Cache by Post Type: Selected tags = ' . print_r( $tags, true ) );
		}

		// Call clear_cache with tags
		$FrontPupObj = FrontPup::get_instance();
        $clear_cache_instance = $FrontPupObj->get_clear_cache_instance();
		$result = $clear_cache_instance->clear_cache( $tags );

		// Display feedback
		if ( $result ) {
			add_settings_error(
				'frontpup_clear_cache_by_tag',
				'cache_cleared',
				__( 'Cache invalidation request completed successfully.', 'frontpup' ),
				'updated'
			);
		} else {
			$error_message = $clear_cache_instance->get_last_error();
			add_settings_error(
				'frontpup_clear_cache_by_tag',
				'cache_clear_failed',
				sprintf(
					__( 'Error occurred while clearing CloudFront cache: %s  Did you enable the `Use cache tags for cache invalidation` option for your distribution?', 'frontpup' ),
					$error_message
				),
				'error'
			);
		}
	}

}

// eof
