<?php
/**
 * Clear Cache Admin Class
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'base.class.php';

class FrontPup_Admin_Clear_Cache extends FrontPup_Admin_Base {

    protected $settings_key = 'frontpup_plugin_clear_cache';
    protected $settings = [];
    protected $settings_defaults = [
        // No settings for now
    ];

    protected $booleanFields = [];
    protected $numericFields = [];
    protected $view = 'clear-cache-settings';

    /**
     * Constructor
     */
    // public function __construct() {
    //     parent::__construct();
    // }
}

// eof