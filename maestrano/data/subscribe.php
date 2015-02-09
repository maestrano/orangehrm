<?php

require_once '../init.php';
require_once '../connec/init.php';

try {
  $client = new Maestrano_Connec_Client('orangehrm.app.dev.maestrano.io');

  $notification = json_decode(file_get_contents('php://input'), false);
  $entity_name = strtoupper(trim($notification->entity));
  $entity_id = $notification->id;

  error_log("Received notification = ". json_encode($notification));

  switch ($entity_name) {
    case "COMPANYS":
      $msg = $client->get("companies/$entity_id");
      $code = $msg['code'];

      if($code != 200) {
        error_log("Cannot fetch Connec! entity code=$code, entity_name=$entity_name, entity_id=$entity_id");
      } else {
        $result = json_decode($msg['body'], true);
        error_log("processing entity_name=$entity_name entity=". json_encode($result));
        $companyMapper = new CompanyMapper();
        $companyMapper->saveConnecResource($result['company']);
      }
      break;
    case "EMPLOYEES":
      $msg = $client->get("employees/$entity_id");
      $code = $msg['code'];

      if($code != 200) {
        error_log("Cannot fetch Connec! entity code=$code, entity_name=$entity_name, entity_id=$entity_id");
      } else {
        $result = json_decode($msg['body'], true);
        error_log("processing entity_name=$entity_name entity=". json_encode($result));
        $employeeMapper = new EmployeeMapper();
        $employeeMapper->saveConnecResource($result['employees']);
      }
      break;
  }
} catch (Exception $e) {
  error_log("Caught exception in subscribe " . json_encode($e->getMessage()));
}

?>
