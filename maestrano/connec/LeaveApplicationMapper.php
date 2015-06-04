<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec LeaveApplication representation to/from OrangeHRM LeaveRequest
*/
class LeaveApplicationMapper extends BaseMapper {

  private $leaveTypeService;
  private $leaveRequestService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'LeaveApplication';
    $this->local_entity_name = 'LeaveRequest';
    $this->connec_resource_name = 'leave_applications';
    $this->connec_resource_endpoint = 'leave_applications';

    $this->leaveTypeService = new LeaveTypeService();
    $this->leaveRequestService = new LeaveRequestService();
  }

  // Return the LeaveRequest local id
  protected function getId($leave_request) {
    return $leave_request->id;
  }

  // Return a local LeaveRequest by id
  protected function loadModelById($local_id) {
    return $this->leaveRequestService->fetchLeaveRequest($local_id);
  }

  protected function validate($leave_application_hash) {
    return !empty($leave_application_hash['employee_id']) && !empty($leave_application_hash['pay_item_id']);
  }

  // Map the Connec resource attributes onto the OrangeHRM LeaveRequest
  protected function mapConnecResourceToModel($leave_application_hash, $leave_request) {
    // Map hash attributes to LeaveRequest

    if(!is_null($leave_application_hash['title'])) { $leave_request->comments = $leave_application_hash['title']; }
    else if(!is_null($leave_application_hash['description'])) { $leave_request->comments = $leave_application_hash['description']; }
    else { $leave_request->comments = 'Generated leave request'; }

    // Map Employee
    if(!is_null($leave_application_hash['employee_id'])) {
      $employeeMapper = new EmployeeMapper();
      $employee = $employeeMapper->loadModelByConnecId($leave_application_hash['employee_id']);
      $leave_request->Employee = $employee;
    }

    // Map Leave Type
    if(!is_null($leave_application_hash['pay_item_id'])) {
      $payItemMapper = new PayItemMapper();
      $leaveType = $payItemMapper->loadModelByConnecId($leave_application_hash['pay_item_id']);
      $leave_request->LeaveType = $leaveType;
    }
  }

  // Map the OrangeHRM LeaveRequest to a Connec resource hash
  protected function mapModelToConnecResource($leave_request) {
    $leave_application_hash = array();

    if(!is_null($leave_request->comments)) { $leave_application_hash['title'] = $leave_request->comments; }

    // Leave requests date range
    $date_range = $leave_request->getLeaveStartAndEndDate();
    $leave_application_hash['start_date'] = $this->dateStringToTime($date_range[0]);
    $leave_application_hash['end_date'] = $this->dateStringToTime($date_range[1]);

    // Map Employee
    if(!is_null($leave_request->emp_number)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($leave_request->emp_number, 'Employee');
      if($mno_id_map) { $leave_application_hash['employee_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map Leave Type
    if(!is_null($leave_request->leave_type_id)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($leave_request->leave_type_id, 'LeaveType');
      if($mno_id_map) { $leave_application_hash['pay_item_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Leave periods
    $leave_application_hash['leave_periods'] = array();
    foreach ($leave_request->Leave as $leave) {
      $leave_period_hash = array();

      if(!is_null($leave->length_hours)) { $leave_period_hash['units'] = $leave->length_hours; }
      if(!is_null($leave->status)) { $leave_period_hash['status'] = Leave::getTextForLeaveStatus($leave->status); }

      if(!is_null($leave->date)) {
        $leave_period_hash['start_date'] = $this->dateStringToTime($leave->date);
        $leave_period_hash['end_date'] = $this->dateStringToTime($leave->date);
      }

      $leave_application_hash['leave_periods'][] = $leave_period_hash;
    }

    return $leave_application_hash;
  }

  // Persist the OrangeHRM LeaveRequest
  protected function persistLocalModel($leave_request, $leave_application_hash) {
    $parameters = array();
    $parameters['txtEmpID'] = $leave_request->Employee->empNumber;
    $parameters['txtEmployee'] = array('empId' => $leave_request->Employee->empNumber);
    $parameters['txtLeaveType'] = $leave_request->LeaveType->id;
    $parameters['txtComment'] = $leave_request->comments;
    $parameters['txtEmpWorkShift'] = "8";
    $parameters['txtLeaveTotalTime'] = "8.00";
    $parameters['txtFromDate'] = $this->timeToDateString($leave_application_hash['start_date']);
    $parameters['txtToDate'] = $this->timeToDateString($leave_application_hash['end_date']);
    $parameters['duration'] = array('duration'=>'full_day', 'ampm'=>'AM', 'time'=>array('from'=>'09:00', 'to'=>'17:00'));

    $leaveParameterObject = new LeaveParameterObject($parameters);
    $leaveAssignmentService = new LeaveAssignmentService();
    $accessFlowStateMachineDao = new AccessFlowStateMachineDao();
    $leaveAssignmentService->assignWorkflowItem = $accessFlowStateMachineDao->getWorkflowItemByStateActionAndRole(WorkflowStateMachine::FLOW_LEAVE, 'INITIAL', 'ASSIGN', 'ADMIN');
    $leaveAssignmentService->assignLeave($leaveParameterObject, false);
  }
}
