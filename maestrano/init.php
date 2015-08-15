<?php

// Define maestrano root path
if (!defined('MAESTRANO_ROOT')) { define("MAESTRANO_ROOT", realpath(dirname(__FILE__))); }

// Define application root path
if (!defined('ROOT_PATH')) { define('ROOT_PATH', realpath(MAESTRANO_ROOT . '/../')); }

// Include Maestrano required libraries
require_once ROOT_PATH . '/vendor/autoload.php';
Maestrano::configure(ROOT_PATH . '/maestrano.json');
require_once 'app/sso/MnoSsoUser.php';

// Initialize symfony app
define('SF_APP_NAME', 'orangehrm');
require_once(ROOT_PATH . '/symfony/config/ProjectConfiguration.class.php');
$configuration = ProjectConfiguration::getApplicationConfiguration(SF_APP_NAME, 'prod', true);
new sfDatabaseManager($configuration);
$context = sfContext::createInstance($configuration);
