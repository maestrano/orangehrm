<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  /**
   * User form
   * @var AddEmployeeForm
   */
  public $_user = null;
  
  /**
   * SysUser form
   * @var SystemUserForm
   */
  public $_sysuser = null;
  
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    if ($this->local_id && $this->uid) {
        $service = new AuthenticationService();
        $service->setAuthenticationDao(new AuthenticationDao());
        $success = $service->setCredentialsWithoutPassword($this->uid, array());
        return $success;
    } else {
        return false;
    }
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->local_id) {
      return $this->local_id;
    }
    
    if ($this->accessScope() == 'private') {
      // Create employee
      $this->buildLocalUser();
      $service = new EmployeeService();
      $service->saveEmployee($this->_user);
      $lid = $this->_user->empNumber;
      
      // Create system user
      $service = new SystemUserService();
      $this->buildLocalSysUser($lid);
      $service->saveSystemUser($this->_sysuser, true);
    }
    
    return $lid;
  }
  
  /**
   * Build an employee object ready to
   * be saved
   *
   * @return AddEmployeeForm object
   */
  protected function buildLocalUser()
  {
    $employee = new Employee();
    $employee->setFirstName($this->name);
    $employee->setLastName($this->surname);
    $employee->setEmpWorkEmail($this->email);
    
    $this->_user = $employee;
    
    return $this->_user;
  }
  
  
  /**
   * Build a system user ready to
   * be saved
   *
   * @return SystemUser object
   */
  protected function buildLocalSysUser($local_id = null)
  {
    if($this->local_id) {
      $local_id = $this->local_id;
    }
    
    $user = new SystemUser();
    $user->setDateEntered(date('Y-m-d H:i:s'));
    $user->setUserPassword($this->generatePassword());
    $user->setUserRoleId(2);
    $user->setEmpNumber($local_id);
    $user->setUserName($this->uid);
    $user->setStatus(1);
    
    $this->_sysuser = $user;
    
    return $this->_sysuser;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
       
       // Update Employee details
       $q = Doctrine_Query :: create()->update('Employee')
                       ->set('emp_work_email', '?', $this->email)
                       ->set('emp_firstname', '?', $this->name)
                       ->set('emp_lastname', '?', $this->surname)
                       ->where('empNumber = ?', $this->local_id);
       $upd = $q->execute();
       
       // Chech if a system user exists for this Employee
       $q = Doctrine_Manager::getInstance()->getCurrentConnection();
       $result = $q->execute("SELECT id from ohrm_user WHERE emp_number = '$this->local_id'")->fetch();
       
       // If employee has an associated user then finish local sync
       // Otherwise create a new ohrm user
       if ($result && $result['id']) {
         return true;
       } else {
         $service = new SystemUserService();
         $this->buildLocalSysUser();
         $service->saveSystemUser($this->_sysuser, true);
       }
       
     }
     
     return false;
   }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
    $q = Doctrine_Manager::getInstance()->getCurrentConnection();
    $result = $q->execute("SELECT emp_number from hs_hr_employee WHERE mno_uid = '$this->uid'")->fetch();
    
    if ($result && $result['emp_number']) {
      return $result['emp_number'];
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {

    $q = Doctrine_Manager::getInstance()->getCurrentConnection();
    $result = $q->execute("SELECT emp_number from hs_hr_employee WHERE emp_work_email = '$this->email'")->fetch();
    
    if ($result && $result['emp_number']) {
      return $result['emp_number'];
    }
    
    return null;
  }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
      $q = Doctrine_Query :: create()->update('Employee')
                      ->set('mno_uid', '?', $this->uid)
                      ->where('empNumber = ?', $this->local_id);
      $upd = $q->execute();
      return $upd;
    }
    
    return false;
  }
}