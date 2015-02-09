<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec Organization representation to/from OrangeHRM Customer
*/
class CustomerMapper extends BaseMapper {
  private $_customerService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Organization';
    $this->local_entity_name = 'Customer';
    $this->connec_resource_name = 'organizations';
    $this->connec_resource_endpoint = 'organizations';

    $this->_customerService = new CustomerService();
  }

  // Return the Customer local id
  protected function getId($customer) {
    return $customer->customerId;
  }

  // Return a local Customer by id
  protected function loadModelById($local_id) {
    return $this->_customerService->getCustomerById($local_id);
  }

  // Map the Connec resource attributes onto the OrangeHRM Customer
  protected function mapConnecResourceToModel($customer_hash, $customer) {
    // Map hash attributes to Customer
    if(!is_null($customer_hash['name'])) { $customer->name = $customer_hash['name']; }
    if(!is_null($customer_hash['description'])) { $customer->description = $customer_hash['description']; }
  }

  // Map the OrangeHRM Customer to a Connec resource hash
  protected function mapModelToConnecResource($customer) {
    $customer_hash = array();

    // Map Customer to Connec hash
    if(!is_null($customer->name)) { $customer_hash['name'] = $customer->name; }
    if(!is_null($customer->description)) { $customer_hash['description'] = $customer->description; }
    $customer_hash['is_customer'] = true;

    return $customer_hash;
  }

  // Persist the OrangeHRM Customer
  protected function persistLocalModel($customer) {
    $customer->save();
  }
}
