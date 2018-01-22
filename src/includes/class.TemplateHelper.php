<?php

  class TemplateHelper {
    public function date($format, $timestring) {
      if ($format == 'short') $format = 'n/j/Y g:i:s A';
      return date($format, strtotime($timestring));
    }

    public function round($val, $precision = 0) {
      return round($val, $precision);
    }

    public function pluralize($val, $word) {
      $suffix = (abs($val) == 1) ? '' : 's';
      return "{$val} {$word}{$suffix}";
    }

    public function heartbeat_age($timestamp) {
      $diff = time() - strtotime($timestamp);
      return $this->pluralize($diff, 'second') . ' ago';
    }
  }

?>
