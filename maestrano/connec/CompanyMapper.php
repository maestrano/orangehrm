<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec Company representation to/from OrangeHRM Organization
*/
class CompanyMapper extends BaseMapper {
  private $_organizationService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Company';
    $this->local_entity_name = 'Organization';
    $this->connec_resource_name = 'company';
    $this->connec_resource_endpoint = 'company';

    $this->_organizationService = new OrganizationService();
  }

  // Single resource, id does not matter
  public function getId($employee) {
    return 0;
  }

  // Does not match by id
  public function loadModelById($local_id) {
    return null;
  }

  // Return the default Organization
  protected function matchLocalModel($employee_hash) {
    return $this->_organizationService->getOrganizationGeneralInformation();
  }

  // Map the Connec resource attributes onto the OrangeHRM Organization
  protected function mapConnecResourceToModel($company_hash, $organization) {
    // Map hash attributes to Organization
    if(!is_null($company_hash['name'])) { $organization->name = $company_hash['name']; }
    if(!is_null($company_hash['employer_id'])) { $organization->registraionNumber = $company_hash['employer_id']; }
    if(!is_null($company_hash['tax_number'])) { $organization->taxId = $company_hash['tax_number']; }
    if(!is_null($company_hash['note'])) { $organization->note = $company_hash['note']; }

    // Address
    if(!is_null($company_hash['address']) && !is_null($company_hash['address']['billing'])) {
      if(!is_null($company_hash['address']['billing']['line1'])) { $organization->street1 = $company_hash['address']['billing']['line1']; }
      if(!is_null($company_hash['address']['billing']['line2'])) { $organization->street2 = $company_hash['address']['billing']['line2']; }
      if(!is_null($company_hash['address']['billing']['city'])) { $organization->city = $company_hash['address']['billing']['city']; }
      if(!is_null($company_hash['address']['billing']['postal_code'])) { $organization->zipCode = $company_hash['address']['billing']['postal_code']; }
      if(!is_null($company_hash['address']['billing']['country'])) { $organization->country = $company_hash['address']['billing']['country']; }
      if(!is_null($company_hash['address']['billing']['region'])) { $organization->province = $company_hash['address']['billing']['region']; }
    }

    // Phone
    if(!is_null($company_hash['phone'])) {
      if(!is_null($company_hash['phone']['landline'])) { $organization->phone = $company_hash['phone']['landline']; }
      if(!is_null($company_hash['phone']['fax'])) { $organization->fax = $company_hash['phone']['fax']; }
    }

    // Email
    if(!is_null($company_hash['email'])) {
      if(!is_null($company_hash['email']['address'])) { $organization->email = $company_hash['email']['address']; }
    }
  }

  // Map the OrangeHRM Organization to a Connec resource hash
  protected function mapModelToConnecResource($organization) {
    $company_hash = array();

    // Map Organization to Connec hash
    if(!is_null($organization->name)) { $company_hash['name'] = $organization->name; }
    if(!is_null($organization->registraionNumber)) { $company_hash['employer_id'] = $organization->registraionNumber; }
    if(!is_null($organization->taxId)) { $company_hash['tax_number'] = $organization->taxId; }
    if(!is_null($organization->note)) { $company_hash['note'] = $organization->note; }

    // Address
    if(!is_null($organization->street1)) { $company_hash['address']['billing']['line1'] = $organization->street1; }
    if(!is_null($organization->street2)) { $company_hash['address']['billing']['line2'] = $organization->street2; }
    if(!is_null($organization->city)) { $company_hash['address']['billing']['city'] = $organization->city; }
    if(!is_null($organization->zipCode)) { $company_hash['address']['billing']['postal_code'] = $organization->zipCode; }
    if(!is_null($organization->country)) { $company_hash['address']['billing']['country'] = $organization->country; }
    if(!is_null($organization->province)) { $company_hash['address']['billing']['region'] = $organization->province; }

    // Phone
    if(!is_null($organization->phone)) { $company_hash['phone']['landline'] = $organization->phone; }
    if(!is_null($organization->fax)) { $company_hash['phone']['fax'] = $organization->fax; }

    // Email
    if(!is_null($organization->email)) { $company_hash['email']['address'] = $organization->email; }

    return $company_hash;
  }

  // Persist the OrangeHRM Organization
  protected function persistLocalModel($organization) {
    $organization->save();
  }
}
