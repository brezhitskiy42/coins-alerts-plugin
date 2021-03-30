<?php
  // If this file is called directly, abort
  if ( ! defined( 'ABSPATH' ) ) {
    die;
  }
?>

<div class="wrap">
  <h2><?php echo get_admin_page_title(); ?></h2>
  <?php settings_errors(); ?>
  
  <form action="options.php" method="post">
    <?php
      settings_fields( 'ca_option_group' );
      do_settings_sections( 'coins-alerts-settings' );
      submit_button();
    ?>
  </form>
</div>