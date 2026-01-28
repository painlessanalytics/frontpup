<?php
/** 
 * Clear Cache Settings View
 * 
 * $this = Frontpup_Admin object
 * $settings = array of current settings
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<h3 class="frontpup-mb-0"><?php echo esc_html( __('CloudFront Status', 'frontpup') ); ?></h3>
<?php
// CloudFront is detected
if( !empty($_SERVER['HTTP_X_AMZ_CF_ID'])) {
?>
<p class="frontpup-green">
<span class="dashicons dashicons-yes frontpup-dashicon frontpup-dashicon-md"></span>
<strong class="frontpup-cf-detected"><?php echo esc_html( __('CloudFront Detected', 'frontpup') ); ?></strong>
</p>
<div>
  <p><?php echo esc_html(__('Congratulations! Your site is being served through AWS CloudFront. You may utilize the features provided by this plugin.', 'frontpup')); ?></p>
</div>
<?php
} else {
?>
<p class="frontpup-red">
<span class="dashicons dashicons-warning frontpup-dashicon frontpup-dashicon-md"></span>
<strong class="frontpup-cf-detected"><?php echo esc_html( __('CloudFront NOT Detected', 'frontpup') ); ?></strong>
</p>
<div>
  <p><?php echo esc_html(__('It appears that your site is not being served through AWS CloudFront. FrontPup requires CloudFront to function properly.', 'frontpup')); ?></p>
  <a href="https://aws.amazon.com/cloudfront/" target="_blank"><?php echo esc_html(__('Learn more', 'frontpup')); ?></a>
</div>
<?php
}
?>
<div class="frontpup-mt-4 frontpup-bl">
  <h3 class="frontpup-mb-0"><?php echo esc_html( __('Configure FrontPup', 'frontpup') ); ?></h3>
  <ul>
    <li><strong><a href="<?php echo esc_attr(admin_url('admin.php?page=frontpup-cache-settings')); ?>"><?php echo esc_html( __('Cache Settings', 'frontpup') ); ?></a></strong></li>
    <li><strong><a href="<?php echo esc_attr(admin_url('admin.php?page=frontpup-clear-cache')); ?>"><?php echo esc_html( __('Clear Cache Settings', 'frontpup') ); ?></a></strong></li>
  </ul>
</div>


<style>
.frontpup-welcome-heading {
    display: flex;       /* Enables flexbox layout */
    align-items: center; /* Vertically centers the items */
    gap: 15px;           /* Adds space between the image and the h1 (optional) */
}

.frontpup-welcome-heading img {
    /* Optional: set a specific size for the image */
    width: auto;
    height: 75px;
}
.frontpup-welcome-heading h1 {
    font-size: 2.4em; /* Remove default margin */
    font-weight: bold;
}
.frontpup-welcome-heading p {
    font-size: 1.2em; /* Remove default margin */
    color: #555; /* Optional: a lighter color for the subtitle */
}

.frontpup-mt-0 {
  margin-top: 0;
}
.frontpup-mb-0 {
  margin-bottom: 0;
}
.frontpup-mt-4 {
  margin-top: 48px;
}
.frontpup-mb-4 {
  margin-bottom: 48px;
}
.frontpup-mt-5 {
  margin-top: 60px;
}
.frontpup-mb-5 {
  margin-bottom: 60px;
}
.frontpup-dashicon {
  vertical-align: middle;
  display: inline-block;
}
.frontpup-dashicon-md {
  vertical-align: middle;
  display: inline-block;
  font-size: 32px;
  height: 32px;
  width: 32px;
}
.frontpup-dashicon-lg {
  vertical-align: middle;
  display: inline-block;
  font-size: 64px;
  height: 64px;
  width: 64px;
}
.frontpup-green {
    color: #28a745;
    color: #00a32a;
}
.frontpup-red {
    color: #dc3545;
    color: #e02424;
}
.frontpup-cf-detected {
    font-size: 24px;
    vertical-align: middle;
}
.frontpup-admin-welcome h1 {
    margin-bottom: 0;

}
.frontpup-welcome-heading {
    margin-top: 0;
    margin-bottom: 50px;
}
.frontpup-bl {

}
.frontpup-bl ul li {
    list-style-type: disc; /* Use 'disc' for standard bullets, 'circle' or 'square' for alternatives */
    margin-left: 20px; /* Adds space for the bullet to appear */
    list-style-position: outside; /* Position the bullet outside the text block */
}
</style>