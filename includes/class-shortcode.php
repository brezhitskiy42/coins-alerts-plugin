<?php

namespace CoinsAlerts;

// If this file is called directly, abort
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

// Class for creating and adding a shortcode [cryptocurrency_alert_form]
if ( ! class_exists( 'CA_Shortcode' ) ) {
  class CA_Shortcode {

    // Shortcode adding
    public function __construct() {
      add_shortcode( 'cryptocurrency_alert_form', [ $this, 'createShortcode' ] );    
    }

    // Shortcode creating
    public function createShortcode( $atts ) {
      
      $design = '';

      if ( isset( $atts['design'] ) && 'black' == $atts['design'] ) {
        $design = ' black';
      }
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/shortcode/cryptocurrency-alert-form.php';
      $content = ob_get_clean();
      
      $content = str_replace( '%design%', $design, $content );
      
      return $content;

    }

  }

  new CA_Shortcode();
}