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
      try {
        $this->saveConnecResource($resource_hash);
      } catch (Exception $e) {
        error_log("Error when processing entity=".$this->connec_entity_name.", id=".$resource_hash['id'].", message=" . $e->getMessage());
      }
    }
  }

  // Map a Connec Resource to an OrangeHRM Model
  public function saveConnecResource($resource_hash, $persist=true, $model=null) {
    error_log("save connec resource entity=$this->connec_entity_name, hash=" . json_encode($resource_hash));
    
    // Load existing Model or create a new instance
    if(is_null($model)) {
      $model = $this->findOrInitializeModel($resource_hash);
      if(is_null($model)) {
        error_log("model cannot be initialized and will not be saved");
        return null;
      }
    }

    // Update the model attributes
    $this->mapConnecResourceToModel($resource_hash, $model);

    // Save and map the Model id to the Connec resource id
    if($persist) {
      $this->persistLocalModel($model);
      $local_id = $this->getId($model);

      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($local_id, $this->local_entity_name);
      if(!$mno_id_map) {
        error_log("map connec resource entity=$this->connec_entity_name, id=" . $resource_hash['id'] . ", local_id=$local_id");
        MnoIdMap::addMnoIdMap($local_id, $this->local_entity_name, $resource_hash['id'], $this->connec_entity_name);
      }
    }

    return $model;
  }

  // Process a Model update event
  // $pushToConnec: option to notify Connec! of the model update
  // $delete:       option to soft delete the local entity mapping amd ignore further Connec! updates
  public function processLocalUpdate($model, $pushToConnec=true, $delete=false) {
    error_log("process local update entity=$this->connec_entity_name, local_id=" . $this->getId($model) . ", pushToConnec=$pushToConnec, delete=$delete");
    
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
    
    error_log("find or initialize entity=$this->connec_entity_name, mno_id=$mno_id, mno_id_map=" . json_encode($mno_id_map));

    if($mno_id_map) {
      // Ignore updates for deleted Models
      if($mno_id_map['deleted_flag'] == 1) {
        error_log("ignore update for locally deleted entity=$this->connec_entity_name, mno_id=$mno_id");
        return null;
      }
      
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
    // Find Connec resource id
    $local_id = $this->getId($model);
    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($local_id, $this->local_entity_name);

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
      $this->saveConnecResource($result[$this->connec_resource_name], true, $model);
    }
  }

  // Flag the local Model mapping as deleted to ignore further updates
  protected function flagAsDeleted($model) {
    $local_id = $this->getId($model);
    error_log("flag as deleted entity=$this->connec_entity_name, local_id=$local_id");
    MnoIdMap::deleteMnoIdMap($local_id, $this->local_entity_name);
  }
}
