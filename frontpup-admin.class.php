<?php
/**
 * FrontPup Admin Class
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'admin/cache-control.class.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/clear-cache.class.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/welcome.class.php';

class FrontPup_Admin {

    private static $instance = null;
    private $admin_views = [];

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
        // Create the admin views and their controller classes
        $this->admin_views['welcome'] = new FrontPup_Admin_Welcome(); // __('Welcome', 'frontpup') );
        $this->admin_views['cache-control'] = new FrontPup_Admin_Cache_Control(); // __('Cache Control Settings', 'frontpup') );
        $this->admin_views['clear-cache'] = new FrontPup_Admin_Clear_Cache(); // __('Clear Cache Settings', 'frontpup') );
       
        // Admin hooks
        add_action( 'admin_menu', [$this, 'admin_menu'] );
        add_action( 'admin_init', [$this, 'admin_init'] );
        add_action( 'admin_bar_menu', [$this, 'admin_bar_menu'], 801 );

        // Clear cache ajax action
        add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'] );
        add_action( 'wp_ajax_frontpup_clear_cache_action', [$this, 'wp_ajax_frontpup_clear_cache_action']);
    }

    /**
     * Add top-level admin menu
     */
    public function admin_menu() {

        $icon_url = 'dashicons-cloud-upload';
        //$icon_url = plugin_dir_url( __FILE__ ) . 'images/frontpup-icon-16.png';
        //echo $icon_url;

        add_menu_page(
            'Welcome',
            'FrontPup',
            'manage_options',
            'frontpup-plugin', // menu slug
            [$this->admin_views['welcome'], 'view'],
            $icon_url
        );

        add_submenu_page(
            'frontpup-plugin',
            'Welcome',
            'Welcome',
            'manage_options',
            'frontpup-plugin', // menu slug
            [$this->admin_views['welcome'], 'view']
        );

        add_submenu_page(
            'frontpup-plugin',
            'Cache Settings',
            'Cache Settings',
            'manage_options',
            'frontpup-cache-settings', // menu slug
            [$this->admin_views['cache-control'], 'view']
        );

        add_submenu_page(
            'frontpup-plugin',
            'Clear Cache Settings',
            'Clear Cache Settings',
            'manage_options',
            'frontpup-clear-cache', // menu slug
            [$this->admin_views['clear-cache'], 'view']
        );
    }

    /**
     * Register plugin settings
     */
    public function admin_init() {
        // Set the page titles
        $this->admin_views['welcome']->set_page_title( __('Welcome to FrontPup', 'frontpup') );
        $this->admin_views['cache-control']->set_page_title( __('Cache Settings', 'frontpup') );
        $this->admin_views['clear-cache']->set_page_title( __('Clear Cache Settings', 'frontpup') );

        // Register settings for each admin view
        foreach( $this->admin_views as $view ) {
            $view->register_settings();
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
            'id'    => 'frontpup_admin_menu',
            'title' => 'FrontPup',
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
            'parent'=> 'frontpup_admin_menu',
        );
        $wp_admin_bar->add_node( $args );
    }

    /**
     * Admin enqueue scripts
     */
    public function admin_enqueue_scripts( $hook ) {

        // Determine if we have the clear cache enabled
        $settings = get_option( 'frontpup_clear_cache', [] );
        if( empty($settings['clear_cache_enabled']) ) {
            return;
        }

        $translation_array = array(
            'dismiss' => __( 'Dismiss', 'frontpup' ),
            'ajax_url' => admin_url('admin-ajax.php'),
            'security_nonce' => wp_create_nonce('frontpup_clear_cache_nonce'),
        );

        wp_enqueue_script( 'frontpup-clear-cache-script', plugin_dir_url( __FILE__ ) . 'admin/js/clear-cache.js', [], FRONTPUP_VERSION, true );
        wp_localize_script( 'frontpup-clear-cache-script', 'frontpupClearCache', $translation_array );
    }

    /**
     * WP AJax action for clearing cache
     */
    public function wp_ajax_frontpup_clear_cache_action() {

        $settings = get_option( 'frontpup_clear_cache', [] );
        if( empty($settings['clear_cache_enabled']) ) {
            wp_send_json_error( __( 'This option is not available.', 'frontpup' ) );
            return;
        }
        
        // Check user capabilities
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have sufficient permissions to access this action.', 'frontpup' ) );
            return;
        }

        // Check nonce TODO:
        if( !check_ajax_referer( 'frontpup_clear_cache_nonce', 'nonce', false ) ) {
            wp_send_json_error( __( 'Invalid security token sent.', 'frontpup' ) );
            return;
        }

        // Perform cache clearing
        $FrontPupObj = FrontPup::get_instance();
        $clearCacheObj = $FrontPupObj->get_clear_cache_instance();
        $result = $clearCacheObj->clear_cache();

        if ( $result === false ) {
            $error_message = sprintf( '%s.', $clearCacheObj->get_last_error() == '' ? __( 'Unknown error occurred', 'frontpup' ) : $clearCacheObj->get_last_error() );
            wp_send_json_error( __( 'Error occurred while clearing cache: ', 'frontpup' ) . $error_message );
            return;
        } else {
            wp_send_json_success( __( 'Cache cleared successfully.', 'frontpup' ) );
        }
    }
}

FrontPup_Admin::get_instance();

// eof