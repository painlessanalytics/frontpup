<?php
/**
 * Cache Control Admin Class
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'base.class.php';

class FrontPup_Admin_Cache_Control extends FrontPup_Admin_Base {

    protected $settings_key = 'frontpup_plugin_settings';
    protected $settings = [];
    protected $settings_defaults = [
        'maxage' => 31536000, // 1 year
        'smaxage' => 31536000, // 1 year
        'custom_smaxage_enabled' => 0,
        'cachecontrol' => 0,
    ];

    protected $booleanFields = ['custom_smaxage_enabled'];
    protected $numericFields = ['maxage', 'smaxage', 'cachecontrol'];
    protected $view = 'cache-control-settings';

    /**
     * Constructor
     */
    // public function __construct() {
    //     parent::__construct();
    // }
}

// eof