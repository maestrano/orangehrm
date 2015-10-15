<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec Employee representation to/from OrangeHRM Employee
*/
class EmployeeMapper extends BaseMapper {
  private $_employeeService;
  private $_jobTitleService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Employee';
    $this->local_entity_name = 'Employee';
    $this->connec_resource_name = 'employees';
    $this->connec_resource_endpoint = 'employees';

    $this->_employeeService = new EmployeeService();
    $this->_jobTitleService = new JobTitleService();
  }

  // Return the Employee local id
  protected function getId($employee) {
    return $employee->empNumber;
  }

  // Return a local Employee by id
  protected function loadModelById($local_id) {
    return $this->_employeeService->getEmployee($local_id);
  }

  // Match an Employee by employee id or email
  protected function matchLocalModel($employee_hash) {
    if(!is_null($employee_hash['employee_id'])) {
      $employee = $this->_employeeService->getEmployeeByEmployeeId($employee_hash['employee_id']);
      if(!is_null($employee)) { return $employee; }
    } else if(!is_null($employee_hash['email']) && !is_null($employee_hash['email']['address'])) {
      // Match only by email if employee_id is not set, most likely created during SSO
      $employee = $this->getEmployeeByEmail($employee_hash['email']['address']);
      if(!is_null($employee)) { return $employee; }
    }
    return null;
  }

  // Map the Connec resource attributes onto the OrangeHRM Employee
  protected function mapConnecResourceToModel($employee_hash, $employee) {
    // Map hash attributes to Employee
    if(!is_null($employee_hash['employee_id'])) { $employee->employeeId = $employee_hash['employee_id']; }
    if(!is_null($employee_hash['first_name'])) { $employee->firstName = $employee_hash['first_name']; }
    if(!is_null($employee_hash['last_name'])) { $employee->lastName = $employee_hash['last_name']; }
    if(!is_null($employee_hash['birth_date'])) { $employee->emp_birthday = $employee_hash['birth_date']; }
    if(!is_null($employee_hash['gender'])) { $employee->emp_gender = ($employee_hash['gender'] == 'M' ? Employee::GENDER_MALE : Employee::GENDER_FEMALE); }
    if(!is_null($employee_hash['social_security_number'])) { $employee->ssn = $employee_hash['social_security_number']; }

    // Address
    if(!is_null($employee_hash['address']) && !is_null($employee_hash['address']['shipping'])) {
      if(!is_null($employee_hash['address']['shipping']['line1'])) { $employee->street1 = $employee_hash['address']['shipping']['line1']; }
      if(!is_null($employee_hash['address']['shipping']['line2'])) { $employee->street2 = $employee_hash['address']['shipping']['line2']; }
      if(!is_null($employee_hash['address']['shipping']['city'])) { $employee->city = $employee_hash['address']['shipping']['city']; }
      if(!is_null($employee_hash['address']['shipping']['postal_code'])) { $employee->emp_zipcode = $employee_hash['address']['shipping']['postal_code']; }
      if(!is_null($employee_hash['address']['shipping']['country'])) {
        $employee->country = $employee_hash['address']['shipping']['country'];
      } else {
        $employee->country = 'United States';
      }
      if(!is_null($employee_hash['address']['shipping']['region'])) { $employee->province = $employee_hash['address']['shipping']['region']; }
    }

    // Phone
    if(!is_null($employee_hash['phone'])) {
      if(!is_null($employee_hash['phone']['landline'])) { $employee->emp_hm_telephone = $employee_hash['phone']['landline']; }
      if(!is_null($employee_hash['phone']['landline2'])) { $employee->emp_work_telephone = $employee_hash['phone']['landline2']; }
      if(!is_null($employee_hash['phone']['mobile'])) { $employee->emp_mobile = $employee_hash['phone']['mobile']; }
    }

    // Email
    if(!is_null($employee_hash['email'])) {
      if(!is_null($employee_hash['email']['address'])) { $employee->emp_work_email = $employee_hash['email']['address']; }
      if(!is_null($employee_hash['email']['address2'])) { $employee->emp_oth_email = $employee_hash['email']['address2']; }
    }

    // Job title is mapped to a JobTitle object
    if(!is_null($employee_hash['job_title'])) {
      $employee->jobTitle = $this->findOrCreateJobTitleByName($employee_hash['job_title']);
    }

    // Job details
    if(!is_null($employee_hash['hired_date'])) { $employee->joined_date = $employee_hash['hired_date']; }

    // Work Locations
    if(!is_null($employee_hash['work_locations'])) {
      $workLocationMapper = new WorkLocationMapper();
      foreach ($employee_hash['work_locations'] as $work_location_hash) {
        $work_location = $workLocationMapper->loadModelByConnecId($work_location_hash['work_location_id']);
        $employee->locations->add($work_location);
      }
    }

    // Employee Salary
    if(!is_null($employee_hash['employee_salaries'])) {
      $employeeSalaryMapper = new EmployeeSalaryMapper();
      foreach ($employee_hash['employee_salaries'] as $employee_salary_hash) {
        $employee_salary = $employeeSalaryMapper->saveConnecResource($employee_salary_hash, false);
        if(!is_null($employee_salary) && $employee_salary->isNew()) { $employee->salary->add($employee_salary); }
      }
    }
  }

  // Map the OrangeHRM Employee to a Connec resource hash
  protected function mapModelToConnecResource($employee) {
    $employee_hash = array();

    // Map Employee to Connec hash
    if(!is_null($employee->employeeId)) { $employee_hash['employee_id'] = $employee->employeeId; }
    if(!is_null($employee->firstName)) { $employee_hash['first_name'] = $employee->firstName; }
    if(!is_null($employee->lastName)) { $employee_hash['last_name'] = $employee->lastName; }
    if(!is_null($employee->emp_birthday)) { $employee_hash['birth_date'] = $employee->emp_birthday; }
    if(!is_null($employee->emp_gender)) { $employee_hash['gender'] = ($employee->emp_gender) == Employee::GENDER_MALE ? 'M' : 'F'; }
    if(!is_null($employee->ssn)) { $employee_hash['social_security_number'] = $employee->ssn; }

    // Address
    if(!is_null($employee->street1)) { $employee_hash['address']['shipping']['line1'] = $employee->street1; }
    if(!is_null($employee->street2)) { $employee_hash['address']['shipping']['line2'] = $employee->street2; }
    if(!is_null($employee->city)) { $employee_hash['address']['shipping']['city'] = $employee->city; }
    if(!is_null($employee->emp_zipcode)) { $employee_hash['address']['shipping']['postal_code'] = $employee->emp_zipcode; }
    if(!is_null($employee->country)) { $employee_hash['address']['shipping']['country'] = $employee->country; }
    if(!is_null($employee->province)) { $employee_hash['address']['shipping']['region'] = $employee->province; }

    // Phone
    if(!is_null($employee->emp_hm_telephone)) { $employee_hash['phone']['landline'] = $employee->emp_hm_telephone; }
    if(!is_null($employee->emp_work_telephone)) { $employee_hash['phone']['landline2'] = $employee->emp_work_telephone; }
    if(!is_null($employee->emp_mobile)) { $employee_hash['phone']['mobile'] = $employee->emp_mobile; }

    // Email
    if(!is_null($employee->emp_work_email)) { $employee_hash['email']['address'] = $employee->emp_work_email; }
    if(!is_null($employee->emp_oth_email)) { $employee_hash['email']['address2'] = $employee->emp_oth_email; }

    // Job title
    if(!is_null($employee->jobTitle)) { $employee_hash['job_title'] = $employee->jobTitle->jobTitleName; }
    if(!is_null($employee->joined_date)) { $employee_hash['hired_date'] = $employee->joined_date; }

    // Work Locations
    if(!is_null($employee->locations)) {
      $workLocationMapper = new WorkLocationMapper();
      $employee_hash['work_locations'] = array();
      foreach ($employee->locations as $location) {
        $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($location->id, 'LOCATION');
        if($mno_id_map) { $employee_hash['work_locations'][] = array('work_location_id' => $mno_id_map['mno_entity_guid']); }
      }
    }

    return $employee_hash;
  }

  // Persist the OrangeHRM Employee
  protected function persistLocalModel($employee, $resource_hash) {
    $this->_employeeService->saveEmployee($employee, false);

    // Map Employee Salary IDs
    $employeeSalaryMapper = new EmployeeSalaryMapper();
    foreach ($resource_hash['employee_salaries'] as $index => $employee_salary_hash) {
      $salary_array = $employee->salary->getData();
      $employeeSalary = $salary_array[$index];
      $employeeSalaryMapper->findOrCreateIdMap($employee_salary_hash, $employeeSalary);
    }
  }

  // Find or Create an OrangeHRM JobTitle object by its name
  private function findOrCreateJobTitleByName($jobTitleName) {
    $job_list = $this->_jobTitleService->getJobTitleList();
    foreach ($job_list as $job) {
      if($job->jobTitleName == $jobTitleName) { return $job; }
    }
    
    $job = new JobTitle();
    $job->jobTitleName = $jobTitleName;
    return $job->save();
  }

  public function getEmployeeByEmail($email) {
    $q = Doctrine_Query::create()
                       ->from('Employee')
                       ->where('emp_work_email = ?', trim($email));
    $result = $q->fetchOne();
    if (!$result) { return null; }
    return $result;
  }
}
