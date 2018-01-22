<?php

  // Find the current quantity
  $quantity_raw = data_to_quantity($data['value']);
  if ($quantity_raw > QUANTITY_CANS_MAX) $quantity_raw = QUANTITY_CANS_MAX;
  if ($quantity_raw < 0) $quantity_raw = 0;

  // Read the raw quantities for the last n points
  $lim = intval(QUANTITY_AVG_POINTS);
  $lastn = $db->get_col("
    SELECT ROUND(`quantity_raw`) FROM `quantities`
    ORDER BY `timestamp` DESC
    LIMIT $lim
  ");

  // If there is enough data, filter the raw values into something smooth
  $quantity_filtered = $db->get_var('quantity_current', 0);
  if (count($lastn) >= QUANTITY_AVG_POINTS) {
    if (min($lastn) == max($lastn)) {
      // Lowest and highest elements of the array are the same, we can
      // reasonably assume that ALL the elements in the array are the same.
      $quantity_filtered = $lastn[0];
    }
  }

  // Detect and process apparent and true changes to the quantity
  if (round($quantity_raw) != round($db->get_var('quantity_raw', 0))) {
    // Raw quantity seems to have changed, store that fact
    $db->set_var('quantity_raw_changed', $data['timestamp']);
  }
  if ($quantity_filtered != $db->get_var('quantity_current', 0)) {
    // True quantity seems to have changed, store that fact
    quantity_changed($quantity_filtered);
  }

  // Store the current quantity (in proper form) in the DB
  $n = $db->escape($data['timestamp']);
  $qr = floatval($quantity_raw);
  $qf = intval($quantity_filtered);
  $db->query("
    INSERT INTO `quantities`
    SET
      `timestamp`         = '$n',
      `quantity_raw`      = $qr,
      `quantity_filtered` = $qf
  ");
  $db->set_var('quantity_raw',     $quantity_raw);
  $db->set_var('quantity_current', $quantity_filtered);

  // Handle any quantity alarms that might have popped up
  $alarm_state = $db->get_var('quantity_alarm', ALARM_NONE);
  $too_low = (round($quantity_raw) <= QUANTITY_CANS_LOW &&
              $quantity_filtered   <= QUANTITY_CANS_LOW);
  if ($alarm_state != ALARM_NONE && !$too_low) {
    // Unit was in an alarm state, but the problem has gone away
    $db->set_var('quantity_alarm', ALARM_NONE);
    $db->write_log("Reset quantity alarm, raw count: " . round($quantity_raw));
    //TODO send email

  } else if ($alarm_state != ALARM_CANS_LOW && $too_low) {
    // Can count is too low, and we haven't seen it before
    $db->set_var('quantity_alarm', ALARM_CANS_LOW);
    $db->write_log("LOW QUANTITY ALARM, current count: $quantity_filtered");
    //TODO send email
  }

  // ===========================================================================
  // === Helper function to process true changes in quantity ===================
  // ===========================================================================
  function quantity_changed($new_quantity) {
    global $db;

    $default_stack = serialize(array());
    $default_hours = serialize(array_fill(0, 24, 0));

    // Things we will likely need
    $old_quantity = $db->get_var('quantity_current', 0);
    $change_time  = $db->get_var('quantity_raw_changed', $data['timestamp']);
    $age_stack    = unserialize($db->get_var('quantity_stack', $default_stack));
    $difference   = $new_quantity - $old_quantity;  //pos=added, neg=removed

    // Store various scalar values
    if ($difference > 0) {
      // Process cans added
      $d = $difference;
      for ($i = 0; $i < $d; $i++) {
        array_push($age_stack, $change_time);  //ADD to END
      }
      $db->set_var('quantity_added',   $change_time);
      $db->set_var('quantity_add_mag', $d);
      $db->set_var('total_adds',       $db->get_var('total_adds', 0) + $d);

      // 24-hour bar graph
      $hourly = unserialize($db->get_var('hourly_adds', $default_hours));
      $hourly[date('G', strtotime($change_time))] += $d;
      $db->set_var('hourly_adds', serialize($hourly));

    } else {
      // Process cans removed
      $d = abs($difference);
      for ($i = 0; $i < $d; $i++) {
        array_shift($age_stack);  //REMOVE from BEGINNING
      }
      $db->set_var('quantity_removed',    $change_time);
      $db->set_var('quantity_remove_mag', $d);
      $db->set_var('total_removes',     $db->get_var('total_removes', 0) + $d);

      // 24-hour bar graph
      $hourly = unserialize($db->get_var('hourly_removes', $default_hours));
      $hourly[date('G', strtotime($change_time))] += $d;
      $db->set_var('hourly_removes', serialize($hourly));
    }

    // Update common variables
    $db->set_var('quantity_stack',      serialize($age_stack));
    $db->set_var('quantity_changed',    $change_time);
    $db->set_var('quantity_change_mag', $difference);

    // Record this event in the DB
    $t = $db->escape($change_time);
    $d = $db->escape($difference);
    $db->query("
      INSERT INTO `quantity_deltas`
      SET
        `timestamp` = '$t',
        `delta`     = $d
    ");

    $db->write_log("Quantity: $change_time, $old_quantity -> $new_quantity");
  }

?>
