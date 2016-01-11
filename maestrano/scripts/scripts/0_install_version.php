<?php

// Log the current OrangeHRM version on first install

require_once(dirname(__FILE__).'/../../../upgrader/config/ProjectConfiguration.class.php');
require_once(dirname(__FILE__).'/../../../lib/confs/Conf.php');

// Database configuration
$configuration = ProjectConfiguration::getApplicationConfiguration('upgrader', 'prod', false);
$app_instance = sfContext::createInstance($configuration);
$conf = new Conf();
$dbInfo = array(
  'host' => $conf->dbhost,
  'username' => $conf->dbuser,
  'password' => $conf->dbpass,
  'database' => $conf->dbname,
  'port' => $conf->dbport,
);

// Use the OrangeHRM upgrade utility tool
$upgraderUtility = new UpgradeUtility();
$upgraderUtility->getDbConnection($conf->dbhost, $conf->dbuser, $conf->dbpass, $conf->dbname, $conf->dbport);
$version = $upgraderUtility->getNewVersion();
$increment = $upgraderUtility->getEndIncrementNumber();

// Log upgrade
$date = gmdate("Y-m-d H:i:s", time());
$result = $upgraderUtility->insertUpgradeHistory($version, $version, $increment, $increment, $date);
