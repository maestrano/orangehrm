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

    // Map Project Activities
    if(!is_null($project_hash['tasks'])) {
      $projectActivityMapper = new ProjectActivityMapper();
      foreach ($project_hash['tasks'] as $task_hash) {
        // Find or create project activity without saving it
        $activity = $this->findMatchingProjectActivity($project, $task_hash['name']);
        $activity = $projectActivityMapper->saveConnecResource($task_hash, false, $activity);
        if($activity->isNew()) { $project->ProjectActivity->add($activity); }
      }
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

    // Map Project Activities
    if(!is_null($project->ProjectActivity)) {
      $project_hash['tasks'] = array();
      $projectActivityMapper = new ProjectActivityMapper();
      foreach ($project->ProjectActivity as $projectActivity) {
        $task_hash = $projectActivityMapper->mapModelToConnecResource($projectActivity);
        $project_hash['tasks'][] = $task_hash;
      }
    }

    return $project_hash;
  }

  // Persist the OrangeHRM Project
  protected function persistLocalModel($project, $resource_hash) {
    $project->save();

    // Map Project Activities IDs
    if(!is_null($project->ProjectActivity)) {
      $projectActivityMapper = new ProjectActivityMapper();
      foreach ($project->ProjectActivity as $i => $projectActivity) {
        $projectActivityMapper->findOrCreateIdMap($resource_hash['tasks'][$i], $projectActivity);
      }
    }
  }

  // Find a ProjectActivity by name (unique inside a project)
  private function findMatchingProjectActivity($project, $activity_name) {
    $project_activities = $this->_projectService->getActivityListByProjectId($this->getId($project));
    foreach ($project_activities as $project_activity) {
      if($project_activity->name == $activity_name) { return $project_activity; }
    }
    return null; 
  }

  // Find or create a default Project. Used for Timesheet creation if no Customer/Project is defined
  public function defaultProject($name='Default Project') {
    $projects = $this->_projectService->getAllProjects();
    foreach ($projects as $project) {
      if($project->name == $name) { return $project; }
    }
    // Create default Project
    $project = new Project();
    $project->name = $name;

    // Assign to Default Customer
    $customerMapper = new CustomerMapper();
    $project->Customer = $customerMapper->defaultCustomer();
    $this->persistLocalModel($project);
    // Push to Connec!
    $this->processLocalUpdate($project);

    return $project;
  }

  // Find or create a default ProjectActivity. Used for Timesheet creation if no Customer/Project is defined
  public function defaultProjectActivity($name='Default Activity') {
    $project = $this->defaultProject();

    // Create Default Activity
    $activity = $this->findMatchingProjectActivity($project, $name);
    if($activity == null) {
      $activity = new ProjectActivity();
      $activity->Project = $project;
      $activity->name = $name;
      $project->ProjectActivity->add($activity);
      $this->persistLocalModel($project);
      // Push to Connec!
      $this->processLocalUpdate($project);
    }

    return $activity;
  }
}
