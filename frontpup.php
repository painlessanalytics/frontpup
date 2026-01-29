<?php
/**
 * FrontPup
 *
 * @package           FrontPup
 * @author            Painless Analytics
 * @copyright         2025 Painless Analytics
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       FrontPup
 * Plugin URI:        https://www.painlessanalytics.com/frontpup-cloudfront-wordpress-plugin/
 * Description:       FrontPup, your CloudFront companion - optimize your CloudFront distribution for your WordPress website.
 * Version:           1.1
 * Requires at least: 5.5
 * Tested up to:      6.9
 * Requires PHP:      8.1
 * Author:            Painless Analytics
 * Author URI:        https://www.painlessanalytics.com
 * Text Domain:       frontpup
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined('ABSPATH') ) exit;

define('FRONTPUP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
if( !defined('FRONTPUP_VERSION') ) {
    define('FRONTPUP_VERSION', '1.1');
}
if( !defined('FRONTPUP_REGION') ) {
    define('FRONTPUP_REGION', 'us-east-1'); // Default region
}

if( !class_exists('FrontPup') ) {
    require_once FRONTPUP_PLUGIN_PATH . 'frontpup.class.php';
    FrontPup::get_instance();
}

if( is_admin() ) {
    require_once FRONTPUP_PLUGIN_PATH . 'frontpup-admin.class.php';
}

// eof