<?php

  // Include all external files
  $self_path = dirname(__FILE__);
  $src_path  = realpath("{$self_path}/../src");
  require "{$src_path}/includes/config.php";
  require "{$src_path}/includes/class.Database.php";

  // Most rudimentary router I could throw together
  $page = isset($_GET['page']) ? $_GET['page'] : '';
  $page = preg_replace('/[^a-z]/', '', $page);
  if (!is_readable("{$src_path}/pages/{$page}.php")) $page = DEFAULT_PAGE;

  // Connect to the database, and run the appropriate script from 'pages/'
  $db = new Database(MYSQL_HOST, MYSQL_SOCK, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
  require "{$src_path}/pages/{$page}.php";
  $db->disconnect();

  // ===========================================================================
  // === Configure and spin off an instance of the templating engine ===========
  // ===========================================================================
  function spawn_smarty() {
    $_sp = $GLOBALS['src_path'];

    require_once "{$_sp}/includes/smarty/Smarty.class.php";
    require_once "{$_sp}/includes/class.TemplateHelper.php";

    // Permit $compile_dir to be either relative (to $src_path) or absolute
    if (COMPILE_DIR[0] != '/') {
      $compile_dir = $_sp . '/' . COMPILE_DIR;
    } else {
      $compile_dir = COMPILE_DIR;
    }

    $smarty = new Smarty();
    $smarty->setTemplateDir("{$_sp}/templates");
    $smarty->setCompileDir($compile_dir);
    $smarty->assign('templateHelper', new TemplateHelper());

    return $smarty;
  }

?>
