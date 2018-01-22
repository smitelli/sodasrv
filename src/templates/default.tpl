<pre>

Current Temperature: {$templateHelper->round($vars.temperature_current, 2)}&deg;F
Average: {$templateHelper->round($vars.temperature_average, 2)}&deg;F
Highest: {$templateHelper->round($vars.temperature_max, 2)}&deg;F <em>(Hit on: {$templateHelper->date('short', $vars.temperature_max_hit)})</em>
Lowest: {$templateHelper->round($vars.temperature_min, 2)}&deg;F <em>(Hit on: {$templateHelper->date('short', $vars.temperature_min_hit)})</em>
Alarm Trigger Points: {$smarty.const.TEMP_HOT_POINT}&deg;F (high), {$smarty.const.TEMP_COLD_POINT}&deg;F (low)
Alarm State: {$vars.temperature_alarm}

Current Quantity: {$templateHelper->pluralize($vars.quantity_current, 'can')}
Raw (Unconfirmed) Quantity: {$templateHelper->round($vars.quantity_raw, 2)} cans
Last "Add" Event: {$templateHelper->pluralize($vars.quantity_add_mag, 'can')} on {$templateHelper->date('short', $vars.quantity_added)}
Last "Remove" Event: {$templateHelper->pluralize($vars.quantity_remove_mag, 'can')} on {$templateHelper->date('short', $vars.quantity_removed)}
Last "Change" Event: {$templateHelper->pluralize($vars.quantity_change_mag, 'can')} on {$templateHelper->date('short', $vars.quantity_changed)}
Total Capacity: {$templateHelper->pluralize($smarty.const.QUANTITY_CANS_MAX, 'can')}
Alarm Trigger Point: {$templateHelper->pluralize($smarty.const.QUANTITY_CANS_LOW, 'can')}
Alarm State: {$vars.quantity_alarm}

Last Heartbeat: {$templateHelper->heartbeat_age($vars.heartbeat_last)}
Points in Data Table: {$dataCount}

{section name='line' loop=$log}
  [{$log[line].timestamp}] {$log[line].message|escape:'html'}
{/section}

</pre>
