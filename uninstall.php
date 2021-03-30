<?php

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  exit;
}

// Connecting the required classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-helper.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coin-alert.php';

// Removing the plugin
$helper = new CoinsAlerts\CA_Helper();
$helper->delete();