<?php

require_once '../init.php';

try {
  $notification = json_decode(file_get_contents('php://input'), false);
  $notification_entity = strtoupper(trim($notification->entity));

  error_log("Received notification = ". json_encode($notification));

  switch ($notification_entity) {
    case "COMPANY":
    // TODO
      break;
    case "EMPLOYEES":
      $employeeMapper = new EmployeeMapper();
      $employeeMapper->hashToEmployee($notification['Employee']);
      break;
  }
} catch (Exception $e) {
  error_log("Caught exception in subscribe " . json_encode($e->getMessage()));
}

?>
