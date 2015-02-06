<?php

require_once 'MnoIdMap.php';

/**
* Map Connec Employee representation to/from OrangeHRM Employee
*/
class EmployeeMapper {
  private $_connec_client;
  private $_employeeService;

  public function __construct() {
    $this->_employeeService = new EmployeeService();
    $this->_connec_client = new Maestrano_Connec_Client('orangehrm.app.dev.maestrano.io');
  }

  // Persist a list of Connec Employee hashes as OrangeHRM Employees
  public function persistAll($employees_hash) {
    foreach($employees_hash as $employee_hash) {
      $this->hashToEmployee($employee_hash);
    }
  }

  // Map a Connec Employee hash to an OrangeHRM Employee
  public function hashToEmployee($employee_hash, $persist=true) {
    $employee = null;
    $map_record = false;

    // Find local Employee if exists
    $mno_id = $employee_hash['id'];
    $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($mno_id, 'Employee');
    if($mno_id_map) {
      $employee = $this->_employeeService->getEmployee($mno_id_map['app_entity_id']);
    } else {
      // Find Employee by unique employeeId
      if($employee_hash['employee_id'] != null) {
        $employee = $this->_employeeService->getEmployeeByEmployeeId($employee_hash['employee_id']);
        // Link existing record if found
        if($employee != null) { $map_record = true; }
      }
    }

    // Create a new Employee if none found
    if($employee == null) {
      $employee = new Employee();
      $map_record = true;
    }

    // Map hash attributes to Employee
    if(!is_null($employee_hash['employee_id'])) { $employee->employeeId = $employee_hash['employee_id']; }
    if(!is_null($employee_hash['first_name'])) { $employee->firstName = $employee_hash['first_name']; }
    if(!is_null($employee_hash['last_name'])) { $employee->lastName = $employee_hash['last_name']; }
    if(!is_null($employee_hash['birth_date'])) { $employee->emp_birthday = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $employee_hash['birth_date'])->format("Y-m-d"); }
    if(!is_null($employee_hash['gender'])) { $employee->emp_gender = ($employee_hash['gender'] == 'M' ? 1 : 2); } // Male: 1, Female: 2

    // TODO

    // Save and map the Employee
    if($persist) {
      $res = $this->_employeeService->saveEmployee($employee, false);
      if($map_record) {
        MnoIdMap::addMnoIdMap($employee->empNumber, 'Employee', $employee_hash['id'], 'Employee');
      }
    }

    return $employee;
  }

  public function pushToConnec($employee) {
    $employee_hash = array();
    if(!is_null($employee->employeeId)) { $employee_hash['employees']['employee_id'] = $employee->employeeId; }
    if(!is_null($employee->firstName)) { $employee_hash['employees']['first_name'] = $employee->firstName; }
    if(!is_null($employee->lastName)) { $employee_hash['employees']['last_name'] = $employee->lastName; }
    if(!is_null($employee->emp_birthday)) { $employee_hash['employees']['birth_date'] = DateTime::createFromFormat('Y-m-d', $employee->emp_birthday)->format("Y-m-d\TH:i:s\Z"); }
    if(!is_null($employee->emp_gender)) { $employee_hash['employees']['gender'] = ($employee->emp_gender) == 1 ? 'M' : 'F'; }

    // TODO

    error_log("Built employee hash: " . json_encode($employee_hash));

    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($employee->empNumber, 'Employee');
    if($mno_id_map) {
      $response = $this->_connec_client->put("employees/" . $mno_id_map['mno_entity_guid'], $employee_hash);
    } else {
      $response = $this->_connec_client->post("employees", $employee_hash);
    }

    $code = $response['code'];
    $body = $response['body'];
    if($code >= 300) {
      error_log("Cannot push to Connec! entity_name=Employee, code=$code, body=$body");
    } else {
      $result = json_decode($response['body'], true);
      error_log("processing entity_name=Employee entity=". json_encode($result));
      $employeeMapper = new EmployeeMapper();
      $employeeMapper->hashToEmployee($result['employees']);
    }
  }
}
