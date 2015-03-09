<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec Activity representation to/from OrangeHRM Activity
*/
class ProjectActivityMapper extends BaseMapper {
  private $_projectService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Task';
    $this->local_entity_name = 'ProjectActivity';
    $this->connec_resource_name = 'tasks';
    $this->connec_resource_endpoint = 'projects/:project_id/tasks';

    $this->_projectService = new ProjectService();
  }

  // Return the Activity local id
  protected function getId($activity) {
    return $activity->activityId;
  }

  // Return a local Activity by id
  protected function loadModelById($local_id) {
    return $this->_projectService->getProjectActivityById($local_id);
  }

  // Map the Connec resource attributes onto the OrangeHRM Activity
  protected function mapConnecResourceToModel($activity_hash, $activity) {
    // Map hash attributes to Activity
    if(!is_null($activity_hash['name'])) { $activity->name = $activity_hash['name']; }
  }

  // Map the OrangeHRM Activity to a Connec resource hash
  protected function mapModelToConnecResource($activity) {
    $activity_hash = array();

    // Map Activity to Connec hash
    if(!is_null($activity->name)) { $activity_hash['name'] = $activity->name; }

    // Map task id
    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($this->getId($activity), $this->local_entity_name);
    if($mno_id_map) { $activity_hash['id'] = $mno_id_map['mno_entity_guid']; }

    return $activity_hash;
  }

  // Persist the OrangeHRM Activity
  protected function persistLocalModel($activity, $resource_hash) {
    $activity->save();
  }
}
