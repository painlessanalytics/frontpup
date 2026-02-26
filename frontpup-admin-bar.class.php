<?php
/**
 * FrontPup Admin Bar
 *
 * @package           FrontPup
 * @author            Angelo Mandato, Painless Analytics
 * @copyright         2026 Painless Analytics
 * @license           GPL-2.0-or-later
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * FrontPup Admin Bar Class
 *
 * Handles the admin bar menu and page rendering for the FrontPup plugin.
 */
class FrontPup_AdminBar {

    private static $instance = null;
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
        // Clear cache ajax action
        add_action( 'wp_ajax_frontpup_clear_cache_action', [$this, 'wp_ajax_frontpup_clear_cache_action']);

        // Where do we want to load the menu bar depending on if admin or public side
        if ( is_admin() ) {
            add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
        } else {
            add_action( 'wp', array( $this, 'wp_loaded' ) );
        }
    }

    /**
     * WP Loaded action to add the admin bar menu
     */
    public function wp_loaded() {

        if ( current_user_can( 'manage_options' ) ) {
            if ( is_admin_bar_showing() ) {
                if ( is_admin() ) {
                    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                } else {
                    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                }

                // Add the FrontPup Toolbar to the Admin bar.
                add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 802 );
            }
        }
    }

    /**
     * Add admin bar menu
     * Include a drop down menu in the admin bar for quick access
     */
    public function admin_bar_menu( $wp_admin_bar ) {
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }

        // Return if the enable clear cache is not set
        $settings = get_option( 'frontpup_clear_cache', [] );
        if( empty($settings['clear_cache_enabled']) ) {
            return;
        }

        $args = array(
            'id'    => 'frontpup-adminbar-menu',
            'title' => '<span class="ab-icon"></span><span class="ab-label">' . __('FrontPup', 'frontpup') . '</span>',
            'href'  => '',
            'meta'  => array( 'class' => 'frontpup-admin-bar-menu' )
        );
        $wp_admin_bar->add_node( $args );

        // Submenu: Cache Settings
        $url = admin_url( 'admin.php?action=frontpup_clear_cache' );
        $nonceUrl = wp_nonce_url( $url, 'frontpup_clear_cache', 'frontpup_clear_cache_nonce' );

        $args = array(
            'id'    => 'frontpup-clear-cache',
            'title' => __('Clear CloudFront Cache', 'frontpup'),
            'href'  => '#',
            'parent'=> 'frontpup-adminbar-menu',
        );
        $wp_admin_bar->add_node( $args );

        // Status menu item:
        $args = array(
            'id'    => 'frontpup-clear-cache-status',
            'title' => __('Clear cache status.', 'frontpup'),
            'parent'=> 'frontpup-adminbar-menu',
        );
        $wp_admin_bar->add_node( $args );
    }

    /**
     * WP AJAX action for clearing cache
     */
    public function wp_ajax_frontpup_clear_cache_action() {

        $settings = get_option( 'frontpup_clear_cache', [] );
        if( empty($settings['clear_cache_enabled']) ) {
            wp_send_json_error( __( 'Clear cache disabled.', 'frontpup' ) );
            return;
        }
        
        // Check user capabilities
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'frontpup' ) );
            return;
        }

        // Check nonce
        if( !check_ajax_referer( 'frontpup_clear_cache_nonce', 'nonce', false ) ) {
            wp_send_json_error( __( 'Invalid security token.', 'frontpup' ) );
            return;
        }

        // Perform cache clearing
        $FrontPupObj = FrontPup::get_instance();
        $clearCacheObj = $FrontPupObj->get_clear_cache_instance();
        $result = $clearCacheObj->clear_cache();

        if ( $result === false ) {
            $error_message = __( 'An error occurred.', 'frontpup' );
            wp_send_json_error( $error_message );
            return;
        } else {
            wp_send_json_success( __( 'Invalidation successful.', 'frontpup' ) );
        }
    }

    /**
     * Enqueue scripts and styles for the admin bar menu
     */
    public function enqueue_scripts() {

        $settings = get_option( 'frontpup_clear_cache', [] );
        if( empty($settings['clear_cache_enabled']) ) {
            return;
        }

        wp_enqueue_style( 'frontpup-admin-bar', plugins_url( '/css/admin-bar.css', __FILE__ ), array(), FRONTPUP_VERSION, 'all' );

        $translation_array = array(
            'processing' => __( 'Processing request...', 'frontpup' ),
            'ajax_url' => admin_url('admin-ajax.php'),
            'security_nonce' => wp_create_nonce('frontpup_clear_cache_nonce'),
        );

        wp_enqueue_script( 'frontpup-admin-bar', plugin_dir_url( __FILE__ ) . 'js/admin-bar.js', [], FRONTPUP_VERSION, true );
        wp_localize_script( 'frontpup-admin-bar', 'frontpupClearCache', $translation_array );
    }
};

// eof