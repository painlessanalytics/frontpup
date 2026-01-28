<?php
/** 
 * Cache Control Settings View
 * 
 * $this = FrontPup_Admin_Cache_Control object
 * $settings = array of current settings
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<p><?php echo esc_html(__('The caching settings control how your pages are cached by CloudFront.', 'frontpup')); ?></p>
<p><?php echo esc_html(__('The following settings only apply to public pages.', 'frontpup')); ?></p>
<p><?php echo esc_html(__('Utilize the options below if your `Minimum TTL` cache policy setting in CloudFront is set to 0 seconds.', 'frontpup')); ?></p>
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
                    <label for="cachecontrol-input-none"><?php echo esc_html( 'None', 'frontpup' ); ?></label>
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
                    <label for="cachecontrol-input-nocache"><?php echo esc_html( 'No-cache', 'frontpup' ); ?></label>
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
                    <label for="cachecontrol-input-browser"><?php echo esc_html( 'Browser Cache Only', 'frontpup'  ); ?></label>
					<p id="cachecontrol-input-browser-description">
						<code><?php echo esc_html(__( 'Cache-Control: private, max-age=VALUE', 'frontpup' )); ?></code> 
					</p>
                    <p style="margin-top:10px;">
                        <?php echo esc_html(__( 'CloudFront will not cache content, only the browser.', 'frontpup' )); ?>
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
                    <label for="cachecontrol-input-browser-cloudfront"><?php echo esc_html( 'Browser and CloudFront Cache', 'frontpup' ); ?> <span class="recommended"><?php echo esc_html('Recommended', 'frontpup'); ?></span></label>
                    
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
<style>
.frontpup-settings .recommended {
    background-color: #0073aa;
    color: #fff;
    font-size: 0.75em;
    font-weight: bold;
    line-height: 1;
    margin-left: 8px;
    padding: 2px 6px;
    text-transform: uppercase;
    border-radius: 3px;
}
</style>
