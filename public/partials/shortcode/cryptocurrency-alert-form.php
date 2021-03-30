<?php
  // If this file is called directly, abort
  if ( ! defined( 'ABSPATH' ) ) {
    die;
  }
?>

<div class="block__ca%design%">
  <div class="title__ca"><?php echo esc_html__( 'Notify me when', 'coins-alerts' ); ?></div>
  <form id="add-alert__ca">
    <div class="form-block__ca">
      <select name="coins"></select>
    </div>
    <div class="form-block__ca">
      <select name="criteria">
        <option value="rises"><?php echo esc_html__( 'Rises above', 'coins-alerts' ); ?></option>
        <option value="falls"><?php echo esc_html__( 'Falls below', 'coins-alerts' ); ?></option>
        <option value="incr"><?php echo esc_html__( 'Increases by at least', 'coins-alerts' ); ?></option>
        <option value="decr"><?php echo esc_html__( 'Decreases by at least', 'coins-alerts' ); ?></option>
        <option value="incr_perc"><?php echo esc_html__( 'Increases by at least (%)', 'coins-alerts' ); ?></option>
        <option value="decr_perc"><?php echo esc_html__( 'Decreases by at least (%)', 'coins-alerts' ); ?></option>
        <option value="volume"><?php echo esc_html__( 'Volume in coins exceeds', 'coins-alerts' ); ?></option>
        <option value="volume_to"><?php echo esc_html__( 'Volume in currency exceeds', 'coins-alerts' ); ?></option>
        <option value="days_low"><?php echo esc_html__( 'Day’s low falls below', 'coins-alerts' ); ?></option>
        <option value="days_high"><?php echo esc_html__( 'Day’s high exceeds', 'coins-alerts' ); ?></option>
        <option value="market_cap"><?php echo esc_html__( 'Market cap exceeds', 'coins-alerts' ); ?></option>
        <option value="supply"><?php echo esc_html__( 'Supply exceeds', 'coins-alerts' ); ?></option>
      </select>
    </div>
    <div class="form-block__ca form-addon__ca">
      <div class="form-wrap__ca">
        <input type="text" name="amount" placeholder="<?php echo esc_html__( 'Input value', 'coins-alerts' ); ?>">
        <span class="addon__ca"><?php echo CoinsAlerts\CA_Helper::getCurrentCurrency(); ?></span>
      </div>
      <div class="current-value__ca"><?php echo esc_html__( 'Current value', 'coins-alerts' ); ?>: <span><?php echo esc_html__( 'loading...', 'coins-alerts' ); ?></span></div>
    </div>
    <div class="form-block__ca">
      <input type="text" name="email" placeholder="email@example.com" value="<?php echo CoinsAlerts\CA_Helper::getEmailFromCookie(); ?>">
    </div>
    <div class="info__ca">
      <button class="close-btn__ca">&times;</button>
      <div class="info-text__ca"></div>
    </div>
    <div class="form-block__ca text-center__ca">
      <button type="submit" class="blue-btn__ca"><?php echo esc_html__( 'Add alert', 'coins-alerts' ); ?></button>
      <div class="spinner__ca">
        <div class="double-bounce1"></div>
        <div class="double-bounce2"></div>
      </div>
    </div>
  </form>
  <?php if ( CoinsAlerts\CA_Helper::isUserCredible() ): ?>
  <div class="my-alerts-block__ca text-center__ca">
    <a href="<?php echo CoinsAlerts\CA_Helper::generateMyAlertsLink( $_COOKIE['ca_secret_key'] ); ?>" class="my-alerts-link__ca"><?php echo esc_html__( 'My alerts', 'coins-alerts' ); ?></a>
  </div>
  <?php endif; ?>
</div>