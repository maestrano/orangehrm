<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec WorkLocation representation to/from OrangeHRM WorkLocation
*/
class WorkLocationMapper extends BaseMapper {
  private $_workLocationService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'WorkLocation';
    $this->local_entity_name = 'Location';
    $this->connec_resource_name = 'work_locations';
    $this->connec_resource_endpoint = 'work_locations';

    $this->_workLocationService = new LocationService();
  }

  // Return the WorkLocation local id
  protected function getId($workLocation) {
    return $workLocation->id;
  }

  // Return a local WorkLocation by id
  protected function loadModelById($local_id) {
    return $this->_workLocationService->getLocationById($local_id);
  }

  // Map the Connec resource attributes onto the OrangeHRM WorkLocation
  protected function mapConnecResourceToModel($work_location_hash, $workLocation) {
    // Map hash attributes to WorkLocation

    if(!is_null($work_location_hash['description'])) { $workLocation->notes = $work_location_hash['description']; }

    // Map address shipping otherwise billing
    $address = null;
    if(array_key_exists('address', $work_location_hash)) {
      if(array_key_exists('shipping', $work_location_hash['address'])) {
        $address = $work_location_hash['address']['shipping'];
      } else if(array_key_exists('billing', $work_location_hash['address'])) {
        $address = $work_location_hash['address']['billing'];
      }
    }
    if(!is_null($address)) {
      if(!is_null($address['line1'])) { $workLocation->address = $address['line1']; }
      if(!is_null($address['city'])) { $workLocation->city = $address['city']; }
      if(!is_null($address['postal_code'])) { $workLocation->zipCode = $address['postal_code']; }
      if(!is_null($address['region'])) { $workLocation->province = $address['region']; }
      if(!is_null($address['country'])) {
        $workLocation->countryCode = $address['country'];
      } else {
        $workLocation->countryCode = 'US';
      }
    }

    // Phone
    if(!is_null($work_location_hash['phone'])) {
      if(!is_null($work_location_hash['phone']['landline'])) { $workLocation->phone = $work_location_hash['phone']['landline']; }
      if(!is_null($work_location_hash['phone']['fax'])) { $workLocation->fax = $work_location_hash['phone']['fax']; }
    }

    // Location name, default to city if not set
    if(!is_null($work_location_hash['name'])) {
      $workLocation->name = $work_location_hash['name']; 
    } else if(!is_null($work_location_hash['address'])) {
      $workLocation->name = $workLocation->city . " - " . $workLocation->countryCode;
    }
  }

  // Map the OrangeHRM WorkLocation to a Connec resource hash
  protected function mapModelToConnecResource($workLocation) {
    $work_location_hash = array();

    // Map WorkLocation to Connec hash
    if(!is_null($workLocation->name)) { $work_location_hash['name'] = $workLocation->name; }
    if(!is_null($workLocation->notes)) { $work_location_hash['description'] = $workLocation->notes; }

    // Address
    $work_location_hash['address'] = array('street' => array());
    if(!is_null($workLocation->address)) { $work_location_hash['address']['street']['line1'] = $workLocation->address; }
    if(!is_null($workLocation->city)) { $work_location_hash['address']['street']['city'] = $workLocation->city; }
    if(!is_null($workLocation->zipCode)) { $work_location_hash['address']['street']['postal_code'] = $workLocation->zipCode; }
    if(!is_null($workLocation->countryCode)) { $work_location_hash['address']['street']['country'] = $workLocation->countryCode; }
    if(!is_null($workLocation->province)) { $work_location_hash['address']['street']['region'] = $workLocation->province; }

    // Phone
    if(!is_null($workLocation->phone)) { $work_location_hash['phone']['landline'] = $workLocation->phone; }
    if(!is_null($workLocation->fax)) { $work_location_hash['phone']['fax'] = $workLocation->fax; }

    return $work_location_hash;
  }

  // Persist the OrangeHRM WorkLocation
  protected function persistLocalModel($workLocation, $resource_hash) {
    $workLocation->save();
  }
}
