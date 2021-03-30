<?php

namespace CoinsAlerts;

// If this file is called directly, abort
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

// Loading helper classes
require_once plugin_dir_path( __FILE__ ) . 'class-helper.php';
require_once plugin_dir_path( __FILE__ ) . 'class-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'class-cron.php';
require_once plugin_dir_path( __FILE__ ) . 'class-admin.php';

// Main plugin class
if ( ! class_exists( 'CA_Coin_Alert' ) ) {
  class CA_Coin_Alert {
    
    const PLUGIN_NAME = 'Coins Alerts';
    const PLUGIN_VERSION = '1.0.1';
    const MIN_PHP_VERSION = '5.5.0';
    const MY_ALERTS_SLUG = 'my-alerts';
    
    // Here the main functionality of the plugin is launched
    public function __construct() {
      
      $this->enqueueStylesScripts();
      $this->myAlertsReq();
      $this->addAJAXHandlers();
      
      add_action( 'plugins_loaded', [ $this, 'loadTextDomain' ] );
      
    }
    
    // Connecting styles and scripts
    public function enqueueStylesScripts() {
      add_action( 'wp_enqueue_scripts', [ $this, 'loadStylesScripts' ] );
    }
    
    // Adding a request handler to the My Alerts page
    public function myAlertsReq() {
      
      add_filter( 'query_vars', [ $this, 'addMyAlertsQueryVars' ] );
      add_action( 'wp', [ $this, 'myAlertsReqHandler' ] );
      
    }
      
    // Adding AJAX handlers
    public function addAJAXHandlers() {
      if ( defined( 'DOING_AJAX' ) ) {
        
        add_action( 'wp_ajax_add_alert', [ $this, 'addAlertHandler' ] );
        add_action( 'wp_ajax_nopriv_add_alert', [ $this, 'addAlertHandler' ] );
        
        add_action( 'wp_ajax_deactivate_alert', [ $this, 'deactivateAlertHandler' ] );
        add_action( 'wp_ajax_nopriv_deactivate_alert', [ $this, 'deactivateAlertHandler' ] );
        
        add_action( 'wp_ajax_remove_alert', [ $this, 'removeAlertHandler' ] );
        add_action( 'wp_ajax_nopriv_remove_alert', [ $this, 'removeAlertHandler' ] );
        
        add_action( 'wp_ajax_get_current_value', [ $this, 'getCurrentValue' ] );
        add_action( 'wp_ajax_nopriv_get_current_value', [ $this, 'getCurrentValue' ] );
        
      }
    }
    
    // Loading text domain
    public function loadTextDomain() {
      load_plugin_textdomain( 'coins-alerts', false, 'coins-alerts/lang' );
    }
    
    // Loading styles and scripts
    public function loadStylesScripts() {
      
      wp_register_style( 'ca-select2', COINS_ALERTS_URL . 'public/vendor/select2/css/select2.min.css' );
      wp_register_style( 'ca-datatables', COINS_ALERTS_URL . 'public/vendor/datatables/css/jquery.dataTables.css' );
      wp_register_style( 'ca-datatables-r', COINS_ALERTS_URL . 'public/vendor/datatables/css/responsive.dataTables.min.css' );
      wp_register_style( 'ca-custom', COINS_ALERTS_URL . 'public/css/custom.css' );
      wp_register_script( 'ca-select2', COINS_ALERTS_URL . 'public/vendor/select2/js/select2.min.js', [ 'jquery' ], self::PLUGIN_VERSION, true );
      wp_register_script( 'ca-datatables', COINS_ALERTS_URL . 'public/vendor/datatables/js/jquery.dataTables.min.js', [ 'jquery' ], self::PLUGIN_VERSION, true );
      wp_register_script( 'ca-datatables-r', COINS_ALERTS_URL . 'public/vendor/datatables/js/dataTables.responsive.min.js', [ 'jquery' ], self::PLUGIN_VERSION, true );
      wp_register_script( 'ca-custom', COINS_ALERTS_URL . 'public/js/custom.js', [ 'jquery' ], self::PLUGIN_VERSION, true );
      
      wp_enqueue_style( 'ca-select2' );
      wp_enqueue_style( 'ca-datatables' );
      wp_enqueue_style( 'ca-datatables-r' );
      wp_enqueue_style( 'ca-custom' );
      wp_enqueue_script( 'ca-select2' );
      wp_enqueue_script( 'ca-datatables' );
      wp_enqueue_script( 'ca-datatables-r' );
      wp_enqueue_script( 'ca-custom' );
      
      wp_localize_script( 'ca-custom', 'caCustom', [
        'coins' => CA_Helper::loadCoinsList(),
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'add_alert_nonce' => wp_create_nonce( 'add-alert-nonce' ),
        'deactivate_alert_nonce' => wp_create_nonce( 'deactivate-alert-nonce' ),
        'remove_alert_nonce' => wp_create_nonce( 'remove-alert-nonce' ),
        'currency' => CA_Helper::getCurrentCurrency(),
        'error_text' => esc_html__( 'Some required information is missing or incomplete. Please, try again.', 'coins-alerts' ),
        'internal_error_text' => esc_html__( 'Sorry, something went wrong. Please, try again.', 'coins-alerts' ),
        'success_text' => esc_html__( 'Well done! You have successfully added an alert. Please, check your email and confirm it.', 'coins-alerts' ),
        'loading' => esc_html__( 'loading...', 'coins-alerts' )
      ] );
      
    }
    
    // Add alert handler
    public function addAlertHandler() {
      
      check_ajax_referer( 'add-alert-nonce', 'nonce' );
      
      $coin_id = sanitize_text_field( $_POST['coin_id'] );
      $criteria = sanitize_text_field( $_POST['criteria'] );
      $amount = sanitize_text_field( $_POST['amount'] );
      $email = sanitize_email( $_POST['email'] );
      
      if ( ! $coin_id || ! $criteria || ! $amount || ! $email ) {
        
        echo 'error';
        wp_die();
        
      }
      
      if ( $amount <= 0 || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        
        echo 'error';
        wp_die();
        
      }
      
      $coin_data = $this->getCryptocurrencyInfo( $coin_id );
      
      $keys = $this->addAlertToDB( $coin_id, $coin_data['symbol'], $coin_data['name'], $criteria, $amount, $email );
      
      $this->sendConfirmEmail( $email, $coin_data['name'], $criteria, $amount, $keys['confirm_key'], $keys['secret_key'] );
    
    }
    
    // Extracting information by currency
    public function getCryptocurrencyInfo( $coin_id ) {
      
      $coins = CA_Helper::loadCoinsList();
      
      if ( ! $coins ) {
        
        echo 'internal_error';
        wp_die();
        
      }
      
      $coin_data = [];
      foreach ( $coins as $id => $coin ) {
        if ( $id == $coin_id ) {
          
          $coin_data['symbol'] = $coin['symbol'];
          $coin_data['name'] = $coin['name'];
          
        }
      }
      
      if ( ! $coin_data ) {
        
        echo 'error';
        wp_die();
        
      }
      
      return $coin_data;
      
    }
    
    // Adding an alert to the database
    public function addAlertToDB( $coin_id, $coin_symbol, $coin_name, $criteria, $amount, $email ) {
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
        
      $confirm_key = CA_Helper::generateConfirmKey();
      $secret_key = CA_Helper::generateSecretKey();
      
      $data = [
        'coin_id' => $coin_id,
        'coin_symbol' => $coin_symbol,
        'coin_name' => $coin_name,
        'criteria' => $criteria,
        'amount' => $amount,
        'currency' => CA_Helper::getCurrentCurrency(),
        'email' => $email,
        'created' => CA_Helper::getCurrentDate(),
        'status' => 'inactive',
        'confirm_key' => $confirm_key,
        'secret_key' => $secret_key
      ];
      
      $insert_result = $wpdb->insert( $table_name, $data );
      
      if ( ! $insert_result ) {
        
        echo 'internal_error';
        wp_die();
        
      }
      
      CA_Helper::addEmailToCookie( $email );
      
      return [
        'confirm_key' => $confirm_key,
        'secret_key' => $secret_key
      ];
      
    }
    
    // Sending an email with a link to confirm the alert
    public function sendConfirmEmail( $email, $coin_name, $criteria, $amount, $confirm_key, $secret_key ) {
      
      $subject = esc_html__( 'Alert Confirmation', 'coins-alerts' );
      
      $content = CA_Helper::getTemplData( CA_Helper::getConfirmTempl() );
      
      $site_url = get_site_url();
      $amount_text = CA_Helper::getAmountText( $amount, $criteria, CA_Helper::getCurrentCurrency() );
      $criteria = CA_Helper::getCriteriaName( $criteria );
      $confirm_link = CA_Helper::generateConfirmLink( $confirm_key );
      $my_alerts_link = CA_Helper::generateMyAlertsLink( $secret_key );
      $mail_banner = COINS_ALERTS_URL . 'public/img/mail-banner.jpg';
      $from = CA_Helper::getFromHeader();
      
      $content = str_replace(
        [ '%subject%', '%site_url%', '%coin_name%', '%criteria%', '%amount_text%', '%confirm_link%', '%my_alerts_link%', '%mail_banner%' ],
        [ $subject, $site_url, esc_html__( $coin_name, 'coins-alerts' ), $criteria, $amount_text, $confirm_link, $my_alerts_link, $mail_banner ],
        $content
      );
      
      $headers = [ 
        "from: {$from}",
        "content-type: text/html"
      ];
      
      $send_result = wp_mail( $email, $subject, $content, $headers );
      
      if ( ! $send_result ) {
        
        echo 'internal_error';
        wp_die();
        
      }
      
      echo 'success';
      wp_die();
      
    }
    
    // Adding query variables to the My Alerts page
    public function addMyAlertsQueryVars( $vars ) {
      
      $vars[] = 'secret_key';
      $vars[] = 'confirm_key';
      $vars[] = 'cron_key';
      
      return $vars;
      
    }
    
    // The request handler to the My Alerts page
    public function myAlertsReqHandler() {
      
      if ( ! is_page( CA_Helper::getMyAlertsSlug() ) ) {
        return;    
      }
      
      $cron_key = get_query_var( 'cron_key' );
      
      if ( $cron_key ) {
        $this->startCronTask( $cron_key );
      }
      
      $confirm_key = get_query_var('confirm_key');
      $secret_key = get_query_var('secret_key');
      
      if ( ! $confirm_key && ! $secret_key ) {
        return;
      }
      
      if ( $confirm_key || $secret_key ) {
        
        if ( $confirm_key ) {
          $this->confirmReqHandler( $confirm_key );
        } else {
          $this->secretReqHandler( $secret_key );
        }
        
      }
      
    }
    
    // The request handler with confirm_key
    public function confirmReqHandler( $confirm_key ) {
      
      global $wpdb;

      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $secret_key_var = $wpdb->get_var(
        $wpdb->prepare( "SELECT secret_key FROM $table_name WHERE confirm_key = %s", $confirm_key )
      );
      
      if ( ! $secret_key_var ) {
        
        if ( ! isset( $_COOKIE['ca_secret_key'] ) ) {
          
          wp_redirect( home_url() );
          die();
          
        } else {
          
          wp_redirect( CA_Helper::generateMyAlertsLink( $_COOKIE['ca_secret_key'] ) );
          die();
          
        }
        
      }
        
      if ( ! isset( $_COOKIE['ca_secret_key'] ) ) {
        setcookie( 'ca_secret_key', $secret_key_var, time() + YEAR_IN_SECONDS, '/' );
      }
      
      $wpdb->update( 
        $table_name,
        [ 'status' => 'active', 'confirm_key' => '' ],
        [ 'confirm_key' => $confirm_key ]
      );
      
      $this->showMyAlertsPage( 1, $secret_key_var );
      
    }
    
    // The request handler with secret_key
    public function secretReqHandler( $secret_key ) {
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $query_result = $wpdb->query(
        $wpdb->prepare( "SELECT id FROM $table_name WHERE secret_key = %s", $secret_key )
      );
      
      if ( ! $query_result ) {
        
        wp_redirect( home_url() );
        die();
        
      }
      
      if ( ! isset( $_COOKIE['ca_secret_key'] ) ) {
        setcookie( 'ca_secret_key', $secret_key, time() + YEAR_IN_SECONDS, '/' );
      }
      
      $this->showMyAlertsPage( 0, $secret_key );
      
    }
    
    // Displaying the My Alerts page
    public function showMyAlertsPage( $confirm, $secret_key ) {
      
      global $post, $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $my_alerts = $wpdb->get_results(
        "SELECT id, coin_symbol, coin_name, criteria, amount, trigger_value, currency, created, last_checked, triggered, status, secret_key FROM $table_name WHERE secret_key = '$secret_key'"
      );
      
      $show_confirm_info = false;
      
      if ( 1 === $confirm ) {
        $show_confirm_info = true;
      }
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/my-alerts/my-alerts.php';
      $content = ob_get_clean();

      $post->post_content = $content;
      
    }
    
    // Deactivating alert handler
    public function deactivateAlertHandler() {
      
      check_ajax_referer( 'deactivate-alert-nonce', 'nonce' );
      
      $secret_key = sanitize_text_field( $_POST['secret_key'] );
      $alert_id = sanitize_text_field( $_POST['alert_id'] );
      
      if ( ! $secret_key || ! $alert_id ) {
        echo 'error';
        wp_die();
      }
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $secret_key_var = $wpdb->get_var(
        $wpdb->prepare( "SELECT secret_key FROM $table_name WHERE id = %s", $alert_id )
      );
      
      if ( $secret_key_var != $secret_key ) {
        echo 'error';
        wp_die();
      }
      
      $update_result = $wpdb->update( 
        $table_name,
        [ 'status' => 'inactive' ],
        [ 'id' => $alert_id ]
      );
      
      if ( ! $update_result ) {
        echo 'error';
        wp_die();
      }
      
      echo 'success';
      wp_die();
      
    }
    
    // Removing alert handler
    public function removeAlertHandler() {
      
      check_ajax_referer( 'remove-alert-nonce', 'nonce' );
      
      $secret_key = sanitize_text_field( $_POST['secret_key'] );
      $alert_id = sanitize_text_field( $_POST['alert_id'] );
      
      if ( ! $secret_key || ! $alert_id ) {
        echo 'error';
        wp_die();
      }
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $secret_key_var = $wpdb->get_var(
        $wpdb->prepare( "SELECT secret_key FROM $table_name WHERE id = %s", $alert_id )
      );
      
      if ( $secret_key_var != $secret_key ) {
        echo 'error';
        wp_die();
      }
      
      $delete_result = $wpdb->delete( 
        $table_name,
        [ 'id' => $alert_id ]
      );
      
      if ( ! $delete_result ) {
        echo 'error';
        wp_die();
      }
      
      echo 'success';
      wp_die();
      
    }
    
    // Getting current value
    public function getCurrentValue() {
      
      $currency = sanitize_text_field( $_POST['currency'] );
      $coin = sanitize_text_field( $_POST['coin'] );
      $criteria = sanitize_text_field( $_POST['criteria'] );
      
      if ( ! $currency || ! $coin || ! $criteria ) {
        echo 'error';
        wp_die();
      }
      
      $coins_symbols = [];
      $coins_symbols[$currency][] = $coin;
      
      $compare_data = CA_Cron::cryptoCompareReq( $coins_symbols );
      
      if ( ! $compare_data ) {
        echo 'error';
        wp_die();
      }
      
      $criteria_api = CA_Helper::getCriteriaAPIName( $criteria );
      
      if ( ! $criteria_api ) {
        echo 'error';
        wp_die();
      }
      
      if ( ! isset( $compare_data[$coin] ) || ! isset( $compare_data[$coin][$currency] ) || ! isset( $compare_data[$coin][$currency][$criteria_api] ) ) {
        echo 'error';
        wp_die();
      }
      
      $current_value = $compare_data[$coin][$currency][$criteria_api];
      
      echo CA_Helper::getAmountText( $current_value, $criteria, $currency );
      wp_die();
      
    }
    
    // Running the cron task
    public function startCronTask( $cron_key ) {
      
      if ( $cron_key != CA_Helper::getCronKey() ) {
        return;
      }
      
      $active_alerts = CA_Cron::getActiveAlerts();
      
      if ( ! $active_alerts ) {
        return;
      }
      
      $coins_symbols = CA_Cron::getCoinsSymbols( $active_alerts );
      
      $compare_data = CA_Cron::cryptoCompareReq( $coins_symbols );
      
      if ( ! $compare_data ) {
        return;
      }
      
      CA_Cron::checkAlerts( $active_alerts, $compare_data );
      
    }

  }
}