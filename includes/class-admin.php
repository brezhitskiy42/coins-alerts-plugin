<?php

namespace CoinsAlerts;

// If this file is called directly, abort
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

// Class for adding functionality to the admin panel
if ( ! class_exists( 'CA_Admin' ) ) {
  class CA_Admin {
    
    public function __construct() {
      
      add_action( 'admin_enqueue_scripts', [ $this, 'loadStylesScripts' ] );
      add_action( 'admin_menu', [ $this, 'addMenuPages' ] );
      add_action( 'admin_init', [ $this, 'registerSettings' ] );
      add_action( 'pre_update_option_ca_option', [ $this, 'updateMyAlertsSlug' ], 10, 2 );
      add_action( 'admin_init', [ $this, 'manageAlertsReqHandler' ] );
      
    }
    
    // Loading styles and scripts
    public function loadStylesScripts() {

      wp_register_style( 'ca-datatables', COINS_ALERTS_URL . 'public/vendor/datatables/css/jquery.dataTables.css' );
      wp_register_style( 'ca-datatables-r', COINS_ALERTS_URL . 'public/vendor/datatables/css/responsive.dataTables.min.css' );
      wp_register_style( 'ca-admin', COINS_ALERTS_URL . 'public/css/admin.css' );
      wp_register_script( 'ca-datatables', COINS_ALERTS_URL . 'public/vendor/datatables/js/jquery.dataTables.min.js', [ 'jquery' ], CA_Coin_Alert::PLUGIN_VERSION, true );
      wp_register_script( 'ca-datatables-r', COINS_ALERTS_URL . 'public/vendor/datatables/js/dataTables.responsive.min.js', [ 'jquery' ], CA_Coin_Alert::PLUGIN_VERSION, true );
      wp_register_script( 'ca-admin', COINS_ALERTS_URL . 'public/js/admin.js', [ 'jquery' ], CA_Coin_Alert::PLUGIN_VERSION, true );
      
      wp_enqueue_style( 'ca-datatables' );
      wp_enqueue_style( 'ca-datatables-r' );
      wp_enqueue_style( 'ca-admin' );
      wp_enqueue_script( 'ca-datatables' );
      wp_enqueue_script( 'ca-datatables-r' );
      wp_enqueue_script( 'ca-admin' );
      
    }
    
    // Registering pages in the admin panel
    public function addMenuPages() {
      
      add_menu_page( esc_html__( 'Coins alerts', 'coins-alerts' ), esc_html__( 'Coins alerts', 'coins-alerts' ), 'manage_options', 'manage-alerts', [ $this, 'showManageAlertsPage' ], COINS_ALERTS_URL . '/icon.png' );
      $id1 = add_submenu_page( 'manage-alerts', esc_html__( 'Manage alerts', 'coins-alerts' ), esc_html__( 'Manage alerts', 'coins-alerts' ), 'manage_options', 'manage-alerts', [ $this, 'showManageAlertsPage' ] );
      $id2 = add_submenu_page( 'manage-alerts', esc_html__( 'Settings', 'coins-alerts' ), esc_html__( 'Settings', 'coins-alerts' ), 'manage_options', 'coins-alerts-settings', [ $this, 'showSettingsPage' ] );
    }
    
    // Settings registering
    public function registerSettings() {
      
      register_setting( 'ca_option_group', 'ca_option', [ $this, 'sanitizeOption' ] );
      
      add_settings_section( 'ca_main_section', esc_html__( 'Main settings', 'coins-alerts' ), '', 'coins-alerts-settings' );
      
      add_settings_field( 'currency', esc_html__( 'Currency', 'coins-alerts' ), [ $this, 'fillCurrencyField' ], 'coins-alerts-settings', 'ca_main_section' );
      add_settings_field( 'confirm_templ', esc_html__( 'Alert confirmation template', 'coins-alerts' ), [ $this, 'fillConfirmTemplField' ], 'coins-alerts-settings', 'ca_main_section' );
      add_settings_field( 'notific_templ', esc_html__( 'Alert notification template', 'coins-alerts' ), [ $this, 'fillNotificTemplField' ], 'coins-alerts-settings', 'ca_main_section' );
      add_settings_field( 'my_alerts_slug', esc_html__( 'My Alerts page slug', 'coins-alerts' ), [ $this, 'fillMyAlertsSlugField' ], 'coins-alerts-settings', 'ca_main_section' );
      add_settings_field( 'from', esc_html__( 'From email', 'coins-alerts' ), [ $this, 'fillFromHeaderField' ], 'coins-alerts-settings', 'ca_main_section' );
      add_settings_field( 'cron_key', esc_html__( 'Cron command', 'coins-alerts' ), [ $this, 'fillCronKeyField' ], 'coins-alerts-settings', 'ca_main_section' );

    }
    
    // Updating My Alerts slug
    public function updateMyAlertsSlug( $new_value ) {
      
      wp_update_post( [ 'ID' => get_option( 'ca_my_alerts_id' ), 'post_name' => $new_value['my_alerts_slug'] ] );
      
      return $new_value;
      
    }
    
    // Showing the Manage alerts page
    public function showManageAlertsPage() {
      
      $all_alerts = CA_Helper::getAllAlerts();
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/manage-alerts.php';
      echo ob_get_clean();
      
    }
    
    // Showing the Settings page
    public function showSettingsPage() {
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/settings.php';
      echo ob_get_clean();
      
    }
    
    // Currency field output
    public function fillCurrencyField() {
      
      $currency_list = CA_Helper::getCurrencyList();

      $ca_option = get_option( 'ca_option' );
      $currency = $ca_option['currency'];
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/currency-field.php';
      echo ob_get_clean();
      
    }
    
    // Cron key field output
    public function fillCronKeyField() {
      
      $cron_key = CA_Helper::getCronKey();
      $cron_url = CA_Helper::getCronUrl( $cron_key );
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/cron-key-field.php';
      $content = ob_get_clean();
      
      $content = str_replace( [ '%cron_key%', '%cron_url%' ], [ $cron_key, $cron_url ], $content );
      
      echo $content;
      
    }
    
    // Alert confirmation template field output
    public function fillConfirmTemplField() {
      
      $confirm_templ = CA_Helper::getConfirmTempl();
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/confirm-templ-field.php';
      $content = ob_get_clean();
      
      $content = str_replace( '%confirm_templ%', $confirm_templ, $content );
      
      echo $content;
      
    }
    
    // Alert notification template field output
    public function fillNotificTemplField() {
      
      $notific_templ = CA_Helper::getNotificTempl();
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/notific-templ-field.php';
      $content = ob_get_clean();
      
      $content = str_replace( '%notific_templ%', $notific_templ, $content );
      
      echo $content;
      
    }
    
    // My Alerts slug field output
    public function fillMyAlertsSlugField() {
      
      $my_alerts_slug = CA_Helper::getMyAlertsSlug();
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/my-alerts-slug-field.php';
      $content = ob_get_clean();
      
      $content = str_replace( '%my_alerts_slug%', $my_alerts_slug, $content );
      
      echo $content;
      
    }
    
    // From header field output
    public function fillFromHeaderField() {
      
      $from_header = CA_Helper::getFromHeader();
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/admin/from-header-field.php';
      $content = ob_get_clean();
      
      $content = str_replace( '%from_header%', $from_header, $content );
      
      echo $content;
      
    }
    
    // Checking the transferred settings
    public function sanitizeOption( $value ) {
      
      $ca_option = get_option( 'ca_option' );
      
      $cron_key = $value['cron_key'];
      $confirm_templ = $value['confirm_templ'];
      $notific_templ = $value['notific_templ'];
      $my_alerts_slug = $value['my_alerts_slug'];
      $from_header = $value['from'];
      
      if ( empty( $cron_key ) || empty( $confirm_templ ) || empty( $notific_templ ) || empty( $my_alerts_slug ) || empty( $from_header ) ) {
        add_settings_error( 'ca_option', 'ca-option', 'Empty value.' );
        return $ca_option;
      }
      
      return $value;
      
    }
    
    // The request handler to the Manage Alerts page
    public function manageAlertsReqHandler() {
      
      global $pagenow;
      
      if ( 'admin.php' != $pagenow ) {
        return;
      }
      
      if ( isset( $_GET['page'] ) && 'manage-alerts' == $_GET['page'] ) {
        $this->createUsersCSVFile();
      }
      
      if ( isset( $_POST['export'] ) ) {
        $this->sendUsersCSVFile();
      }
      
      if ( isset( $_GET['alert-id'] ) ) {
        $this->deactivateAlert();
      }
      
    }
    
    // Creating a csv file with email addresses of users
    public function createUsersCSVFile() {
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $emails = $wpdb->get_results(
        "SELECT DISTINCT email FROM $table_name"
      );
      
      $emails_list = [];
      
      if ( ! $emails ) {
        $emails_list[][] = 'Email';
      } else {
        
        $i = 0;
        foreach ( $emails as $email ) {

          $i++;

          if ( 1 == $i ) {
            $emails_list[][] = 'Email';
          }

          $emails_list[][] = $email->email;

        }
        
      }
      
      $fp = fopen( COINS_ALERTS_PATH . 'config/users.csv', 'w' );
      
      foreach ( $emails_list as $email ) {
        fputcsv( $fp, $email );
      }
      
      fclose( $fp );
      
    }
    
    // Sending a csv file with email addresses of users
    public function sendUsersCSVFile() {
      
      $file = COINS_ALERTS_PATH . 'config/users.csv';
      
      if( ! file_exists( $file ) ) {
        return;
      }
      
      $finfo = finfo_open( FILEINFO_MIME_TYPE );
      header( 'Content-Type: ' . finfo_file( $finfo, $file ) );
      finfo_close( $finfo );
      
      header( 'Content-Disposition: attachment; filename=' . basename( $file ) );
      header( 'Expires: 0' );
      header( 'Cache-Control: must-revalidate' );
      header( 'Pragma: public' );
      header( 'Content-Length: ' . filesize( $file ) );
      
      if ( ob_get_length() > 0 ) { 
        ob_clean();
      }
      flush();
      readfile( $file );
      exit;
      
    }
    
    // Alert deactivation
    public function deactivateAlert() {
      
      $alert_id = $_GET['alert-id'];
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $wpdb->update( 
        $table_name,
        [ 'status' => 'inactive' ],
        [ 'id' => $alert_id ]
      );
      
    }
    
  }
}