<?php

if (!defined('ABSPATH')) exit;

/**
 * License manager module
 */
function gwdp_updater_utility() {
    $prefix = 'GWDP_';
    $settings = [
        'prefix' => $prefix,
        'get_base' => GWDP_PLUGIN_BASENAME,
        'get_slug' => GWDP_PLUGIN_DIR,
        'get_version' => GWDP_BUILD,
        'get_api' => 'https://download.geekcodelab.com/',
        'license_update_class' => $prefix . 'Update_Checker'
    ];

    return $settings;
}

// register_activation_hook(__FILE__, 'gwdp_updater_activate');
function gwdp_updater_activate() {

    // Refresh transients
    delete_site_transient('update_plugins');
    delete_transient('gwdp_plugin_updates');
    delete_transient('gwdp_plugin_auto_updates');
}

require_once(GWDP_PLUGIN_DIR_PATH . 'updater/class-update-checker.php');