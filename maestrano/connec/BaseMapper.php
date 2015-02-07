<?php

require_once 'MnoIdMap.php';

/**
* Map Connec Resource representation to/from OrangeHRM Model
* You need to extend this class an implement the following methods:
* - getId($model) Returns the OrangeHRM entity local id
* - loadModelById($local_id) Loads the OrangeHRM entity by its id
* - mapConnecResourceToModel($resource_hash, $model) Maps the Connec resource to the OrangeHRM entity
* - mapModelToConnecResource($model) Maps the OrangeHRM entity into a Connec resource
* - persistLocalModel($model) Saves the OrangeHRM entity
* - matchLocalModel($resource_hash) (Optional) Returns an OrangeHRM entity matched by attributes
*/
abstract class BaseMapper {
  private $_connec_client;

  protected $connec_entity_name = 'Model';
  protected $local_entity_name = 'Model';

  protected $connec_resource_name = 'models';
  protected $connec_resource_endpoint = 'models';

  public function __construct() {
    $this->_connec_client = new Maestrano_Connec_Client('orangehrm.app.dev.maestrano.io');
  }

  // Overwrite me!
  // Return the Model local id
  abstract protected function getId($model);

  // Overwrite me!
  // Return a local Model by id
  abstract protected function loadModelById($local_id);

  // Overwrite me!
  // Map the Connec resource attributes onto the OrangeHRM model
  abstract protected function mapConnecResourceToModel($resource_hash, $model);

  // Overwrite me!
  // Map the OrangeHRM model to a Connec resource hash
  abstract protected function mapModelToConnecResource($model);

  // Overwrite me!
  // Persist the OrangeHRM model and returns its local id
  abstract protected function persistLocalModel($model);

  // Overwrite me!
  // Optional: Match a local Model from hash attributes
  protected function matchLocalModel($resource_hash) {
    return null;
  }

  // Persist a list of Connec Resources as OrangeHRM Models
  public function persistAll($resources_hash) {
    foreach($resources_hash as $resource_hash) {
      $this->saveConnecResource($resource_hash);
    }
  }

  // Map a Connec Resource to an OrangeHRM Model
  public function saveConnecResource($resource_hash, $persist=true) {
    // Load existing Model or create a new instance
    $model = $this->findOrInitializeModel($resource_hash);
    if(is_null($model)) { return null; }

    // Update the model attributes
    $this->mapConnecResourceToModel($resource_hash, $model);

    // Save and map the Model id to the Connec resource id
    if($persist) {
      $new_record = $model->isNew();
      $this->persistLocalModel($model);
      $local_id = $this->getId($model);
      if($new_record) { MnoIdMap::addMnoIdMap($local_id, $this->local_entity_name, $resource_hash['id'], $this->connec_entity_name); }
    }

    return $model;
  }

  // Process a Model update event
  // $pushToConnec: option to notify Connec! of the model update
  // $delete:       option to soft delete the local entity mapping amd ignore further Connec! updates
  public function processLocalUpdate($model, $pushToConnec=true, $delete=false) {
    if($pushToConnec) {
      $this->pushToConnec($model);
    }

    if($delete) {
      $this->flagAsDeleted($model);
    }
  }

  // Find an OrangeHRM entity matching the Connec resource or initialize a new one
  protected function findOrInitializeModel($resource_hash) {
    $model = null;

    // Find local Model if exists
    $mno_id = $resource_hash['id'];
    $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($mno_id, $this->connec_entity_name);
    
    if($mno_id_map) {
      // Ignore updates for deleted Models
      if($mno_id_map['deleted_flag'] == 1) { return null; }
      
      // Load the locally mapped Model
      $model = $this->loadModelById($mno_id_map['app_entity_id']);
    }

    // Match a local Model from hash attributes
    if($model == null) { $model = $this->matchLocalModel($resource_hash); }

    // Create a new Model if none found
    if($model == null) { $model = new $this->local_entity_name(); }

    return $model;
  }

  // Transform an OrangeHRM Model into a Connec Resource and push it to Connec
  protected function pushToConnec($model) {
    // Transform the Model into a Connec hash
    $resource_hash = $this->mapModelToConnecResource($model);
    $hash = array($this->connec_resource_name => $resource_hash);
error_log("PUSH HASH  => " . json_encode($resource_hash));
    // Find Connec resource id
    $local_id = $this->getId($model);
    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($local_id, $this->local_entity_name);
error_log("FIND ID FOR $local_id - $this->local_entity_name=> " . json_encode($mno_id_map));
    if($mno_id_map) {
      // Update resource
      error_log("updating entity=$this->local_entity_name id=$local_id hash=" . json_encode($hash));
      $response = $this->_connec_client->put($this->connec_resource_endpoint . '/' . $mno_id_map['mno_entity_guid'], $hash);
    } else {
      // Create resource
      error_log("creating entity=$this->local_entity_name hash=" . json_encode($hash));
      $response = $this->_connec_client->post($this->connec_resource_endpoint, $hash);
    }

    // Process Connec response
    $code = $response['code'];
    $body = $response['body'];
    if($code >= 300) {
      error_log("Cannot push to Connec! entity_name=$this->local_entity_name, code=$code, body=$body");
    } else {
      error_log("Processing Connec! response code=$code, body=$body");
      $result = json_decode($response['body'], true);
      error_log("processing entity_name=$this->local_entity_name entity=". json_encode($result));
      $this->saveConnecResource($result[$this->connec_resource_name]);
    }
  }

  // Flag the local Model mapping as deleted to ignore further updates
  protected function flagAsDeleted($model) {
    $local_id = $this->getId($model);
    MnoIdMap::deleteMnoIdMap($local_id, $this->local_entity_name);
  }
}
