<?php
/** 
 * Clear Cache Settings View
 * 
 * $this = Frontpup_Admin object
 * $settings = array of current settings
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<table class="form-table">
  <tr>
    <th scope="row"><?php echo esc_html__('Clear Cache', 'frontpup'); ?></th>
    <td>
      <label>
        <input type="checkbox"
                id="csd_enable_feature"
                name="<?php echo esc_attr($this->settings_key); ?>[clear_cache_enabled]"
                value="1"
                onclick="document.getElementById('frontpup-credentials-input-container').style.display = this.checked ? '' : 'none';"
                <?php checked( isset( $settings['clear_cache_enabled'] ) && $settings['clear_cache_enabled'] ); ?>
        />
        <?php echo esc_html__('Enable Clear Cache', 'frontpup'); ?>
      </label>
      <p>
        <?php echo esc_html( __('The Clear CloudFront Cache feature will invalidate the entire CloudFront cache when selected.', 'frontpup') ); ?>
      </p>
    </td>
  </tr>
</table>

<div id="frontpup-credentials-input-container" style="<?php echo ( isset( $settings['clear_cache_enabled'] ) && $settings['clear_cache_enabled'] ) ? '' : 'display:none;'; ?>">
<table class="form-table" id="frontpup-credentials">
  <tr class="csd-credentials">
    <th scope="row"><?php echo esc_html__('CloudFront Distribution ID', 'frontpup'); ?></th>
    <td>
      <input type="text"
              name="<?php echo esc_attr($this->settings_key); ?>[distribution_id]"
              value="<?php echo esc_attr($settings['distribution_id'] ?? ''); ?>"
              placeholder=""
              class="regular-text"

      />
      <p>
        <?php echo esc_html(__('Example: d111111abcdef8', 'frontpup')); ?>
      </p>
    </td>
  </tr>

  <tr>
    <th scope="row"><?php echo esc_html__('Credentials Method', 'frontpup'); ?></th>
    <td>
      <fieldset>
        <label>
          <input type="radio"
                  name="<?php echo esc_attr($this->settings_key); ?>[credentials_mode]"
                  value="policy"
                  <?php checked( isset( $settings['credentials_mode'] ) && $settings['credentials_mode'] === 'policy' ); ?>
                  onclick="frontpup_select_credentials_mode();"
          />
          <?php echo esc_html__('IAM Role assigned to EC2 Instances, ECS tasks, and EKS pods', 'frontpup'); ?>
          <span class="recommended"><?php echo esc_html('Recommended', 'frontpup'); ?></span>
        </label>
        <p>
          <?php echo esc_html(__('This is the recommended method, no keys are stored on the server.', 'frontpup')); ?>
        </p>
        <br />

        <label>
          <input type="radio"
                  name="<?php echo esc_attr($this->settings_key); ?>[credentials_mode]"
                  value="wpconfig"
                  <?php checked( isset( $settings['credentials_mode'] ) && $settings['credentials_mode'] === 'wpconfig' ); ?>
                  onclick="frontpup_select_credentials_mode();"
              />
          <?php echo esc_html__('IAM Access Keys saved in wp-config.php', 'frontpup'); ?>
        </label>
        <p>
          <?php echo esc_html(__('This method uses AWS Access Keys saved in the wp-config.php file.', 'frontpup')); ?>
        </p>
        <br />

        <label>
          <input type="radio"
                  name="<?php echo esc_attr($this->settings_key); ?>[credentials_mode]"
                  value="database"
                  <?php checked( isset( $settings['credentials_mode'] ) && $settings['credentials_mode'] === 'database' ); ?>
                  onclick="frontpup_select_credentials_mode();"
          />
          <?php echo esc_html__('IAM Access Keys provided below', 'frontpup'); ?>
        </label>
        <p>
          <?php echo esc_html(__('This method allows you to provide AWS Access Keys directly in the settings below.', 'frontpup')); ?>
        </p>
      </fieldset>
    </td>
  </tr>
</table>

<table class="form-table" id="frontpup-wp-config" style="<?php echo ( isset( $settings['credentials_mode'] ) && $settings['credentials_mode'] === 'wpconfig' ) ? '' : 'display:none;'; ?>">
  <tr class="csd-credentials">
    <th scope="row">Credentials in wp-config.php</th>
    <td>
      <p>
        <?php echo esc_html(__('Add the following lines to your wp-config.php.', 'frontpup')); ?>
      </p>
      <div class="code-block" style="background:#FCFCFC; margin: 10px 0; padding: 0 20px; border:1px solid #ddd; border-radius:8px; max-width: 600px;">
<pre>define('FRONTPUP_ACCESS_KEY_ID', '&lt;YOUR-ACCESS-KEY-ID&gt;');
define('FRONTPUP_SECRET_ACCESS_KEY', '&lt;YOUR-SECRET-ACCESS-KEY&gt;');
</pre>
      </div>
    </td>
  </tr>
</table>

<table class="form-table" id="frontpup-access-keys" style="<?php echo ( isset( $settings['credentials_mode'] ) && $settings['credentials_mode'] === 'database' ) ? '' : 'display:none;'; ?>">
  <tr class="csd-credentials">
    <th scope="row"><?php echo esc_html__('AWS Access Key ID', 'frontpup'); ?></th>
    <td>
      <input type="text"
              name="<?php echo esc_attr($this->settings_key); ?>[access_key_id]"
              value="<?php echo esc_attr($settings['access_key_id'] ?? ''); ?>"
              class="regular-text">
      <p>
        <?php echo esc_html(__('Example: AKIAIOSFODNN7EXAMPLE', 'frontpup')); ?>
      </p>
    </td>
  </tr>

  <tr class="csd-credentials">
    <th scope="row"><?php echo esc_html__('AWS Secret Access Key', 'frontpup'); ?></th>
    <td>
      <input type="password"
              name="<?php echo esc_attr($this->settings_key); ?>[secret_access_key]"
              value="<?php echo esc_attr($settings['secret_access_key'] ?? ''); ?>"
              class="regular-text">
      <p>
        <?php echo esc_html(__('Example: wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', 'frontpup')); ?>
      </p>
    </td>
  </tr>
</table>

<p style="margin-bottom: 0;"><label>
  <input type="checkbox"
          name="FrontPupTestCredentials"
          value="1"
  />
  <?php echo esc_html__('Test credentials, clear cache upon saving', 'frontpup'); ?>
</label>
</p>
</div>

<script>
function frontpup_select_credentials_mode() {
    var mode = document.querySelector('input[name="<?php echo esc_js($this->settings_key); ?>[credentials_mode]"]:checked').value;
    document.getElementById('frontpup-wp-config').style.display = (mode === 'wpconfig') ? '' : 'none';
    document.getElementById('frontpup-access-keys').style.display = (mode === 'database') ? '' : 'none';
}
</script>
<style>
.frontpup-settings .recommended {
    background-color: #135e96;
    border-color: #135e96;
    color: #fff;
    font-size: 0.75em;
    font-weight: bold;
    line-height: 1;
    margin-left: 8px;
    padding: 2px 6px;
    text-transform: uppercase;
    border-radius: 3px;
}
.frontpup-settings .submit {
    margin-top: 0;
}

</style>