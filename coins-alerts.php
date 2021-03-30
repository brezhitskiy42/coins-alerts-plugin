<?php
/**
 * Plugin Name: Coins Alerts
 * Description: Plugin allows website visitors to create custom cryptocurrency prices alerts and be notified by email when specified conditions are met.
 * Text Domain: coins-alerts
 * Domain Path: /lang
 */

// If this file is called directly, abort
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

// Paths to the main plugin file
define( 'COINS_ALERTS_PATH',  plugin_dir_path( __FILE__ ) );
define( 'COINS_ALERTS_URL',  plugin_dir_url( __FILE__ ) );

// Connecting the main class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coin-alert.php';

// Activating the plugin
function ca_activate_coins_alerts() {
  $helper = new CoinsAlerts\CA_Helper();
  $helper->activate();
}

register_activation_hook( __FILE__, 'ca_activate_coins_alerts' );

// Running the admin panel functionality
new CoinsAlerts\CA_Admin();

// Running the basic functionality
new CoinsAlerts\CA_Coin_Alert();