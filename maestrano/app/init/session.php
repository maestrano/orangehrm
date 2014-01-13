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
require MAESTRANO_ROOT . '/app/init/_config_loader.php'; //configure MaestranoService

//------------------------------------------------
// Preparation
//------------------------------------------------
// Store accessed url in session
$_SESSION['mno_previous_url'] = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  
