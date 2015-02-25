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

    if(!is_null($timesheet_hash['start_date'])) { $timesheet->startDate = $timesheet_hash['start_date']; }
    if(!is_null($timesheet_hash['end_date'])) { $timesheet->endDate = $timesheet_hash['end_date']; }

    // Map Employee entity
    if(!is_null($timesheet_hash['employee_id'])) {
      $employeeMapper = new EmployeeMapper();
      $employee = $employeeMapper->loadModelByConnecId($timesheet_hash['employee_id']);
      $timesheet->employeeId = $employeeMapper->getId($employee);
    }

    // Map TimeSheetLines/TimeActivities
    if(!is_null($timesheet_hash['time_sheet_lines'])) {
      $timesheetItemMapper = new TimesheetItemMapper();
      
      // Process each TimeSheetLine
      foreach ($timesheet_hash['time_sheet_lines'] as $time_sheet_line) {
        // Iterate over each TimeSheet day
        $begin = new DateTime($timesheet->startDate);
        $end = new DateTime($timesheet->endDate);
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach($period as $date) {
          $date_time_activity_hash = $this->findTimeActivity($time_sheet_line, $date);
          // If no TimeActivity is returned for that day, create a default one if none exists
          if(is_null($date_time_activity_hash)) {
            $date_time_activity_hash = array();
            $timesheetItem = $this->findMatchingTimeActivity($timesheet, $time_sheet_line, $date->format("Y-m-d"));
            if(is_null($timesheetItem)) {
              $timesheetItem = new TimesheetItem();
              $timesheetItem->date = $date->format("Y-m-d");
              $timesheetItem->duration = 0;
              $timesheetItem->Employee = $employee;
            }
          } else {
            $timesheetItem = $this->findMatchingTimeActivity($timesheet, $time_sheet_line, $date_time_activity_hash['transaction_date']);
          }

          $timesheetItem = $timesheetItemMapper->saveConnecResource($date_time_activity_hash, false, $timesheetItem);

          if($timesheetItem->isNew()) {
            $timesheet->TimesheetItem->add($timesheetItem);
            $timesheetItem->Timesheet = $timesheet;
          }

          // Map Project
          $projectMapper = new ProjectMapper();
          if(!is_null($time_sheet_line['project_id'])) {
            $project = $projectMapper->loadModelByConnecId($time_sheet_line['project_id']);
            $timesheetItem->Project = $project;
          } else {
            // Assign default Project if none set
            $timesheetItem->Project = $projectMapper->defaultProject();
          }

          // Map Project Activity
          if(!is_null($time_sheet_line['project_task_id'])) {
            $projectActivityMapper = new ProjectActivityMapper();
            $projectActivity = $projectActivityMapper->loadModelByConnecId($time_sheet_line['project_task_id']);
            $timesheetItem->ProjectActivity = $projectActivity;
          } else {
            // Assign default Project Activity if none set
            $timesheetItem->ProjectActivity = $projectMapper->defaultProjectActivity("Default Activity " . $time_sheet_line['line_order']);
          }
        }
      }
    }

    // Map status
    $timesheet->state = $this->statusFromConnec($timesheet_hash['status']);
  }

  // Map the OrangeHRM Timesheet to a Connec resource hash
  protected function mapModelToConnecResource($timesheet) {
    $timesheet_hash = array();
    
    // Start and End dates can appear in two different formats
    if(!is_null($timesheet->startDate)) {
      $startDate = DateTime::createFromFormat('Y-m-d H:i', $timesheet->startDate);
      if(!$startDate) {
        $startDate = DateTime::createFromFormat('Y-m-d', $timesheet->startDate);
      }
      // $timesheet_hash['start_date'] = $startDate->format("Y-m-d\TH:i:s\Z");
      $timesheet_hash['start_date'] = $startDate->format("Y-m-d");
    }
    if(!is_null($timesheet->endDate)) {
      $endDate = DateTime::createFromFormat('Y-m-d H:i', $timesheet->endDate);
      if(!$endDate) {
        $endDate = DateTime::createFromFormat('Y-m-d', $timesheet->endDate);
      }
      // $timesheet_hash['end_date'] = $endDate->format("Y-m-d\TH:i:s\Z");
      $timesheet_hash['end_date'] = $endDate->format("Y-m-d");
    }

    // Map Employee reference
    if(!is_null($timesheet->employeeId)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheet->employeeId, 'Employee');
      if($mno_id_map) { $timesheet_hash['employee_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map Activities => [TimesheetItem] as Connec TimeSheetLines
    if(!is_null($timesheet->TimesheetItem)) {
      $timesheetItemMapper = new TimesheetItemMapper();
      $timesheet_hash['time_sheet_lines'] = array();

      // Group TimesheetItems by ProjectActivities
      $activities_items = array();
      foreach ($timesheet->TimesheetItem as $timesheetItem) {
        if(is_null($activities_items[$timesheetItem->activityId])) { $activities_items[$timesheetItem->activityId] = array(); }
        $activities_items[$timesheetItem->activityId][] = $timesheetItem;
      }

      // Create a TimeSheetLine per ProjectActivity
      asort($activities_items);
      foreach ($activities_items as $activityId => $timesheetItems) {
        $time_sheet_line = array('time_activities' => array());

        // Map Employee
        if(!is_null($timesheetItem->employeeId)) {
          $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheetItem->employeeId, 'Employee');
          if($mno_id_map) { $time_sheet_line['employee_id'] = $mno_id_map['mno_entity_guid']; }
        }

        // Map Project
        if(!is_null($timesheetItem->projectId)) {
          $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheetItem->projectId, 'Project');
          if($mno_id_map) { $time_sheet_line['project_id'] = $mno_id_map['mno_entity_guid']; }
        }

        // Map Activity
        if(!is_null($timesheetItem->activityId)) {
          $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheetItem->activityId, 'ProjectActivity');
          if($mno_id_map) { $time_sheet_line['project_task_id'] = $mno_id_map['mno_entity_guid']; }
        }

        // Create a TimeActivity per TimesheetItem
        foreach ($timesheetItems as $timesheetItem) {
          $time_activity_hash = $timesheetItemMapper->mapModelToConnecResource($timesheetItem);
          $time_sheet_line['time_activities'][] = $time_activity_hash;
        }

        $time_sheet_line['line_order'] = count($timesheet_hash['time_sheet_lines']) + 1;

        $timesheet_hash['time_sheet_lines'][] = $time_sheet_line;
      }
    }

    // Map status to Connec!
    $timesheet_hash['status'] = $this->statusFromConnec($timesheet->state);

    return $timesheet_hash;
  }

  // Persist the OrangeHRM Timesheet
  protected function persistLocalModel($timesheet, $timesheet_hash) {
    $timesheet = $this->_timesheetService->saveTimesheet($timesheet, false);

    // Map TimesheetItem IDs
    $timesheetItemMapper = new TimesheetItemMapper();
    foreach ($timesheet_hash['time_sheet_lines'] as $time_sheet_line) {
      foreach ($time_sheet_line['time_activities'] as $time_activity_hash) {
        $timesheetItem = $this->findMatchingTimeActivity($timesheet, $time_sheet_line, $time_activity_hash['transaction_date']);
        $timesheetItemMapper->findOrCreateIdMap($time_activity_hash, $timesheetItem);
      }
    }
  }

  // Find a TimesheetItem by ProjectActivity and Date
  private function findMatchingTimeActivity($timesheet, $time_sheet_line, $transaction_date) {
    $projectActivityMapper = new ProjectActivityMapper();
    $projectMapper = new ProjectMapper();

    $projectActivity = $projectActivityMapper->loadModelByConnecId($time_sheet_line['project_task_id']);
    
    // If ProjectActivity is not found, get the default one
    if(is_null($projectActivity)) {
      $projectActivity = $projectMapper->defaultProjectActivity("Default Activity " . $time_sheet_line['line_order']);
    }

    // Find the TimeSheetItem mathcing the ProjectActivity and Date
    foreach ($timesheet->TimesheetItem as $timesheetItem) {
      if(strtotime($timesheetItem->date) == strtotime($transaction_date) && $timesheetItem->activityId == $projectActivity->activityId) {
        return $timesheetItem;
      }
    }
    return null;
  }

  private function findTimeActivity($time_sheet_line, $date) {
    foreach ($time_sheet_line['time_activities'] as $time_activity_hash) {
      if($time_activity_hash['transaction_date'] == $date->format("Y-m-d")) { return $time_activity_hash; }
    }
    return null;
  }

  // Map Connec! TimeSheet status: [DRAFT, SUBMITTED, APPROVED, REJECTED, PROCESSED]
  private function statusFromConnec($status) {
    switch($status) {
      case "DRAFT":
        return 'NOT SUBMITTED';
      case "SUBMITTED":
        return 'SUBMITTED';
      case "PROCESSED":
        return 'APPROVED';
      case "APPROVED":
        return 'APPROVED';
      case "REJECTED":
        return 'REJECTED';
    }
    return $status;
  }

  // Map OrangeHRM TimeSheet status: [NOT SUBMITTED, SUBMITTED, APPROVED, REJECTED]
  private function statusToConnec($status) {
    switch($status) {
      case "NOT SUBMITTED":
        return 'DRAFT';
      case "SUBMITTED":
        return 'SUBMITTED';
      case "APPROVED":
        return 'APPROVED';
      case "REJECTED":
        return 'REJECTED';
    }
    return $status;
  }
}
