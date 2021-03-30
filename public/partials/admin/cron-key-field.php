<?php
  // If this file is called directly, abort
  if ( ! defined( 'ABSPATH' ) ) {
    die;
  }
?>

<input type="hidden" name="ca_option[cron_key]" value="%cron_key%">
<p><code>wget -O - %cron_url% >/dev/null 2>&1</code></p>
