<?php

namespace CoinsAlerts;

// If this file is called directly, abort
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

// Class for cron task processing
if ( ! class_exists( 'CA_Cron' ) ) {
  class CA_Cron {
    
    // Receiving all active alerts
    static public function getActiveAlerts() {
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $active_alerts = $wpdb->get_results(
        "SELECT id, coin_symbol, coin_name, criteria, amount, currency, email, secret_key FROM $table_name WHERE status = 'active'"
      );
      
      if ( ! $active_alerts ) {
        return false;
      }
      
      return $active_alerts;
      
    }
    
    // Receiving cryptocurrency symbols
    static public function getCoinsSymbols( $active_alerts ) {
      
      $coins_symbols = [];
      
      foreach ( $active_alerts as $active_alert ) {
        $coins_symbols[$active_alert->currency][] = $active_alert->coin_symbol;
      }
      
      return $coins_symbols;
      
    }
    
    // Request to CryptoCompare for data extraction
    static public function cryptoCompareReq( $coins_symbols ) {
      
      $currency_arr = [];
      $symbols_arr = [];
      
      foreach ( $coins_symbols as $cy => $symbols ) {
        
        $currency_arr[] = $cy;
        
        foreach ( $symbols as $symbol ) {
          $symbols_arr[] = $symbol;
        }
        
      }
        
      $symbols_multi_arr = array_chunk( $symbols_arr, 50 );

      $currency_str = implode( ',', $currency_arr );

      $compare_data = [];

      foreach ( $symbols_multi_arr as $symbols_arr ) {

        $symbols_str = implode( ',', $symbols_arr );

        $req_url = "https://min-api.cryptocompare.com/data/pricemultifull?fsyms={$symbols_str}&tsyms={$currency_str}";

        $request = wp_remote_get( $req_url );

        if( is_wp_error( $request ) ) {
          return false;
        }

        $body = wp_remote_retrieve_body( $request );
        $data = json_decode( $body, true );

        foreach ( $data['RAW'] as $data_symbol => $data_currency ) {
          foreach ( $data_currency as $cy => $cy_info ) {
            
            $compare_data[$data_symbol][$cy]['price'] = $cy_info['PRICE'];
            $compare_data[$data_symbol][$cy]['change_24_hour'] = $cy_info['CHANGE24HOUR'];
            $compare_data[$data_symbol][$cy]['change_pct_24_hour'] = $cy_info['CHANGEPCT24HOUR'];
            $compare_data[$data_symbol][$cy]['total_volume_24_h'] = $cy_info['TOTALVOLUME24H'];
            $compare_data[$data_symbol][$cy]['total_volume_24_hto'] = $cy_info['TOTALVOLUME24HTO'];
            $compare_data[$data_symbol][$cy]['low_24_hour'] = $cy_info['LOW24HOUR'];
            $compare_data[$data_symbol][$cy]['high_24_hour'] = $cy_info['HIGH24HOUR'];
            $compare_data[$data_symbol][$cy]['mktcap'] = $cy_info['MKTCAP'];
            $compare_data[$data_symbol][$cy]['supply'] = $cy_info['SUPPLY'];
            
          }
        }

      }

      return $compare_data;
      
    }
    
    // Checking alerts for compliance with conditions
    static public function checkAlerts( $active_alerts, $compare_data ) {
      
      $content = CA_Helper::getTemplData( CA_Helper::getNotificTempl() );
      
      foreach ( $active_alerts as $active_alert ) {
        foreach ( $compare_data as $compare_symbol => $compare_currency ) {
          
          if ( $compare_symbol != $active_alert->coin_symbol ) {
            continue;
          }
          
          foreach ( $compare_currency as $cy => $cy_info ) {
            
            if ( $cy != $active_alert->currency ) {
              continue;
            }
            
            $current_amount = self::getCompareResult( $active_alert->criteria, $active_alert->amount, $cy_info );
            
            global $wpdb;
      
            $table_name = $wpdb->prefix . 'coins_alerts';
            
            if ( $current_amount !== false ) {
              
              $current_date = CA_Helper::getCurrentDate();
              
              $wpdb->update(
                $table_name,
                [ 'last_checked' => $current_date, 'triggered' => $current_date, 'trigger_value' => $current_amount, 'status' => 'inactive' ],
                [ 'id' => $active_alert->id ]
              );
              
              self::sendNotificationEmail( $content, $active_alert->email, $active_alert->coin_name, $active_alert->criteria, $active_alert->amount, $current_amount, $active_alert->currency, $current_date, $active_alert->secret_key );
              
            } else {
              $wpdb->update(
                $table_name,
                [ 'last_checked' => CA_Helper::getCurrentDate() ],
                [ 'id' => $active_alert->id ]
              );
            }
            
          }
          
        }
      }
      
    }
    
    // Receiving the current value in the event of a condition
    static public function getCompareResult( $criteria, $amount, $cy_info ) {
      
      $current_amount = false;
      
      $price = $cy_info['price'];
      $change_24_hour = $cy_info['change_24_hour'];
      $change_pct_24_hour = $cy_info['change_pct_24_hour'];
      $total_volume_24_h = $cy_info['total_volume_24_h'];
      $total_volume_24_hto = $cy_info['total_volume_24_hto'];
      $low_24_hour = $cy_info['low_24_hour'];
      $high_24_hour = $cy_info['high_24_hour'];
      $mktcap = $cy_info['mktcap'];
      $supply = $cy_info['supply'];
      
      if ( 'falls' == $criteria ) {        
        if ( $price < $amount ) {
          $current_amount = $price;
        }
      } else if ( 'rises' == $criteria ) {
        if ( $price > $amount ) {
          $current_amount = $price;
        }        
      } else if ( 'incr' == $criteria ) {
        if ( $change_24_hour > $amount ) {
          $current_amount = $change_24_hour;
        }      
      } else if ( 'decr' == $criteria ) {
        
        $amount = -$amount;
        
        if ( $change_24_hour < $amount ) {
          $current_amount = $change_24_hour;
        }
        
      } else if ( 'incr_perc' == $criteria ) {
        if ( $change_pct_24_hour > $amount ) {
          $current_amount = $change_pct_24_hour;
        }
      } else if ( 'decr_perc' == $criteria ) {
        
        $amount = -$amount;
        
        if ( $change_pct_24_hour < $amount ) {
          $current_amount = $change_pct_24_hour;
        }
        
      } else if ( 'volume' == $criteria ) {
        if ( $total_volume_24_h > $amount ) {
          $current_amount = $total_volume_24_h;
        }
      } else if ( 'volume_to' == $criteria ) {
        if ( $total_volume_24_hto > $amount ) {
          $current_amount = $total_volume_24_hto;
        }
      } else if ( 'days_low' == $criteria ) {
        if ( $low_24_hour < $amount ) {
          $current_amount = $low_24_hour;
        }
      } else if ( 'days_high' == $criteria ) {
        if ( $high_24_hour > $amount ) {
          $current_amount = $high_24_hour;
        }
      } else if ( 'market_cap' == $criteria ) {
        if ( $mktcap > $amount ) {
          $current_amount = $mktcap;
        }
      } else if ( 'supply' == $criteria ) {
        if ( $supply > $amount ) {
          $current_amount = $supply;
        }
      }
      
      return $current_amount;
      
    }
    
    // Sending an email with an alert message
    static public function sendNotificationEmail( $content, $email, $coin_name, $criteria, $amount, $current_amount, $currency, $current_date, $secret_key ) {
      
      $subject = esc_html__( 'Alert Notification', 'coins-alerts' );
      
      $site_url = get_site_url();
      $amount_text = CA_Helper::getAmountText( $amount, $criteria, $currency );
      $current_amount_text = CA_Helper::getAmountText( $current_amount, $criteria, $currency );
      $criteria = CA_Helper::getCriteriaName( $criteria );
      $current_date = CA_Helper::addGMTOffset( $current_date );
      $my_alerts_link = CA_Helper::generateMyAlertsLink( $secret_key );
      $mail_banner = COINS_ALERTS_URL . 'public/img/mail-banner.jpg';
      $from = CA_Helper::getFromHeader();
      
      $content = str_replace(
        [ '%subject%', '%coin_name%', '%criteria%', '%amount_text%', '%current_amount_text%', '%current_date%', '%site_url%', '%my_alerts_link%', '%mail_banner%' ],
        [ $subject, esc_html__( $coin_name, 'coins-alerts' ), $criteria, $amount_text, $current_amount_text, $current_date, $site_url, $my_alerts_link, $mail_banner ],
        $content
      );
      
      $headers = [ 
        "from: {$from}",
        "content-type: text/html"
      ];
      
      wp_mail( $email, $subject, $content, $headers );
      
    }

  }
}