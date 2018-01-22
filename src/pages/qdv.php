<?php

  $tt = $db->get_field("
    SELECT `data_value` FROM `data_points`
    WHERE `data_key` = 'temperature'
    ORDER BY `timestamp` DESC
    LIMIT 1
  ");

  $qt = $db->get_field("
    SELECT `data_value` FROM `data_points`
    WHERE `data_key` = 'quantity'
    ORDER BY `timestamp` DESC
    LIMIT 1
  ");

  $t = data_to_temperature($tt);
  $q = data_to_quantity($qt);

  $tr = round($t, 1);
  $qr = round($q);

  echo '<meta http-equiv="refresh" content="5">';
  echo "<h1>t = $tr ($tt -> $t)</h1>";
  echo "<h1>q = $qr ($qt -> $q)</h1>";

?>
