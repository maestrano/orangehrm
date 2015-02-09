<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec Project representation to/from OrangeHRM Project
*/
class ProjectMapper extends BaseMapper {
  private $_projectService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Project';
    $this->local_entity_name = 'Project';
    $this->connec_resource_name = 'projects';
    $this->connec_resource_endpoint = 'projects';

    $this->_projectService = new ProjectService();
  }

  // Return the Project local id
  protected function getId($project) {
    return $project->projectId;
  }

  // Return a local Project by id
  protected function loadModelById($local_id) {
    return $this->_projectService->getProjectById($local_id);
  }

  // Map the Connec resource attributes onto the OrangeHRM Project
  protected function mapConnecResourceToModel($project_hash, $project) {
    // Map hash attributes to Project
    if(!is_null($project_hash['name'])) { $project->name = $project_hash['name']; }
    if(!is_null($project_hash['description'])) { $project->description = $project_hash['description']; }

    // Map Customer entity
    if(!is_null($project_hash['organization_id'])) {
      $customerMapper = new CustomerMapper();
      $customer = $customerMapper->loadModelByConnecId($project_hash['organization_id']);
      $project->Customer = $customer;
      $project->setIsDeleted(Project::ACTIVE_PROJECT);
    }
  }

  // Map the OrangeHRM Project to a Connec resource hash
  protected function mapModelToConnecResource($project) {
    $project_hash = array();

    // Map Project to Connec hash
    if(!is_null($project->name)) { $project_hash['name'] = $project->name; }
    if(!is_null($project->description)) { $project_hash['description'] = $project->description; }

    // Map Customer reference
    if(!is_null($project->customerId)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($project->customerId, 'Customer');
      if($mno_id_map) {
        $project_hash['organization_id'] = $mno_id_map['mno_entity_guid'];
      }
    }

    return $project_hash;
  }

  // Persist the OrangeHRM Project
  protected function persistLocalModel($project) {
    $project->save();
  }
}
