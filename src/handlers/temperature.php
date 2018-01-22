<?php

  // Find the current temperature
  $temperature = data_to_temperature($data['value']);

  // Store the current temperature (in proper form) in the DB
  $n = $db->escape($data['timestamp']);
  $t = floatval($temperature);
  $db->query("
    INSERT INTO `temperatures`
    SET
      `timestamp`   = '$n',
      `temperature` = $t
  ");
  $db->set_var('temperature_current', $temperature);

  // Average calculations
  $average = $db->get_field("SELECT AVG(`temperature`) FROM `temperatures`");
  $db->set_var('temperature_average', $average);

  // Min/max calculations
  $min_temp = $db->get_var('temperature_min', PHP_INT_MAX);
  $max_temp = $db->get_var('temperature_max', -PHP_INT_MAX);
  if ($temperature < $min_temp) {
    $db->set_var('temperature_min', $temperature);
    $db->set_var('temperature_min_hit', $data['timestamp']);
    $db->write_log("New low temperature hit: $temperature");
  }
  if ($temperature > $max_temp) {
    $db->set_var('temperature_max', $temperature);
    $db->set_var('temperature_max_hit', $data['timestamp']);
    $db->write_log("New high temperature hit: $temperature");
  }

  // Read the temperatures for the last n points
  $lim = intval(TEMP_AVG_POINTS);
  $lastn = $db->get_col("
    SELECT `temperature` FROM `temperatures`
    ORDER BY `timestamp` DESC
    LIMIT $lim
  ");

  // If there is data, find the recent running average
  if (count($lastn) > 0) {
    $average = array_sum($lastn) / count($lastn);

    // Handle any temperature alarms that might have popped up
    $alarm_state = $db->get_var('temperature_alarm', ALARM_NONE);
    $too_hot     = $average >= TEMP_HOT_POINT;
    $too_cold    = $average <= TEMP_COLD_POINT;
    if ($alarm_state != ALARM_NONE && !$too_hot && !$too_cold) {
      // Unit was in an alarm state, but the problem has gone away
      $db->set_var('temperature_alarm', ALARM_NONE);
      $db->write_log("Reset temperature alarm, current average: $average");
      //TODO send email

    } else if ($alarm_state != ALARM_TOO_HOT && $too_hot) {
      // Unit is too hot, and we haven't seen it before
      $db->set_var('temperature_alarm', ALARM_TOO_HOT);
      $db->write_log("HIGH TEMPERATURE ALARM, current average: $average");
      //TODO send email

    } else if ($alarm_state != ALARM_TOO_COLD && $too_cold) {
      // Unit is too cold, and we haven't seen it before
      $db->set_var('temperature_alarm', ALARM_TOO_COLD);
      $db->write_log("LOW TEMPERATURE ALARM, current average: $average");
      //TODO send email
    }
  }

?>
