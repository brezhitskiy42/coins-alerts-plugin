<?php
  // If this file is called directly, abort
  if ( ! defined( 'ABSPATH' ) ) {
    die;
  }
?>

<div class="my-alerts__ca">
  <?php if ( $show_confirm_info ): ?>
  <div class="info__ca success__ca show__ca">
    <button class="close-btn__ca">&times;</button><?php echo esc_html__( 'Your alert was successfully confirmed.', 'coins-alerts' ); ?>
  </div>
  <?php endif; ?>
  <table id="my-alerts__ca">
    <thead>
      <tr>
        <th><?php echo esc_html__( 'Coin', 'coins-alerts' ); ?></th>
        <th><?php echo esc_html__( 'Condition', 'coins-alerts' ); ?></th>
        <th><?php echo esc_html__( 'Created', 'coins-alerts' ); ?></th>
        <th><?php echo esc_html__( 'Last checked', 'coins-alerts' ); ?></th>
        <th><?php echo esc_html__( 'Triggered', 'coins-alerts' ); ?></th>
        <th><?php echo esc_html__( 'Trigger value', 'coins-alerts' ); ?></th>
        <th><?php echo esc_html__( 'Status', 'coins-alerts' ); ?></th>
        <th><?php echo esc_html__( 'Action', 'coins-alerts' ); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
        global $post;
      
        foreach( $my_alerts as $alert ):
          $id = $alert->id;
          $coin = $alert->coin_name;
          $criteria = $alert->criteria;
          $amount = $alert->amount;
          $trigger_value = $alert->trigger_value;
          $currency = $alert->currency;
          $created = CoinsAlerts\CA_Helper::addGMTOffset( $alert->created );
          $last_checked = $alert->last_checked;
          $triggered = $alert->triggered;
          $status = $alert->status;
          $secret_key = $alert->secret_key;

          $condition_text = CoinsAlerts\CA_Helper::getConditionText( $criteria, $amount, $currency );
      
          if ( ! $trigger_value ) {
            $trigger_value = '--';
          } else {
            $trigger_value = number_format( $alert->trigger_value, 2, '.', ',' );
          }

          if ( ! $last_checked ) {
            $timestamp = 0;
            $last_checked = '--';
          } else {
            $timestamp = CoinsAlerts\CA_Helper::getTimestamp( $last_checked );
            $last_checked = CoinsAlerts\CA_Helper::getLastCheckedTime( CoinsAlerts\CA_Helper::addGMTOffset( $last_checked ) );
          }

          if ( ! $triggered ) {
            $triggered = '--';
          } else {
            $triggered = CoinsAlerts\CA_Helper::addGMTOffset( $triggered );
          }
      ?>
        <tr>
          <td><?php echo esc_html__( $coin, 'coins-alerts' ); ?></td>
          <td class="condition__ca"><?php echo $condition_text; ?></td>
          <td><?php echo $created; ?></td>
          <td data-order="<?php echo -$timestamp; ?>"><?php echo $last_checked; ?></td>
          <td><?php echo $triggered; ?></td>
          <td><?php echo $trigger_value; ?></td>
          <td><?php echo esc_html__( $status, 'coins-alerts' ); ?></td>
          <td>
            <?php if ( 'active' == $status ): ?>
              <button type="button" class="deactivate__ca" data-alert-id="<?php echo $id; ?>" data-secret-key="<?php echo $secret_key; ?>"><?php echo esc_html__( 'Deactivate', 'coins-alerts' ); ?></button>
            <?php else: ?>
              <button type="button" class="remove__ca" data-alert-id="<?php echo $id; ?>" data-secret-key="<?php echo $secret_key; ?>"><?php echo esc_html__( 'Remove', 'coins-alerts' ); ?></button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>