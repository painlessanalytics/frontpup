<?php
/**
 * Clear Cache by Post Type Settings View
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<style>
.frontpup-two-column-layout {
	display: flex;
	gap: 40px;
	margin: 20px 0;
}

.frontpup-column {
	flex: 1;
}

.frontpup-column h2 {
	font-size: 1.1em;
	margin-bottom: 10px;
}

.frontpup-column label {
	display: block;
	margin-bottom: 8px;
}

.frontpup-column-1 {
	max-width: 200px;
}

.frontpup-notice {
	background: #f0f0f1;
	border-left: 4px solid #72aee6;
	padding: 12px;
	margin: 20px 0;
	font-style: italic;
}
</style>

<div class="wrap frontpup-settings">
	<h1><?php echo esc_html( $this->page_title ); ?></h1>
	<?php settings_errors( 'frontpup_clear_cache_by_tag' ); ?>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'frontpup_clear_cache_by_tag_action', 'frontpup_clear_cache_by_tag_nonce' ); ?>
		
		<div class="frontpup-two-column-layout">
			<div class="frontpup-column frontpup-column-1">
				<h2><?php echo esc_html__( 'Public Post Types', 'frontpup' ); ?></h2>
				<?php foreach ( $public_post_types as $post_type ) : ?>
					<label>
						<input type="checkbox" name="frontpup_tags[]" value="<?php echo esc_attr( $post_type->name ); ?>" />
						<?php echo esc_html( $post_type->label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			
			<div class="frontpup-column frontpup-column-1">
				<h2><?php echo esc_html__( 'Special Tags', 'frontpup' ); ?></h2>
				<?php foreach ( $special_tags as $tag ) : ?>
					<label>
						<input type="checkbox" name="frontpup_tags[]" value="<?php echo esc_attr( $tag ); ?>" />
						<?php echo esc_html( ucfirst( $tag ) ); 
						if( $tag == 'error' )
							echo ' / '. esc_html__('404', 'frontpup');
						?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
<style>
.submit {
	margin-top: 0 !important;
}
</style>
		<p style="margin-bottom: 0;"><i>
			<?php echo esc_html__( 'When no tags above are selected the entire cache will be cleared.', 'frontpup' ); ?>
		</i></p>
		<?php submit_button( __( 'Submit', 'frontpup' ) ); ?>
		
	</form>
</div>