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

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
//define('MY_APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
//require MY_APP_DIR . '/include/some_class_file.php';
//require MY_APP_DIR . '/config/some_database_config_file.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
// e.g:
// $opts = array();
// if (!empty($db_name) and !empty($db_user)) {
//     $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
//     
//     $opts['db_connection'] = $conn;
// }


