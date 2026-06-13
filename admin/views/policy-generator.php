<?php
/**
 * Policy Generator View
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$_generated_policy = $this->get_generated_policy();
$_form_error       = $this->get_form_error();
$_account_id       = isset( $_POST['frontpup_account_id'] ) ? sanitize_text_field( $_POST['frontpup_account_id'] ) : '';
$_distribution_id  = isset( $_POST['frontpup_distribution_id'] ) ? sanitize_text_field( $_POST['frontpup_distribution_id'] ) : '';
?>
<style>
.frontpup-policy-form .form-field {
	margin-bottom: 18px;
}
.frontpup-policy-form label {
	display: block;
	font-weight: 600;
	margin-bottom: 4px;
}
.frontpup-policy-form input[type="text"] {
	width: 320px;
}
.frontpup-policy-form .description {
	color: #646970;
	font-style: italic;
	margin-top: 4px;
	font-size: 13px;
}
.frontpup-policy-output {
	margin-top: 24px;
}
.frontpup-policy-output textarea {
	width: 100%;
	max-width: 700px;
	font-family: monospace;
	font-size: 13px;
	background: #f6f7f7;
	border: 1px solid #c3c4c7;
	padding: 12px;
	resize: vertical;
	display: block;
}
.frontpup-copy-row {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 10px;
}
.frontpup-copy-row h2 {
	margin: 0;
}
.frontpup-copy-btn {
	background: none;
	border: 1px solid #2271b1;
	border-radius: 3px;
	cursor: pointer;
	padding: 4px 10px;
	color: #2271b1;
	font-size: 13px;
	display: inline-flex;
	align-items: center;
	gap: 4px;
	line-height: 1.6;
}
.frontpup-copy-btn:hover {
	background: #f0f6fc;
	color: #135e96;
	border-color: #135e96;
}
.frontpup-copy-btn .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
}
.frontpup-copy-feedback {
	color: #00a32a;
	font-size: 13px;
}
</style>

<div class="wrap frontpup-settings">
	<h1><?php echo esc_html( $this->page_title ); ?></h1>

	<?php if ( ! empty( $_form_error ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $_form_error ); ?></p></div>
	<?php endif; ?>

	<p><?php esc_html_e( 'Generate an IAM policy that can be attached to a role or user to allow clearing (invalidating) a CloudFront cache.', 'frontpup' ); ?></p>

	<form method="post" action="" class="frontpup-policy-form">
		<?php wp_nonce_field( 'frontpup_policy_generator_action', 'frontpup_policy_generator_nonce' ); ?>

		<div class="form-field">
			<label for="frontpup_account_id">
				<?php esc_html_e( 'AWS Account ID', 'frontpup' ); ?>
				<span style="color:#d63638;" aria-hidden="true">*</span>
			</label>
			<input type="text" id="frontpup_account_id" name="frontpup_account_id"
				value="<?php echo esc_attr( $_account_id ); ?>"
				placeholder="123456789012"
				maxlength="12"
				required />
			<p class="description"><?php esc_html_e( 'Your 12-digit AWS account ID.', 'frontpup' ); ?></p>
		</div>

		<div class="form-field">
			<label for="frontpup_distribution_id">
				<?php esc_html_e( 'Distribution ID', 'frontpup' ); ?>
			</label>
			<input type="text" id="frontpup_distribution_id" name="frontpup_distribution_id"
				value="<?php echo esc_attr( $_distribution_id ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. EDFDVBD6EXAMPLE', 'frontpup' ); ?>" />
			<p class="description"><?php esc_html_e( 'Leave blank to allow access to all distributions in your account.', 'frontpup' ); ?></p>
		</div>

		<?php submit_button( __( 'Generate Policy', 'frontpup' ) ); ?>
	</form>

	<?php if ( $_generated_policy !== null ) : ?>
		<div class="frontpup-policy-output">
			<div class="frontpup-copy-row">
				<h2><?php esc_html_e( 'Generated IAM Policy', 'frontpup' ); ?></h2>
				<button type="button" class="frontpup-copy-btn" id="frontpup-copy-btn"
					title="<?php esc_attr_e( 'Copy to clipboard', 'frontpup' ); ?>">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Copy', 'frontpup' ); ?>
				</button>
				<span class="frontpup-copy-feedback" id="frontpup-copy-feedback" style="display:none;"
					aria-live="polite" aria-atomic="true">
					<?php esc_html_e( 'Copied!', 'frontpup' ); ?>
				</span>
			</div>
			<textarea id="frontpup-policy-output" readonly rows="20"><?php echo esc_textarea( $_generated_policy ); ?></textarea>
			<p style="margin-top: 12px;">
				<a href="https://www.frontpup.com/policy-generator/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Instructions', 'frontpup' ); ?>
				</a>
			</p>
		</div>
		<script>
		document.getElementById('frontpup-copy-btn').addEventListener('click', function() {
			var textarea = document.getElementById('frontpup-policy-output');
			var feedback = document.getElementById('frontpup-copy-feedback');
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText(textarea.value).then(function() {
					feedback.style.display = 'inline';
					setTimeout(function() { feedback.style.display = 'none'; }, 2000);
				});
			} else {
				textarea.select();
				document.execCommand('copy');
				feedback.style.display = 'inline';
				setTimeout(function() { feedback.style.display = 'none'; }, 2000);
			}
		});
		</script>
	<?php endif; ?>
</div>
