<?php

// require_once '../init.php';
require_once(dirname(__FILE__).'/../../upgrader/config/ProjectConfiguration.class.php');
require_once(dirname(__FILE__).'/../../lib/confs/Conf.php');

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

// Current and target versions
$startVersion = '3.1.2';
$startIncrement = $upgraderUtility->getStartIncrementNumber('3.1.2');
$endVersion = $upgraderUtility->getNewVersion();
$endIncrement = $upgraderUtility->getEndIncrementNumber();

$result = $upgraderUtility->executeSql("SELECT * FROM ohrm_upgrade_history ORDER BY upgraded_date DESC LIMIT 1");
if($result->num_rows) {
  $row = $upgraderUtility->fetchArray($result);
  $startVersion = $row['end_version'];
  $startIncrement = $row['end_increment'];
}

// Apply database migrations if required
if($startIncrement != $endIncrement) {
  for ($i=$startIncrement; $i<=$endIncrement; $i++) {
    $className      = 'SchemaIncrementTask' . $i;
    $schemaObject   = new $className($dbInfo);
    $schemaObject->execute();
  }

  // Log upgrade
  $date = gmdate("Y-m-d H:i:s", time());
  $result = $upgraderUtility->insertUpgradeHistory($startVersion, $endVersion, $startIncrement, $endIncrement, $date);
}
