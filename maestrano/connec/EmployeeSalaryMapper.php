<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec EmployeeSalary representation to/from OrangeHRM EmployeeSalary
*/
class EmployeeSalaryMapper extends BaseMapper {
  private $employee = null;

  public function __construct($employee) {
    parent::__construct();

    $this->connec_entity_name = 'EmployeeSalary';
    $this->local_entity_name = 'EmployeeSalary';
    $this->connec_resource_name = 'employees';
    $this->connec_resource_endpoint = 'employees/:employee_id/employee_salaries';

    $this->employee = $employee;
  }

  // Return the EmployeeSalary local id
  protected function getId($employeeSalary) {
    return $employeeSalary->id;
  }

  // Find existing matching salary
  protected function loadModelById($local_id) {
    foreach ($this->employee->salary as $salary) {
      if($salary->id == $local_id) { return $salary; }
    }
    return null;
  }

  // Consider only Annual Salary
  protected function validate($resource_hash) {
    return $resource_hash['type'] == 'SALARY';
  }

  // Map the Connec resource attributes onto the OrangeHRM EmployeeSalary
  protected function mapConnecResourceToModel($employee_salary_hash, $employeeSalary) {
    // Map hash attributes to EmployeeSalary
    $employeeSalary->employee = $employee;

    if(!is_null($employee_salary_hash['name'])) {
      $employeeSalary->salaryName = $employee_salary_hash['name'];
    } else {
      $employeeSalary->salaryName = 'Default Salary';
    }

    if(!is_null($employee_salary_hash['annual_salary'])) { $employeeSalary->amount = $employee_salary_hash['annual_salary']; }
    
    if(!is_null($employee_salary_hash['currency'])) {
      $employeeSalary->currencyCode = $employee_salary_hash['currency'];
    } else {
      $employeeSalary->currencyCode = 'USD';
    }

    // Default to monthly pay period
    $employeeSalary->payPeriodId = $this->findPayPeriod('Monthly');
  }

  // Map the OrangeHRM EmployeeSalary to a Connec resource hash
  protected function mapModelToConnecResource($employeeSalary) {
    $employee_salary_hash = array();

    // Map EmployeeSalary to Connec hash
    $employeeSalary['type'] = 'SALARY';
    if(!is_null($employeeSalary->salaryName)) { $employee_salary_hash['name'] = $employeeSalary->salaryName; }
    if(!is_null($employeeSalary->amount)) { $employee_salary_hash['annual_salary'] = $employeeSalary->amount; }
    if(!is_null($employeeSalary->currencyType)) { $employee_salary_hash['currency'] = $employeeSalary->currencyType->currency_id; }

    // Map connec id
    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($this->getId($employeeSalary), $this->local_entity_name);
    if($mno_id_map) { $employee_salary_hash['id'] = $mno_id_map['mno_entity_guid']; }

    return $employee_salary_hash;
  }

  // Persist the OrangeHRM EmployeeSalary
  protected function persistLocalModel($employeeSalary, $resource_hash) {
    $this->_timesheetDao->saveEmployeeSalary($employeeSalary);
  }

  private function findPayPeriod($type) {
    $payPeriods = Doctrine::getTable('Payperiod')->findAll();
    foreach ($payPeriods as $payPeriod) {
      if($payPeriod->getName() == $type) { return $payPeriod->getCode(); }
    }
    return null;
  }
}
