<?php
  // If this file is called directly, abort
  if ( ! defined( 'ABSPATH' ) ) {
    die;
  }
?>

<div class="wrap wrap__ca">
  <h2><?php echo get_admin_page_title(); ?></h2>
  
  <div class="manage-alerts__ca">
    <form method="post"><input type="hidden" name="export"><button type="submit" class="button export-users__ca"><?php echo esc_html__( 'Export users', 'coins-alerts' ); ?></button></form>
      
    <table id="manage-alerts__ca">
      <thead>
        <tr>
          <th><?php echo esc_html__( 'Coin', 'coins-alerts' ); ?></th>
          <th><?php echo esc_html__( 'Condition', 'coins-alerts' ); ?></th>
          <th><?php echo esc_html__( 'Email', 'coins-alerts' ); ?></th>
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
          foreach( $all_alerts as $alert ):
            $id = $alert->id;
            $coin = $alert->coin_name;
            $criteria = $alert->criteria;
            $amount = $alert->amount;
            $trigger_value = $alert->trigger_value;
            $currency = $alert->currency;
            $email = $alert->email;
            $created = CoinsAlerts\CA_Helper::addGMTOffset( $alert->created );
            $last_checked = $alert->last_checked;
            $triggered = $alert->triggered;
            $status = $alert->status;
        
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
            <td><?php echo $email; ?></td>
            <td><?php echo $created; ?></td>
            <td data-order="<?php echo -$timestamp; ?>"><?php echo $last_checked; ?></td>
            <td><?php echo $triggered; ?></td>
            <td><?php echo $trigger_value; ?></td>
            <td><?php echo esc_html__( $status, 'coins-alerts' ); ?></td>
            <td>
              <?php if ( 'active' == $status ): ?>
                <form method="get">
                  <input type="hidden" name="page" value="manage-alerts">
                  <input type="hidden" name="alert-id" value="<?php echo $id; ?>">
                  <button class="button button-primary deactivate__ca"><?php echo esc_html__( 'Deactivate', 'coins-alerts' ); ?></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>