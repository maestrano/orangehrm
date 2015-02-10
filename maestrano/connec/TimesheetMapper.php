<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec TimeSheet representation to/from OrangeHRM Timesheet
*/
class TimesheetMapper extends BaseMapper {
  private $_timesheetService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'TimeSheet';
    $this->local_entity_name = 'Timesheet';
    $this->connec_resource_name = 'time_sheets';
    $this->connec_resource_endpoint = 'time_sheets';

    $this->_timesheetService = new TimesheetService();
  }

  // Return the Timesheet local id
  protected function getId($timesheet) {
    return $timesheet->timesheetId;
  }

  // Return a local Timesheet by id
  protected function loadModelById($local_id) {
    return $this->_timesheetService->getTimesheetById($local_id);
  }

  // Map the Connec resource attributes onto the OrangeHRM Timesheet
  protected function mapConnecResourceToModel($timesheet_hash, $timesheet) {
    // Map hash attributes to Timesheet

    if(!is_null($timesheet_hash['start_date'])) { $timesheet->startDate = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $timesheet_hash['start_date'])->format("Y-m-d"); }
    if(!is_null($timesheet_hash['end_date'])) { $timesheet->endDate = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $timesheet_hash['end_date'])->format("Y-m-d"); }

    // Map Employee entity
    if(!is_null($timesheet_hash['employee_id'])) {
      $employeeMapper = new EmployeeMapper();
      $employee = $employeeMapper->loadModelByConnecId($timesheet_hash['employee_id']);
      $timesheet->employeeId = $employeeMapper->getId($employee);
    }

    // Map Time Activities
    if(!is_null($timesheet_hash['time_activities'])) {
      $timesheetItemMapper = new TimesheetItemMapper();
      foreach ($timesheet_hash['time_activities'] as $time_activity_hash) {
        // Find or create timesheet item without saving it
        $timesheetItem = $this->findMatchingTimeActivity($timesheet, $time_activity_hash);
        $timesheetItem = $timesheetItemMapper->saveConnecResource($time_activity_hash, false, $timesheetItem);
        $timesheetItem->Timesheet = $timesheet;
        $timesheet->TimesheetItem->add($timesheetItem);
      }
    }

    // TODO: Map status
    $timesheet->state = 'NOT SUBMITTED';
  }

  // Map the OrangeHRM Timesheet to a Connec resource hash
  protected function mapModelToConnecResource($timesheet) {
    $timesheet_hash = array();

    // Map Timesheet to Connec hash
    
    // Start and End dates can appear in two different formats
    if(!is_null($timesheet->startDate)) {
      $startDate = DateTime::createFromFormat('Y-m-d H:i', $timesheet->startDate);
      if(!$startDate) {
        $startDate = DateTime::createFromFormat('Y-m-d', $timesheet->startDate);
      }
      $timesheet_hash['start_date'] = $startDate->format("Y-m-d\TH:i:s\Z");
    }
    if(!is_null($timesheet->endDate)) {
      $endDate = DateTime::createFromFormat('Y-m-d H:i', $timesheet->endDate);
      if(!$endDate) {
        $endDate = DateTime::createFromFormat('Y-m-d', $timesheet->endDate);
      }
      $timesheet_hash['end_date'] = $endDate->format("Y-m-d\TH:i:s\Z");
    }

    // Map Employee reference
    if(!is_null($timesheet->employeeId)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheet->employeeId, 'Employee');
      if($mno_id_map) {
        $timesheet_hash['employee_id'] = $mno_id_map['mno_entity_guid'];
      }
    }

    // Map Time Activities
    if(!is_null($timesheet->TimesheetItem)) {
      $timesheet_hash['time_activities'] = array();
      $timesheetItemMapper = new TimesheetItemMapper();
      foreach ($timesheet->TimesheetItem as $timesheetItem) {
        $task_hash = $timesheetItemMapper->mapModelToConnecResource($timesheetItem);
        $timesheet_hash['time_activities'][] = $task_hash;
      }
    }

    // TODO: Map status
    $timesheet_hash['status'] = 'ACTIVE';

    return $timesheet_hash;
  }

  // Persist the OrangeHRM Timesheet
  protected function persistLocalModel($timesheet, $resource_hash) {
    $this->_timesheetService->saveTimesheet($timesheet, false);
  }

  private function findMatchingTimeActivity($timesheet, $time_activity_hash) {
    foreach ($timesheet->TimesheetItem as $timesheetItem) {
      if(strtotime($timesheet->endDate) == strtotime($time_activity_hash['transaction_date']) ) {
        return $timesheetItem;
      }
    }
    return null;
  }
}
