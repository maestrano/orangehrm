<?php

require_once '../../app/init.php';

try {
  $notification = json_decode(file_get_contents('php://input'), false);
  $notification_entity = strtoupper(trim($notification->entity));

  error_log("Notification = ". json_encode($notification));

  switch ($notification_entity) {
    case "COMPANY":
      if (class_exists('MnoSoaCompany')) {
        $mno_company = new MnoSoaCompany($opts['db_connection'], $log);
        $mno_company->receiveNotification($notification);
      }
      break;
    case "EMPLOYEES":
      if (class_exists('MnoSoaAccount')) {
        $mno_account = new MnoSoaAccount($opts['db_connection'], $log);
        $mno_account->receiveNotification($notification);
      }
      break;
  }
} catch (Exception $e) {
  $log->debug("Caught exception in subscribe " . json_encode($e->getMessage()));
}

?>
