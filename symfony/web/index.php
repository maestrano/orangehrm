<?php

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

// Hook:Maestrano
// Load Maestrano Library
require ROOT_PATH . '/vendor/autoload.php';
// Configure Maestrano API
Maestrano::configure(ROOT_PATH . '/maestrano.json');

// Load custom Maestrano configuration
require_once '../../maestrano/init.php';
require_once '../../maestrano/connec/init.php';

require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');
$configuration = ProjectConfiguration::getApplicationConfiguration('orangehrm', 'prod', false);
sfContext::createInstance($configuration)->dispatch();
