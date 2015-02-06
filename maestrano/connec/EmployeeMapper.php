<?php

require_once 'MnoIdMap.php';

/**
* Map Connec Employee representation to/from OrangeHRM Employee
*/
class EmployeeMapper {
  private $_employeeService;

  public function __construct() {
    $this->_employeeService = new EmployeeService();
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
    $employee->employeeId = $employee_hash['employee_id'];
    $employee->firstName = $employee_hash['first_name'];
    $employee->lastName = $employee_hash['last_name'];
    // TODO

    // Save and map the Employee
    if($persist) {
      $this->_employeeService->saveEmployee($employee);
      if($map_record) {
        MnoIdMap::addMnoIdMap($employee->empNumber, 'Employee', $employee_hash['id'], 'Employee');
      }
    }

    return $employee;
  }
}
