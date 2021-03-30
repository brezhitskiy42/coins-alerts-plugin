<?php
  // If this file is called directly, abort
  if ( ! defined( 'ABSPATH' ) ) {
    die;
  }
?>

<select name="ca_option[currency]" class="regular-text">
  <?php foreach ( $currency_list as $symbol => $currency_name ): ?>
    <option
      value="<?php echo $symbol; ?>"
      <?php if ( $currency == $symbol ) { echo ' selected="selected"'; } ?>
    ><?php echo "{$currency_name} [{$symbol}]"; ?></option>
  <?php endforeach; ?>
</select>