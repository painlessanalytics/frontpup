<?php
/**
 * FrontPup Admin Policy Generator Class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'base.class.php';

class FrontPup_Admin_Policy_Generator extends FrontPup_Admin_Base {

	protected $settings_key = '';
	protected $settings = [];
	protected $settings_defaults = [];
	protected $page_title = '';
	protected $view = 'policy-generator';

	protected $booleanFields = [];
	protected $numericFields = [];
	protected $stringFields = [];

	private $generated_policy = null;
	private $form_error = '';

	/**
	 * View: Display the Policy Generator page
	 *
	 * @return void
	 */
	public function view() {
		$this->process_form_submission();
		require_once FRONTPUP_PLUGIN_PATH . 'admin/views/' . $this->view . '.php';
	}

	/**
	 * Process form submission and generate IAM policy JSON
	 *
	 * @return void
	 */
	private function process_form_submission(): void {
		if ( ! isset( $_POST['frontpup_policy_generator_nonce'] ) ) {
			return;
		}

		check_admin_referer( 'frontpup_policy_generator_action', 'frontpup_policy_generator_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->form_error = __( 'You do not have sufficient permissions to access this page.', 'frontpup' );
			return;
		}

		$account_id      = sanitize_text_field( $_POST['frontpup_account_id'] ?? '' );
		$distribution_id = sanitize_text_field( $_POST['frontpup_distribution_id'] ?? '' );

		if ( empty( $account_id ) ) {
			$this->form_error = __( 'AWS Account ID is required.', 'frontpup' );
			return;
		}

		// AWS Account IDs are always 12 digits
		if ( ! preg_match( '/^\d{12}$/', $account_id ) ) {
			$this->form_error = __( 'AWS Account ID must be exactly 12 digits.', 'frontpup' );
			return;
		}

		if ( ! empty( $distribution_id ) ) {
			// CloudFront distribution IDs are alphanumeric; input is accepted in any case and normalized to uppercase in the ARN
			if ( ! preg_match( '/^[A-Z0-9]+$/i', $distribution_id ) ) {
				$this->form_error = __( 'Distribution ID must contain only letters and numbers (e.g. EDFDVBD6EXAMPLE).', 'frontpup' );
				return;
			}
			$resource = 'arn:aws:cloudfront::' . $account_id . ':distribution/' . strtoupper( $distribution_id );
		} else {
			$resource = 'arn:aws:cloudfront::' . $account_id . ':distribution/*';
		}

		$encoded = json_encode(
			[
				'Version'   => '2012-10-17',
				'Statement' => [
					[
						'Effect'   => 'Allow',
						'Action'   => [
							'cloudfront:CreateInvalidation',
							'cloudfront:GetInvalidation',
							'cloudfront:ListInvalidations',
						],
						'Resource' => $resource,
					],
				],
			],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		if ( $encoded === false ) {
			$this->form_error = __( 'Failed to generate policy. Please try again.', 'frontpup' );
			return;
		}

		$this->generated_policy = $encoded;
	}

	/**
	 * Expose generated policy and form error to the view template
	 */
	public function get_generated_policy(): ?string {
		return $this->generated_policy;
	}

	public function get_form_error(): string {
		return $this->form_error;
	}

}

// eof
