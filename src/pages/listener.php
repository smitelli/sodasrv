<?php

  // Only allow this script to run from certain client IPs
  if (@$_SERVER['HTTP_X_SODA_AUTH'] !== LISTENER_AUTH) {
    die("Sorry, nope.");
  }

  // Sent from the client
  $data = array(
    'timestamp' => $db->date(),
    'key'       => isset($_GET['key']) ? $_GET['key'] : '',
    'value'     => isset($_GET['val']) ? $_GET['val'] : ''
  );

  // Make sure the data is usable
  if (!$data['key'] || !$data['value']) {
    die("Missing key or value!");
  }

  // Make sure the returned values are sane, if they came from a probe
  if ($data['key'] == 'temperature' || $data['key'] == 'quantity') {
    if ($data['value'] > PROBE_LONG_IGNORE) {
      $db->write_log(
        "Ignoring LONG response for {$data['key']}: {$data['value']}"
      );
      die("Value is too long!");

    } else if ($data['value'] < PROBE_SHORT_IGNORE) {
      $db->write_log("
        Ignoring SHORT response for {$data['key']}: {$data['value']}"
      );
      die("Value is too short!");
    }
  }

  // Insert the raw data into the database
  $t = $db->escape($data['timestamp']);
  $k = $db->escape($data['key']);
  $v = $db->escape($data['value']);
  $db->query("
    INSERT INTO `data_points`
    SET
      `timestamp`  = '$t',
      `data_key`   = '$k',
      `data_value` = '$v'
  ");

  // Count rows in the data table. This is stupid expensive, so only do it
  // during the heartbeat at the top of the hour.
  if ($data['key'] == 'heartbeat' && intval(date('i')) == 0) {
    $data_points_count = $db->get_field("SELECT COUNT(*) FROM `data_points`");
    $db->set_var('data_points_count', $data_points_count);
  }

  // Give some indication that it worked
  echo 'OK';

  // Call the external handler, if one exists for the type of key sent
  $handler = "{$src_path}/handlers/"
           . preg_replace('/[^a-z]/', '', $data['key'])
           . '.php';
  if (is_readable($handler)) require $handler;

?>
