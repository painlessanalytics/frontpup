<?php
/*
* Uninstall for FrontPup Pages plugin
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die('Access denied');
}

/**
 * class FrontPupUninstall
 */
class FrontPupUninstall
{
    /**
     * FrontPupUninstall constructor.
     */
    public function __construct()
    {
        // Uninstall stuff here
    }
}

new FrontPupUninstall();

// eof