<?php

require_once '../app/init.php';
require_once '../connec/EmployeeMapper.php';

// Initialize symfony app
define('SF_APP_NAME', 'orangehrm');
require_once(ROOT_PATH . '/symfony/config/ProjectConfiguration.class.php');
$configuration = ProjectConfiguration::getApplicationConfiguration(SF_APP_NAME, 'prod', true);
new sfDatabaseManager($configuration);
$context = sfContext::createInstance($configuration);

$filepath = '../var/_data_sequence';
$status = false;

if (file_exists($filepath)) {
  $timestamp = trim(file_get_contents($filepath));
  $current_timestamp = round(microtime(true) * 1000);
  
  if (empty($timestamp)) { $timestamp = 0; } 

  $client = new Maestrano_Connec_Client('orangehrm.app.dev.maestrano.io');
  $msg = $client->get("updates/$timestamp");
  $code = $msg['code'];

  if($code != 200) {
    error_log("Cannot fetch connec updates code=$code");
  } else {
    $response = $msg['body'];
    $result = json_decode($response, true);

    $employeeMapper = new EmployeeMapper();

    foreach($result['Employees'] as $employee_hash) {
      $employee = $employeeMapper->hashToEmployee($employee_hash);
    }
  }
}

if ($status) {
  file_put_contents($filepath, $current_timestamp);
}

?>
