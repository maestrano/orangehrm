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

// Define Autoload manager
$autoloadManager = new AutoloadManager();
$autoloadManager->setSaveFile(MAESTRANO_ROOT . '/app/tmp/_autoload_hash_map.php');

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
//define('MY_APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
define('MY_APP_DIR', '/Users/Arnaud/Sites/apps-dev/app-orangehrm');
//echo MY_APP_DIR;
//$autoloadManager->addFolder(MY_APP_DIR . "/symfony/");
//$autoloadManager->register();
//require MY_APP_DIR . '/symfony/lib/vendor/symfony/lib/helper/I18NHelper.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// Create doctrine connection
//echo 'TAMERE2';
define('SF_APP_NAME', 'orangehrm');
require_once(MY_APP_DIR . '/symfony/config/ProjectConfiguration.class.php');
$configuration = ProjectConfiguration::getApplicationConfiguration(SF_APP_NAME, 'prod', true);
new sfDatabaseManager($configuration);
$context = sfContext::createInstance($configuration);

//$manager = Doctrine_Manager::getInstance();
//$conn = $manager->getConnection('doctrine');

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


