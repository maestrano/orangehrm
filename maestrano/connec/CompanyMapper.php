<?php

require_once 'MnoIdMap.php';

/**
* Map Connec Company representation to/from OrangeHRM Organization
*/
class CompanyMapper {
  private $_connec_client;
  private $_organizationService;

  public function __construct() {
    $this->_organizationService = new OrganizationService();
    $this->_connec_client = new Maestrano_Connec_Client('orangehrm.app.dev.maestrano.io');
  }

  // Persist a list of Connec Company hashes as OrangeHRM Organizations
  // Only one company should be returned
  public function persistAll($companies_hash) {
    foreach($companies_hash as $company_hash) {
      $this->hashToCompany($company_hash);
    }
  }

  // Map a Connec Company hash to an OrangeHRM Organization
  public function hashToCompany($company_hash, $persist=true) {
    // Retrieve Organization resource
    $organization = $this->_organizationService->getOrganizationGeneralInformation();
    if(is_null($organization)) {
      $this->organization = new Organization();
    }

    // Map hash attributes to Organization
    if(!is_null($company_hash['name'])) { $organization->name = $company_hash['name']; }
    if(!is_null($company_hash['tax_number'])) { $organization->registraionNumber = $company_hash['tax_number']; }
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

    // Save and map the Organization
    if($persist) {
      $organization->save();
    }

    return $organization;
  }

  // Process an Company update event
  // $pushToConnec: option to notify Connec! of the model update
  // $delete:       option to soft delete the local entity mapping amd ignore further Connec! updates
  public function processLocalUpdate($organization, $pushToConnec=true, $delete=false) {
    if($pushToConnec) {
      $this->pushToConnec($organization);
    }

    if($delete) {
      $this->flagAsDeleted($organization);
    }
  }

  public function pushToConnec($organization) {
    $company_hash = array();

    // Map Organization to Connec hash
    if(!is_null($organization->name)) { $company_hash['name'] = $organization->name; }
    if(!is_null($organization->registraionNumber)) { $company_hash['tax_number'] = $organization->registraionNumber; }
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

    // Push to Connec!
    $hash = array('company'=>$company_hash);
    error_log("Built hash: " . json_encode($hash));
    $response = $this->_connec_client->post("companies", $hash);

    // Process response
    $code = $response['code'];
    $body = $response['body'];
    if($code >= 300) {
      error_log("Cannot push to Connec! entity_name=Company, code=$code, body=$body");
    } else {
      error_log("Processing Connec! response code=$code, body=$body");
      $result = json_decode($response['body'], true);
      error_log("processing entity_name=Company entity=". json_encode($result));
      $this->hashToCompany($result['company']);
    }
  }

  public function flagAsDeleted($organization) {
  }
}
