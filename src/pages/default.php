<?php

  // Load everything from the variable table
  $variables = array();
  $variables_raw = $db->get_assoc("SELECT * FROM `variables`");
  foreach ($variables_raw as $row) {
    $variables[$row['variable_key']] = $row['variable_value'];
  }

  // Load recent log lines
  $lim = intval(SHOW_LOG_LINES);
  $log_lines = $db->get_assoc("
    SELECT * FROM (
      SELECT * FROM `log`
      ORDER BY `id` DESC
      LIMIT $lim
    ) AS `tmp`
    ORDER BY `id` ASC
  ");

  // Prepare the template and send it
  $smarty = spawn_smarty();
  $smarty->assign('vars', $variables);
  $smarty->assign('log',  $log_lines);
  $smarty->display('default.tpl');

?>
