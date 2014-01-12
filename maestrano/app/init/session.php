<?php
//-----------------------------------------------
// Define root folder
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}

//-----------------------------------------------
// Load Libraries & Settings
//-----------------------------------------------
require MAESTRANO_ROOT . '/app/init/_lib_loader.php';
require MAESTRANO_ROOT . '/app/init/_config_loader.php'; //set $mno_settings variable

//------------------------------------------------
// Preparation
//------------------------------------------------
// Store accessed url in session
$mno_session = null;
if (isset($_SESSION) && $mno_settings->sso_enabled) {
  $_SESSION['mno_previous_url'] = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $mno_session = new MnoSsoSession($mno_settings, $_SESSION);
}

return $mno_session;
  
