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
    $this->_jobTitleService = new JobTitleService();
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
      // Ignore updates for deleted Employees
      if($mno_id_map['deleted_flag'] == 1) {
        return null;
      }
      // Load the locally mapped Employee
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
    if(!is_null($employee_hash['gender'])) { $employee->emp_gender = ($employee_hash['gender'] == 'M' ? Employee::GENDER_MALE : Employee::GENDER_FEMALE); }
    if(!is_null($employee_hash['social_security_number'])) { $employee->ssn = $employee_hash['social_security_number']; }

    // Address
    if(!is_null($employee_hash['address']) && !is_null($employee_hash['address']['billing'])) {
      if(!is_null($employee_hash['address']['billing']['line1'])) { $employee->street1 = $employee_hash['address']['billing']['line1']; }
      if(!is_null($employee_hash['address']['billing']['line2'])) { $employee->street2 = $employee_hash['address']['billing']['line2']; }
      if(!is_null($employee_hash['address']['billing']['city'])) { $employee->city = $employee_hash['address']['billing']['city']; }
      if(!is_null($employee_hash['address']['billing']['postal_code'])) { $employee->emp_zipcode = $employee_hash['address']['billing']['postal_code']; }
      if(!is_null($employee_hash['address']['billing']['country'])) { $employee->country = $employee_hash['address']['billing']['country']; }
      if(!is_null($employee_hash['address']['billing']['region'])) { $employee->province = $employee_hash['address']['billing']['region']; }
    }

    // Phone
    if(!is_null($employee_hash['telephone'])) {
      if(!is_null($employee_hash['telephone']['landline'])) { $employee->emp_hm_telephone = $employee_hash['telephone']['landline']; }
      if(!is_null($employee_hash['telephone']['landline2'])) { $employee->emp_work_telephone = $employee_hash['telephone']['landline2']; }
      if(!is_null($employee_hash['telephone']['mobile'])) { $employee->emp_mobile = $employee_hash['telephone']['mobile']; }
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
    if(!is_null($employee_hash['hired_date'])) { $employee->joined_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $employee_hash['hired_date'])->format("Y-m-d"); }

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

  // Process en Employee update event
  // $pushToConnec: option to notify Connec! of the model update
  // $delete:       option to soft delete the local entity mapping amd ignore further Connec! updates
  public function processLocalUpdate($employee, $pushToConnec=true, $delete=false) {
    if($pushToConnec) {
      $this->pushToConnec($employee);
    }

    if($delete) {
      $this->flagDeletedEmployee($employee);
    }
  }

  public function pushToConnec($employee) {
    $employee_hash = array();

    // Map Employee to Connec hash
    if(!is_null($employee->employeeId)) { $employee_hash['employee_id'] = $employee->employeeId; }
    if(!is_null($employee->firstName)) { $employee_hash['first_name'] = $employee->firstName; }
    if(!is_null($employee->lastName)) { $employee_hash['last_name'] = $employee->lastName; }
    if(!is_null($employee->emp_birthday)) { $employee_hash['birth_date'] = DateTime::createFromFormat('Y-m-d', $employee->emp_birthday)->format("Y-m-d\TH:i:s\Z"); }
    if(!is_null($employee->emp_gender)) { $employee_hash['gender'] = ($employee->emp_gender) == Employee::GENDER_MALE ? 'M' : 'F'; }
    if(!is_null($employee->ssn)) { $employee_hash['social_security_number'] = $employee->ssn; }

    // Address
    if(!is_null($employee->street1)) { $employee_hash['address']['billing']['line1'] = $employee->street1; }
    if(!is_null($employee->street2)) { $employee_hash['address']['billing']['line2'] = $employee->street2; }
    if(!is_null($employee->city)) { $employee_hash['address']['billing']['city'] = $employee->city; }
    if(!is_null($employee->emp_zipcode)) { $employee_hash['address']['billing']['postal_code'] = $employee->emp_zipcode; }
    if(!is_null($employee->country)) { $employee_hash['address']['billing']['country'] = $employee->country; }
    if(!is_null($employee->province)) { $employee_hash['address']['billing']['region'] = $employee->province; }

    // Phone
    if(!is_null($employee->emp_hm_telephone)) { $employee_hash['telephone']['landline'] = $employee->emp_hm_telephone; }
    if(!is_null($employee->emp_work_telephone)) { $employee_hash['telephone']['landline2'] = $employee->emp_work_telephone; }
    if(!is_null($employee->emp_mobile)) { $employee_hash['telephone']['mobile'] = $employee->emp_mobile; }

    // Email
    if(!is_null($employee->emp_work_email)) { $employee_hash['email']['address'] = $employee->emp_work_email; }
    if(!is_null($employee->emp_oth_email)) { $employee_hash['email']['address2'] = $employee->emp_oth_email; }

    // Job title
    if(!is_null($employee->jobTitle)) { $employee_hash['job_title'] = $employee->jobTitle->jobTitleName; }
    if(!is_null($employee->joined_date)) { $employee_hash['hired_date'] = DateTime::createFromFormat('Y-m-d', $employee->joined_date)->format("Y-m-d\TH:i:s\Z"); }

    // TODO

    $hash = array('employees'=>$employee_hash);
    error_log("Built hash: " . json_encode($hash));

    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($employee->empNumber, 'Employee');
    if($mno_id_map) {
      $response = $this->_connec_client->put("employees/" . $mno_id_map['mno_entity_guid'], $hash);
    } else {
      $response = $this->_connec_client->post("employees", $hash);
    }

    $code = $response['code'];
    $body = $response['body'];
    if($code >= 300) {
      error_log("Cannot push to Connec! entity_name=Employee, code=$code, body=$body");
    } else {
      error_log("Processing Connec! response code=$code, body=$body");
      $result = json_decode($response['body'], true);
      error_log("processing entity_name=Employee entity=". json_encode($result));
      $employeeMapper = new EmployeeMapper();
      $employeeMapper->hashToEmployee($result['employees']);
    }
  }

  // Flag the local Employee mapping as deleted to ignore further updates
  public function flagDeletedEmployee($employee) {
    MnoIdMap::deleteMnoIdMap($employee->empNumber, 'Employee');
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
}
