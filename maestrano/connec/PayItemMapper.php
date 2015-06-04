<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec PayItem representation to/from OrangeHRM LeaveType
*/
class PayItemMapper extends BaseMapper {
  private $_leave_type_service;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'PayItem';
    $this->local_entity_name = 'LeaveType';
    $this->connec_resource_name = 'pay_items';
    $this->connec_resource_endpoint = 'pay_items';

    $this->_leave_type_service = new LeaveTypeService();
  }

  // Return the LeaveType local id
  protected function getId($leave_type) {
    return $leave_type->id;
  }

  // Return a local LeaveType by id
  protected function loadModelById($local_id) {
    return $this->_leave_type_service->readLeaveType($local_id);
  }

  // Match an LeaveType by name
  protected function matchLocalModel($pay_item_hash) {
    if($pay_item_hash['name'] != null) {
      return $this->_leave_type_service->readLeaveTypeByName($pay_item_hash['name']);
    }
    return null;
  }

  // Map only LEAVE PayItems
  protected function validate($resource_hash) {
    return $resource_hash['type'] == 'LEAVE';
  }

  // Map the Connec resource attributes onto the OrangeHRM PayItem
  protected function mapConnecResourceToModel($pay_item_hash, $leave_type) {
    // Map hash attributes to PayItem
    if(!is_null($pay_item_hash['name'])) { $leave_type->name = $pay_item_hash['name']; }
    else if(!is_null($pay_item_hash['sub_type'])) { $leave_type->name = $pay_item_hash['sub_type']; }
    if(!is_null($pay_item_hash['show_pay_slip'])) { $leave_type->exclude_in_reports_if_no_entitlement = !$pay_item_hash['show_pay_slip']; }
  }

  // Map the OrangeHRM PayItem to a Connec resource hash
  protected function mapModelToConnecResource($leave_type) {
    $pay_item_hash = array();
    $pay_item_hash['type'] = 'LEAVE';

    // Map PayItem to Connec hash
    if(!is_null($leave_type->name)) { $pay_item_hash['name'] = $leave_type->name; }
    if(!is_null($leave_type->exclude_in_reports_if_no_entitlement)) { $pay_item_hash['show_pay_slip'] = !$leave_type->exclude_in_reports_if_no_entitlement; }

    return $pay_item_hash;
  }

  // Persist the OrangeHRM PayItem
  protected function persistLocalModel($leave_type, $resource_hash) {
    $this->_leave_type_service->saveLeaveType($leave_type, false);
  }
}
