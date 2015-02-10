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

    // Map TimeSheetLines/TimeActivities
    if(!is_null($timesheet_hash['time_sheet_lines'])) {
      $timesheetItemMapper = new TimesheetItemMapper();
      foreach ($timesheet_hash['time_sheet_lines'] as $time_sheet_line) {
        foreach ($time_sheet_line['time_activities'] as $time_activity_hash) {
          // Find or create timesheet item without saving it
          $timesheetItem = $this->findMatchingTimeActivity($timesheet, $time_sheet_line, $time_activity_hash);
          $timesheetItem = $timesheetItemMapper->saveConnecResource($time_activity_hash, false, $timesheetItem);
          $timesheetItem->Timesheet = $timesheet;

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
            $timesheetItem->ProjectActivity = $timesheetItem->Project->ProjectActivity->getFirst();
          }

          if($timesheetItem->isNew()) { $timesheet->TimesheetItem->add($timesheetItem); }
        }
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
      foreach ($activities_items as $activityId => $timesheetItems) {
        $time_sheet_line = array('time_activities' => array());
        $timesheet_hash['time_sheet_lines'][] = $time_sheet_line;

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
      }
    }

    // TODO: Map status
    $timesheet_hash['status'] = 'ACTIVE';

    return $timesheet_hash;
  }

  // Persist the OrangeHRM Timesheet
  protected function persistLocalModel($timesheet, $timesheet_hash) {
    $this->_timesheetService->saveTimesheet($timesheet, false);

    // Map TimesheetItem IDs
    $timesheetItemMapper = new TimesheetItemMapper();
    foreach ($timesheet_hash['time_sheet_lines'] as $time_sheet_line) {
      foreach ($time_sheet_line['time_activities'] as $time_activity_hash) {
        $timesheetItem = $this->findMatchingTimeActivity($timesheet, $time_sheet_line, $time_activity_hash);
        $timesheetItemMapper->findOrCreateIdMap($time_activity_hash, $timesheetItem);
      }
    }
  }

  // Find a TimesheetItem by ProjectActivity and Date
  private function findMatchingTimeActivity($timesheet, $time_sheet_line, $time_activity_hash) {
    $projectActivityMapper = new ProjectActivityMapper();
    foreach ($timesheet->TimesheetItem as $timesheetItem) {
      $projectActivity = $projectActivityMapper->loadModelByConnecId($time_sheet_line['project_task_id']);
      if(strtotime($timesheet->endDate) == strtotime($time_activity_hash['transaction_date']) && $timesheetItem->activityId == $projectActivity->activityId) {
        return $timesheetItem;
      }
    }
    return null;
  }
}
