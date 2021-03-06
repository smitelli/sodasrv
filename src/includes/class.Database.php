<?php

  class Database {
    private $dbh;

    // =========================================================================
    // === Wrapper for the connect() method ====================================
    // =========================================================================
    public function __construct($host, $sock, $user, $pass, $db) {
      $this->connect($host, $sock, $user, $pass, $db);
    }

    // =========================================================================
    // === Connect to MySQL, and choose a DB to use ============================
    // =========================================================================
    public function connect($host, $sock, $user, $pass, $db) {
      $this->dbh = mysqli_connect($host, $user, $pass, $db, NULL, $sock);
      $this->dbh->set_charset('utf8mb4');
    }

    // =========================================================================
    // === Disconnect from the database server =================================
    // =========================================================================
    public function disconnect() {
      $this->dbh->close();
    }

    // =========================================================================
    // === Return a date in the MySQL DATETIME format ==========================
    // =========================================================================
    public function date($time = FALSE) {
      if (!$time) $time = time();
      return date('Y-m-d H:i:s', $time);
    }

    // =========================================================================
    // === Escape a string to make it safe to use in a query ===================
    // =========================================================================
    public function escape($string) {
      return $this->dbh->real_escape_string($string);
    }

    // =========================================================================
    // === Run an SQL query and return the result handle =======================
    // =========================================================================
    public function query($query) {
      return $this->dbh->query($query);
    }

    // =========================================================================
    // === Run a query, and return all rows/columns in an assoc array ==========
    // =========================================================================
    public function get_assoc($query) {
      $result = $this->query($query);

      $arr = array();
      while ($row = $result->fetch_assoc()) {
        $arr[] = $row;
      }

      return $arr;
    }

    // =========================================================================
    // === Run a query, and return all rows for a single column ================
    // =========================================================================
    public function get_col($query) {
      $result = $this->query($query);

      $arr = array();
      while ($row = $result->fetch_row()) {
        if (isset($row[0])) $arr[] = $row[0];
      }

      return $arr;
    }

    // =========================================================================
    // === Run a query, return all columns of the first row in an assoc array ==
    // =========================================================================
    public function get_row($query) {
      $result = $this->query($query);
      return $result->fetch_assoc();
    }

    // =========================================================================
    // === Run a query, return the first field in the first row as a scalar ====
    // =========================================================================
    public function get_field($query) {
      $result = $this->query($query);
      $arr = $result->fetch_row();
      return (isset($arr[0]) ? $arr[0] : FALSE);
    }

    // =========================================================================
    // === Store a single variable in the DB ===================================
    // =========================================================================
    public function set_var($key, $value) {
      $k = $this->escape($key);
      $v = $this->escape($value);
      $this->query("
        INSERT INTO `variables`
        SET
          `variable_key`   = '$k',
          `variable_value` = '$v'
        ON DUPLICATE KEY UPDATE
          `variable_value` = '$v'
      ");
    }

    // =========================================================================
    // === Fetch a single variable from the DB =================================
    // =========================================================================
    public function get_var($key, $default = FALSE) {
      $k = $this->escape($key);
      $value = $this->get_field("
        SELECT `variable_value` FROM `variables`
        WHERE  `variable_key` LIKE '$k'
      ");
      return ($value ? $value : $default);
    }

    // =========================================================================
    // === Write a message to the logging table ================================
    // =========================================================================
    public function write_log($message) {
      $m = $this->escape($message);
      $this->query("
        INSERT INTO `log`
        SET
          `timestamp` = NOW(),
          `message`   = '$m'
      ");
    }
  }

?>
