<?php

namespace CoinsAlerts;
use \DateTime;
use \DateTimeZone;

// If this file is called directly, abort
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

// Connecting the list of cryptocurrency
if ( file_exists( COINS_ALERTS_PATH . 'config/coins.php' ) ) {
  require_once COINS_ALERTS_PATH . 'config/coins.php';
}

// Class with additional functionality
if ( ! class_exists( 'CA_Helper' ) ) {
  class CA_Helper {
    
    const CONFIRM_TEMPL = 'alert-confirmation.php';
    const NOTIFIC_TEMPL = 'alert-notification.php';
    
    // Runs when the plugin is activated
    public function activate() {
      
      if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
      }
      
      $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
      check_admin_referer( "activate-plugin_{$plugin}" );
    
      $this->checkPHPVersion();
      $this->createCoinsAlertsTable();
      $this->createMyAlertsPage();
      $this->createOptionData();
      
    }
    
    // Runs when the plugin is uninstalled
    public function delete() {
      
      if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
      }
      
      $this->deleteCoinsAlertsTable();
      $this->deleteMyAlertsPage();
      $this->deleteOptionData();
      
    }

    // Checking the PHP version
    public function checkPHPVersion() {
      if ( version_compare( PHP_VERSION, CA_Coin_Alert::MIN_PHP_VERSION, '<' ) ) {
        wp_die( sprintf( __( '<p>PHP %s+ is required to use <b>%s</b> plugin. You have %s installed.</p>', 'coins-alerts' ), CA_Coin_Alert::MIN_PHP_VERSION, CA_Coin_Alert::PLUGIN_NAME, PHP_VERSION ), esc_html__( 'Plugin Activation Error', 'coins-alerts' ), [ 'response' => 200, 'back_link' => TRUE ] );
      }
    }
    
    // Creating an alert table
    public function createCoinsAlertsTable() {
      
      global $wpdb;
      
      $charset_collate = $wpdb->get_charset_collate();
      $table_name = $wpdb->prefix . 'coins_alerts';

      $sql = "CREATE TABLE $table_name (
        id int NOT NULL AUTO_INCREMENT,
        coin_id int NOT NULL,
        coin_symbol varchar(20) NOT NULL,
        coin_name varchar(255) NOT NULL,
        criteria varchar(20) NOT NULL,
        amount decimal(65,8) NOT NULL,
        trigger_value decimal(65,8) NULL,
        currency varchar(20) NOT NULL,
        email varchar(255) NOT NULL,
        created datetime NOT NULL,
        last_checked datetime NULL,
        triggered datetime NULL,
        status varchar(20) NOT NULL,
        confirm_key varchar(255) NOT NULL,
        secret_key varchar(255) NOT NULL,
        PRIMARY KEY id (id),
        KEY status (status),
        KEY confirm_key (confirm_key),
        KEY secret_key (secret_key)
      ) $charset_collate;";

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      
      $result = dbDelta( $sql );
      
      if ( ! $result ) {
        wp_die( esc_html__( 'Error while creating a table. Please, try again.', 'coins-alerts' ), esc_html__( 'Table Creation Error', 'coins-alerts' ), [ 'response' => 200, 'back_link' => TRUE ] );
      }
      
    }
    
    // Deleting an alert table
    public function deleteCoinsAlertsTable() {
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';

      $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
      
    }
    
    // Creating My Alerts page
    public function createMyAlertsPage() {
      
      $page = get_page_by_path( CA_Coin_Alert::MY_ALERTS_SLUG );
      
      if ( ! $page ) {
        
        $args = [
          'post_title' => 'My Alerts',
          'post_name' => CA_Coin_Alert::MY_ALERTS_SLUG,
          'post_type' => 'page',
          'post_status' => 'publish',
          'post_author' => 1
        ];

        $result = wp_insert_post( $args );

        if ( ! $result ) {
          wp_die( esc_html__( 'Error while creating a page. Please, try again.', 'coins-alerts' ), esc_html__( 'Page Creation Error', 'coins-alerts' ), [ 'response' => 200, 'back_link' => TRUE ] );
        }
        
        update_option( 'ca_my_alerts_id', $result );
        
      }
      
    }
    
    // Deleting My Alerts page
    public function deleteMyAlertsPage() {
      wp_delete_post( get_option( 'ca_my_alerts_id' ), true );
    }
    
    // Creating data with settings
    public function createOptionData() {
      
      $cron_key = wp_generate_password( 12, false );
      $from = 'Coins Alerts <' . get_option( 'admin_email' ) . '>';
      $cron_url = self::getCronUrl( $cron_key );
      $confirm_templ = self::CONFIRM_TEMPL;
      $notific_templ = self::NOTIFIC_TEMPL;
      $my_alerts_slug = CA_Coin_Alert::MY_ALERTS_SLUG;
      
      $data = [
        'cron_key' => $cron_key,
        'currency' => 'USD',
        'from' => $from,
        'cron_url' => $cron_url,
        'confirm_templ' => $confirm_templ,
        'notific_templ' => $notific_templ,
        'my_alerts_slug' => $my_alerts_slug
      ];
      
      add_option( 'ca_option', $data );
      
      $currencies = [
        'AED' => 'United Arab Emirates dirham',
        'AFN' => 'Afghan afghani',
        'ALL' => 'Albanian lek',
        'AMD' => 'Armenian dram',
        'ANG' => 'Netherlands Antillean guilder',
        'AOA' => 'Angolan kwanza',
        'ARS' => 'Argentine peso',
        'AUD' => 'Australian dollar',
        'AWG' => 'Aruban florin',
        'AZN' => 'Azerbaijani manat',
        'BAM' => 'Bosnia and Herzegovina convertible mark',
        'BBD' => 'Barbados dollar',
        'BDT' => 'Bangladeshi taka',
        'BGN' => 'Bulgarian lev',
        'BHD' => 'Bahraini dinar',
        'BIF' => 'Burundian franc',
        'BMD' => 'Bermudian dollar',
        'BND' => 'Brunei dollar',
        'BOB' => 'Boliviano',
        'BRL' => 'Brazilian real',
        'BSD' => 'Bahamian dollar',
        'BTN' => 'Bhutanese ngultrum',
        'BWP' => 'Botswana pula',
        'BYN' => 'New Belarusian ruble',
        'BYR' => 'Belarusian ruble',
        'BZD' => 'Belize dollar',
        'CAD' => 'Canadian dollar',
        'CDF' => 'Congolese franc',
        'CHF' => 'Swiss franc',
        'CLF' => 'Unidad de Fomento',
        'CLP' => 'Chilean peso',
        'CNY' => 'Chinese yuan',
        'COP' => 'Colombian peso',
        'CRC' => 'Costa Rican colon',
        'CUC' => 'Cuban convertible peso',
        'CUP' => 'Cuban peso',
        'CVE' => 'Cape Verde escudo',
        'CZK' => 'Czech koruna',
        'DJF' => 'Djiboutian franc',
        'DKK' => 'Danish krone',
        'DOP' => 'Dominican peso',
        'DZD' => 'Algerian dinar',
        'EGP' => 'Egyptian pound',
        'ERN' => 'Eritrean nakfa',
        'ETB' => 'Ethiopian birr',
        'EUR' => 'Euro',
        'FJD' => 'Fiji dollar',
        'FKP' => 'Falkland Islands pound',
        'GBP' => 'Pound sterling',
        'GEL' => 'Georgian lari',
        'GHS' => 'Ghanaian cedi',
        'GIP' => 'Gibraltar pound',
        'GMD' => 'Gambian dalasi',
        'GNF' => 'Guinean franc',
        'GTQ' => 'Guatemalan quetzal',
        'GYD' => 'Guyanese dollar',
        'HKD' => 'Hong Kong dollar',
        'HNL' => 'Honduran lempira',
        'HRK' => 'Croatian kuna',
        'HTG' => 'Haitian gourde',
        'HUF' => 'Hungarian forint',
        'IDR' => 'Indonesian rupiah',
        'ILS' => 'Israeli new shekel',
        'INR' => 'Indian rupee',
        'IQD' => 'Iraqi dinar',
        'IRR' => 'Iranian rial',
        'ISK' => 'Icelandic króna',
        'JMD' => 'Jamaican dollar',
        'JOD' => 'Jordanian dinar',
        'JPY' => 'Japanese yen',
        'KES' => 'Kenyan shilling',
        'KGS' => 'Kyrgyzstani som',
        'KHR' => 'Cambodian riel',
        'KMF' => 'Comoro franc',
        'KPW' => 'North Korean won',
        'KRW' => 'South Korean won',
        'KWD' => 'Kuwaiti dinar',
        'KYD' => 'Cayman Islands dollar',
        'KZT' => 'Kazakhstani tenge',
        'LAK' => 'Lao kip',
        'LBP' => 'Lebanese pound',
        'LKR' => 'Sri Lankan rupee',
        'LRD' => 'Liberian dollar',
        'LSL' => 'Lesotho loti',
        'LYD' => 'Libyan dinar',
        'MAD' => 'Moroccan dirham',
        'MDL' => 'Moldovan leu',
        'MGA' => 'Malagasy ariary',
        'MKD' => 'Macedonian denar',
        'MMK' => 'Myanmar kyat',
        'MNT' => 'Mongolian tögrög',
        'MOP' => 'Macanese pataca',
        'MRO' => 'Mauritanian ouguiya',
        'MUR' => 'Mauritian rupee',
        'MVR' => 'Maldivian rufiyaa',
        'MWK' => 'Malawian kwacha',
        'MXN' => 'Mexican peso',
        'MXV' => 'Mexican Unidad de Inversion',
        'MYR' => 'Malaysian ringgit',
        'MZN' => 'Mozambican metical',
        'NAD' => 'Namibian dollar',
        'NGN' => 'Nigerian naira',
        'NIO' => 'Nicaraguan córdoba',
        'NOK' => 'Norwegian krone',
        'NPR' => 'Nepalese rupee',
        'NZD' => 'New Zealand dollar',
        'OMR' => 'Omani rial',
        'PAB' => 'Panamanian balboa',
        'PEN' => 'Peruvian Sol',
        'PGK' => 'Papua New Guinean kina',
        'PHP' => 'Philippine peso',
        'PKR' => 'Pakistani rupee',
        'PLN' => 'Polish złoty',
        'PYG' => 'Paraguayan guaraní',
        'QAR' => 'Qatari riyal',
        'RON' => 'Romanian leu',
        'RSD' => 'Serbian dinar',
        'RUB' => 'Russian ruble',
        'RWF' => 'Rwandan franc',
        'SAR' => 'Saudi riyal',
        'SBD' => 'Solomon Islands dollar',
        'SCR' => 'Seychelles rupee',
        'SDG' => 'Sudanese pound',
        'SEK' => 'Swedish krona',
        'SGD' => 'Singapore dollar',
        'SHP' => 'Saint Helena pound',
        'SLL' => 'Sierra Leonean leone',
        'SOS' => 'Somali shilling',
        'SRD' => 'Surinamese dollar',
        'SSP' => 'South Sudanese pound',
        'STD' => 'São Tomé and Príncipe dobra',
        'SVC' => 'Salvadoran colón',
        'SYP' => 'Syrian pound',
        'SZL' => 'Swazi lilangeni',
        'THB' => 'Thai baht',
        'TJS' => 'Tajikistani somoni',
        'TMT' => 'Turkmenistani manat',
        'TND' => 'Tunisian dinar',
        'TOP' => 'Tongan paʻanga',
        'TRY' => 'Turkish lira',
        'TTD' => 'Trinidad and Tobago dollar',
        'TWD' => 'New Taiwan dollar',
        'TZS' => 'Tanzanian shilling',
        'UAH' => 'Ukrainian hryvnia',
        'UGX' => 'Ugandan shilling',
        'USD' => 'United States dollar',
        'UYU' => 'Uruguayan peso',
        'UZS' => 'Uzbekistan som',
        'VEF' => 'Venezuelan bolívar',
        'VND' => 'Vietnamese đồng',
        'VUV' => 'Vanuatu vatu',
        'WST' => 'Samoan tala',
        'XAF' => 'Central African CFA franc',
        'XCD' => 'East Caribbean dollar',
        'XOF' => 'West African CFA franc',
        'XPF' => 'CFP franc',
        'YER' => 'Yemeni rial',
        'ZAR' => 'South African rand',
        'ZMW' => 'Zambian kwacha',
        'ZWL' => 'Zimbabwean dollar'
      ];
      
      add_option( 'ca_currencies', $currencies );
      
    }
    
    // Delete data with settings
    public function deleteOptionData() {
      
      delete_option( 'ca_option' );
      delete_option( 'ca_currencies' );
      
    }
    
    // Getting the list of currencies from the config file
    static public function getCurrencyList() {
      return get_option( 'ca_currencies' );
    }
    
    // Getting the cron key
    static public function getCronKey() {
      
      $ca_option = get_option( 'ca_option' );
      
      return $ca_option['cron_key'];
      
    }
    
    // Getting the name of the Alert confirmation template
    static public function getConfirmTempl() {
      
      $ca_option = get_option( 'ca_option' );
      
      return $ca_option['confirm_templ'];
      
    }
    
    // Getting the name of the Alert notification template
    static public function getNotificTempl() {
      
      $ca_option = get_option( 'ca_option' );
      
      return $ca_option['notific_templ'];
      
    }
    
    // Getting the My Alerts slug
    static public function getMyAlertsSlug() {
      
      $page = get_post( get_option( 'ca_my_alerts_id' ) );
      
      return $page->post_name;
      
    }
    
    // Downloading email template content
    static public function getTemplData( $templ_name ) {
      
      ob_start();
      require_once COINS_ALERTS_PATH . 'public/partials/email/' . $templ_name;
      $content = ob_get_clean();
      
      return $content;
      
    }
    
    // Getting the from header
    static public function getFromHeader() {
      
      $ca_option = get_option( 'ca_option' );
      
      return $ca_option['from'];
      
    }
    
    // Loading the list of cryptocurrency
    static public function loadCoinsList() {
      
      global $ca_coins;
      
      $coins = [];
      foreach ( $ca_coins as $i => $coin ) {
        
        $coins[$i]['symbol'] = $coin['symbol'];
        $coins[$i]['name'] = $coin['name'];
        $coins[$i]['name_tr'] = esc_html__( $coin['name'], 'coins-alerts' );
        
      }
      
      return $coins;
      
    }
    
    // Getting cron url
    static public function getCronUrl( $cron_key ) {
      
      $page = get_page_by_path( self::getMyAlertsSlug() );

      $page_id = $page->ID;
      $page_link = get_permalink( $page_id );
      
      $cron_url = add_query_arg( [ 'cron_key' => $cron_key ], $page_link );
      
      return $cron_url;
      
    }
    
    // Generating a key for confirmation by email
    static public function generateConfirmKey() {
      return wp_generate_password( 24, false );
    }
    
    // Generating a secret user key
    static public function generateSecretKey() {
      
      if ( ! isset( $_COOKIE['ca_secret_key'] ) ) {
        
        $secret_key = wp_generate_password( 36, false );
        
        $result = setcookie( 'ca_secret_key', $secret_key, time() + YEAR_IN_SECONDS, '/' );
        
        if ( ! $result ) {
          
          echo 'internal_error';
          wp_die();
          
        }
        
        return $secret_key;
        
      } else {
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'coins_alerts';        
        $secret_key = $_COOKIE['ca_secret_key'];
        
        $query_result = $wpdb->query(
          $wpdb->prepare( "SELECT id FROM $table_name WHERE secret_key = %s", $secret_key )
        );
        
        if ( ! $query_result ) {
          
          $secret_key = wp_generate_password( 36, false );
          
          $result = setcookie( 'ca_secret_key', $secret_key, time() + YEAR_IN_SECONDS, '/' );
        
          if ( ! $result ) {

            echo 'internal_error';
            wp_die();

          }
          
        } else {
          
          $result = setcookie( 'ca_secret_key', $secret_key, time() + YEAR_IN_SECONDS, '/' );
          
          if ( ! $result ) {

            echo 'internal_error';
            wp_die();

          }
          
        }
        
        return $secret_key;
        
      }
      
    }
    
    // Adding an email to the cookie
    static public function addEmailToCookie( $email ) {
      setcookie( 'ca_email', $email, time() + YEAR_IN_SECONDS, '/' );
    }
    
    // Receive email from cookie
    static public function getEmailFromCookie() {
      
      if ( isset( $_COOKIE['ca_email'] ) ) {
        return $_COOKIE['ca_email'];
      }
      
      return '';
      
    }
    
    // Getting the current date
    static public function getCurrentDate() {
      
      $gmt_offset = get_option( 'gmt_offset' );
      $timezone_name = timezone_name_from_abbr( '', $gmt_offset * 3600, false );
      $timestamp = time();

      $date_time = new DateTime( 'now', new DateTimeZone( $timezone_name ) );
      $date_time->setTimestamp( $timestamp );
      
      return $date_time->format( 'Y-m-d H:i:s' );
      
    }
    
    // Adding a time offset to a date
    static public function addGMTOffset( $dt ) {
      
      $gmt_offset = get_option( 'gmt_offset' );
      $timezone_name = timezone_name_from_abbr( '', $gmt_offset * 3600, false );
      
      $date_time = new DateTime( $dt, new DateTimeZone( $timezone_name ) );
      
      return $date_time->format( 'Y-m-d H:i:sP' );
      
    }
    
    // Getting a timestamp
    static public function getTimestamp( $dt ) {
      
      $gmt_offset = get_option( 'gmt_offset' );
      $timezone_name = timezone_name_from_abbr( '', $gmt_offset * 3600, false );
      
      $date_time = new DateTime( $dt, new DateTimeZone( $timezone_name ) );
      
      return $date_time->getTimestamp();
      
    }
    
    // Getting the name of the monitoring criterion
    static public function getCriteriaName( $criteria ) {
      
      $criteria_name = '';
      
      if ( 'falls' == $criteria ) {
        $criteria_name = esc_html__( 'falls below', 'coins-alerts' );
      } else if ( 'rises' == $criteria ) {
        $criteria_name = esc_html__( 'rises above', 'coins-alerts' );
      } else if ( 'incr' == $criteria || 'incr_perc' == $criteria ) {
        $criteria_name = esc_html__( 'increases by at least', 'coins-alerts' );
      } else if ( 'decr' == $criteria || 'decr_perc' == $criteria ) {
        $criteria_name = esc_html__( 'decreases by at least', 'coins-alerts' );
      } else if ( 'volume' == $criteria ) {
        $criteria_name = esc_html__( 'volume in coins exceeds', 'coins-alerts' );
      } else if ( 'volume_to' == $criteria ) {
        $criteria_name = esc_html__( 'volume in currency exceeds', 'coins-alerts' );
      } else if ( 'days_low' == $criteria ) {
        $criteria_name = esc_html__( 'day\'s low falls below', 'coins-alerts' );
      } else if ( 'days_high' == $criteria ) {
        $criteria_name = esc_html__( 'day\'s high exceeds', 'coins-alerts' );
      } else if ( 'market_cap' == $criteria ) {
        $criteria_name = esc_html__( 'market cap exceeds', 'coins-alerts' );
      } else if ( 'supply' == $criteria ) {
        $criteria_name = esc_html__( 'supply exceeds', 'coins-alerts' );
      } else {
        
        echo 'error';
        wp_die();
        
      }
      
      return $criteria_name;
      
    }
    
    // Formatting the number to display in the email template
    static public function getAmountText( $amount, $criteria, $currency ) {
      
      $amount_text = '';
      
      if ( 'incr_perc' == $criteria || 'decr_perc' == $criteria ) {
        $amount_text = number_format( $amount, 2, '.', '' ) . '%';
      } else if( 'volume' == $criteria || 'volume_to' == $criteria || 'supply' == $criteria ) {
        $amount_text = number_format( $amount, 2, '.', ',' ) . ' ' . esc_html__( 'coins', 'coins-alerts' );
      } else {
        $amount_text = number_format( $amount, 2, '.', ',' ) . ' ' . $currency;
      }
      
      return $amount_text;
      
    }
    
    // Generating a link to confirm an alert
    static public function generateConfirmLink( $confirm_key ) {
      
      $page = get_page_by_path( self::getMyAlertsSlug() );
      
      if ( ! $page ) {
        
        echo 'internal_error';
        wp_die();
        
      }
      
      $page_id = $page->ID;
      $page_link = get_permalink( $page_id );
      
      $confirm_link = add_query_arg( [ 'confirm_key' => $confirm_key ], $page_link );
      
      return $confirm_link;
      
    }
    
    // Generating a link to the My Alerts page
    static public function generateMyAlertsLink( $secret_key ) {
      
      $page = get_page_by_path( self::getMyAlertsSlug() );
      
      if ( ! $page ) {
        
        echo 'internal_error';
        wp_die();
        
      }
      
      $page_id = $page->ID;
      $page_link = get_permalink( $page_id );
      
      $my_alerts_link = add_query_arg( [ 'secret_key' => $secret_key ], $page_link );
      
      return $my_alerts_link;
      
    }
    
    // Checking the current user for access to My Alerts
    static public function isUserCredible() {
      
      if ( ! isset( $_COOKIE['ca_secret_key'] ) ) {
        return false;
      }

      global $wpdb;

      $table_name = $wpdb->prefix . 'coins_alerts';        
      $secret_key = $_COOKIE['ca_secret_key'];

      $query_result = $wpdb->query(
        $wpdb->prepare( "SELECT id FROM $table_name WHERE secret_key = %s", $secret_key )
      );

      if ( ! $query_result ) {
        return false;
      }

      return true;
      
    }
    
    // Receiving the current currency
    static public function getCurrentCurrency() {
      
      $ca_option = get_option( 'ca_option' );
      
      $currency = $ca_option['currency'];
      
      return $currency;
      
    }
    
    // Receiving all alerts
    static public function getAllAlerts() {
      
      global $wpdb;
      
      $table_name = $wpdb->prefix . 'coins_alerts';
      
      $all_alerts = $wpdb->get_results(
        "SELECT id, coin_symbol, coin_name, criteria, amount, trigger_value, currency, email, created, last_checked, triggered, status FROM $table_name"
      );
      
      return $all_alerts;
      
    }
    
    // Formatting the condition for output in a table
    static public function getConditionText( $criteria, $amount, $currency ) {
      
      $condition_text = '';
      
      $criteria_name = self::getCriteriaName( $criteria );
      
      if ( 'incr_perc' == $criteria || 'decr_perc' == $criteria ) {
        $condition_text = $criteria_name . ' '. number_format( $amount, 2, '.', '' ) . '%';
      } else if( 'volume' == $criteria || 'volume_to' == $criteria || 'supply' == $criteria ) {
        $condition_text = $criteria_name . ' '. number_format( $amount, 2, '.', ',' ) . ' ' . esc_html__( 'coins', 'coins-alerts' );
      } else {
        $condition_text = $criteria_name . ' '. number_format( $amount, 2, '.', ',' ) . ' ' . $currency;
      }
      
      return $condition_text;
      
    }
    
    // Formatting the last validation time
    static public function getLastCheckedTime( $last_checked, $full = false ) {
      
      $now = new DateTime;
      $ago = new DateTime( $last_checked );
      $diff = $now->diff( $ago );

      $diff->w = floor( $diff->d / 7 );
      $diff->d -= $diff->w * 7;

      $last_checked_time = [
        'y' => esc_html__( 'year', 'coins-alerts' ),
        'm' => esc_html__( 'month', 'coins-alerts' ),
        'w' => esc_html__( 'week', 'coins-alerts' ),
        'd' => esc_html__( 'day', 'coins-alerts' ),
        'h' => esc_html__( 'hour', 'coins-alerts' ),
        'i' => esc_html__( 'minute', 'coins-alerts' ),
        's' => esc_html__( 'second', 'coins-alerts' ),
      ];
      
      foreach ( $last_checked_time as $k => &$v ) {
        if ($diff->$k) {
          $v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
        } else {
          unset( $last_checked_time[$k] );
        }
      }

      if ( ! $full ) { 
        $last_checked_time = array_slice( $last_checked_time, 0, 1 );
      }
      
      return $last_checked_time ? implode( ', ', $last_checked_time ) . esc_html__( ' ago', 'coins-alerts' ) : esc_html__( 'just now', 'coins-alerts' );
      
    }
    
    // Getting criteria api name
    static public function getCriteriaAPIName( $criteria ) {
      
      $criteria_api = '';
      
      if ( 'rises' == $criteria || 'falls' == $criteria ) {
        $criteria_api = 'price';
      } else if ( 'incr' == $criteria || 'decr' == $criteria ) {
        $criteria_api = 'change_24_hour';
      } else if ( 'incr_perc' == $criteria || 'decr_perc' == $criteria ) {
        $criteria_api = 'change_pct_24_hour';
      } else if ( 'volume' == $criteria ) {
        $criteria_api = 'total_volume_24_h';
      } else if ( 'volume_to' == $criteria ) {
        $criteria_api = 'total_volume_24_hto';
      } else if ( 'days_low' == $criteria ) {
        $criteria_api = 'low_24_hour';
      } else if ( 'days_high' == $criteria ) {
        $criteria_api = 'high_24_hour';
      } else if ( 'market_cap' == $criteria ) {
        $criteria_api = 'mktcap';
      } else if ( 'supply' == $criteria ) {
        $criteria_api = 'supply';
      }
      
      return $criteria_api;
      
    }
    
  }
}

