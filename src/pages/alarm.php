<?php

  $alarm_state = $db->get_var('quantity_alarm', ALARM_NONE);

  // Don't mess with this unless you also change the bash script on the client
  echo ($alarm_state == ALARM_CANS_LOW ? '1' : '0');

?>
