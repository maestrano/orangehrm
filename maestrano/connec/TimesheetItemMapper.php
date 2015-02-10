<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec TimeActivity representation to/from OrangeHRM TimesheetItem
*/
class TimesheetItemMapper extends BaseMapper {
  private $_timesheetService;
  private $_timesheetDao;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'TimeActivity';
    $this->local_entity_name = 'TimesheetItem';
    $this->connec_resource_name = 'time_activities';
    $this->connec_resource_endpoint = 'time_sheets/:time_sheet_id/time_activities';

    $this->_timesheetService = new TimesheetService();
    $this->_timesheetDao = new TimesheetDao();
  }

  // Return the TimesheetItem local id
  protected function getId($timesheetItem) {
    return $timesheetItem->timesheetItemId;
  }

  // Return a local TimesheetItem by id
  protected function loadModelById($local_id) {
    return $this->_timesheetService->getTimesheetItemById($timesheetItemId);
  }

  // Map the Connec resource attributes onto the OrangeHRM TimesheetItem
  protected function mapConnecResourceToModel($time_activity_hash, $timesheetItem) {
    // Map hash attributes to TimesheetItem
    if(!is_null($time_activity_hash['description'])) { $timesheetItem->comment = $time_activity_hash['description']; }
    if(!is_null($time_activity_hash['transaction_date'])) { $timesheetItem->date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $time_activity_hash['transaction_date'])->format("Y-m-d"); }
    
    $duration = 0;
    if(!is_null($time_activity_hash['hours'])) { $duration += $time_activity_hash['hours'] * 60 * 60; }
    if(!is_null($time_activity_hash['minutes'])) { $duration += $time_activity_hash['minutes'] * 60; }
    $timesheetItem->duration = $duration;

    // Map Employee
    if(!is_null($time_activity_hash['employee_id'])) {
      $employeeMapper = new EmployeeMapper();
      $employee = $employeeMapper->loadModelByConnecId($time_activity_hash['employee_id']);
      $timesheetItem->Employee = $employee;
    }

    // Map Project
    $projectMapper = new ProjectMapper();
    if(!is_null($time_activity_hash['project_id'])) {
      $project = $projectMapper->loadModelByConnecId($time_activity_hash['project_id']);
      $timesheetItem->Project = $project;
    } else {
      // Assign default Project if none set
      $timesheetItem->Project = $projectMapper->defaultProject();
    }

    // Map Project Activity
    if(!is_null($time_activity_hash['project_task_id'])) {
      $projectActivityMapper = new ProjectActivityMapper();
      $projectActivity = $projectActivityMapper->loadModelByConnecId($time_activity_hash['project_task_id']);
      $timesheetItem->ProjectActivity = $projectActivity;
    } else {
      // Assign default Project Activity if none set
      $timesheetItem->ProjectActivity = $timesheetItem->Project->ProjectActivity->getFirst();
    }

    // Generate ID if none set
    if(!$timesheetItem->timesheetItemId) {
      $idGenService = new IDGeneratorService();
      $idGenService->setEntity($timesheetItem);
      $timesheetItem->setTimesheetItemId($idGenService->getNextID());
    }
  }

  // Map the OrangeHRM TimesheetItem to a Connec resource hash
  protected function mapModelToConnecResource($timesheetItem) {
    $time_activity_hash = array();

    // Map TimesheetItem to Connec hash
    if(!is_null($timesheetItem->duration)) {
      $hours = intval($timesheetItem->duration / 60 / 60);
      $minutes = intval($timesheetItem->duration / 60) % 60;
      $time_activity_hash['hours'] = $hours;
      $time_activity_hash['minutes'] = $minutes;
    }

    if(!is_null($timesheetItem->comment)) { $time_activity_hash['description'] = $timesheetItem->comment; }
    if(!is_null($timesheetItem->date)) { $time_activity_hash['transaction_date'] = DateTime::createFromFormat('Y-m-d', $timesheetItem->date)->format("Y-m-d\TH:i:s\Z"); }

    // Map Employee
    if(!is_null($timesheetItem->employeeId)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheetItem->employeeId, 'Employee');
      if($mno_id_map) { $time_activity_hash['employee_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map Project
    if(!is_null($timesheetItem->projectId)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheetItem->projectId, 'Project');
      if($mno_id_map) { $time_activity_hash['project_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map ProjectActivity
    if(!is_null($timesheetItem->activityId)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($timesheetItem->activityId, 'ProjectActivity');
      if($mno_id_map) { $time_activity_hash['project_activity_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map task id
    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($this->getId($timesheetItem), $this->local_entity_name);
    if($mno_id_map) { $time_activity_hash['id'] = $mno_id_map['mno_entity_guid']; }

    return $time_activity_hash;
  }

  // Persist the OrangeHRM TimesheetItem
  protected function persistLocalModel($timesheetItem, $resource_hash) {
    $this->_timesheetDao->saveTimesheetItem($timesheetItem);
  }
}
