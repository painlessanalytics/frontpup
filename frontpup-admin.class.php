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
        $this->admin_views['welcome'] = new FrontPup_Admin_Welcome(); // __('Welcome', 'frontpup') );
        $this->admin_views['cache-control'] = new FrontPup_Admin_Cache_Control(); // __('Cache Control Settings', 'frontpup') );
        $this->admin_views['clear-cache'] = new FrontPup_Admin_Clear_Cache(); // __('Clear Cache Settings', 'frontpup') );
       
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'admin_init' ] );
        
    }

    /**
     * Add top-level admin menu
     */
    public function register_menu() {

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
        

        foreach( $this->admin_views as $view ) {
            $view->register_settings();
        }
    }
}

FrontPup_Admin::get_instance();

// eof