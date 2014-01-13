<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('MY_APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
//require MY_APP_DIR . '/include/somefiles.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// Initialize symfony app
define('SF_APP_NAME', 'orangehrm');
require_once(MY_APP_DIR . '/symfony/config/ProjectConfiguration.class.php');
$configuration = ProjectConfiguration::getApplicationConfiguration(SF_APP_NAME, 'prod', true);
new sfDatabaseManager($configuration);
$context = sfContext::createInstance($configuration);


