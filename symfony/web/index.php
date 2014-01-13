<?php

// Is Maestrano enabled?
$maestrano_enabled = true;

/* For logging PHP errors */
include_once('../../lib/confs/log_settings.php');

/* Added for compatibility with current orangehrm code 
 * OrangeHRM Root directory 
 */
define('ROOT_PATH', dirname(__FILE__) . '/../../');
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
define('WPATH', $scriptPath . "/../../");

/* Redirect to installer if not set up */
if (!is_file(ROOT_PATH . '/lib/confs/Conf.php')) {
    header('Location: ' . WPATH . 'install.php');
    exit();
}

require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

// Hook:Maestrano
// Load Maestrano session
if ($maestrano_enabled) {
  require ROOT_PATH . '/maestrano/app/init/session.php';
  
  // Require authentication straight away if intranet
  // mode enabled
  if ($mno_settings && $mno_settings->sso_enabled && $mno_settings->sso_intranet_mode && $mno_session) {
    if (!$mno_session->isValid()) {
      header("Location: " . $mno_settings->sso_init_url);
    }
  }
}

$configuration = ProjectConfiguration::getApplicationConfiguration('orangehrm', 'prod', false);
sfContext::createInstance($configuration)->dispatch();
