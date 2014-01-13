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
  // protected function setInSession()
  // {
  //   // First set $conn variable (need global variable?)
  //   $conn = $this->connection;
  //   
  //   $sel1 = $conn->query("SELECT ID,name,lastlogin FROM user WHERE ID = $this->local_id");
  //   $chk = $sel1->fetch();
  //   if ($chk["ID"] != "") {
  //       $now = time();
  //       
  //       // Set session
  //       $this->session['userid'] = $chk['ID'];
  //       $this->session['username'] = stripslashes($chk['name']);
  //       $this->session['lastlogin'] = $now;
  //       
  //       // Update last login timestamp
  //       $upd1 = $conn->query("UPDATE user SET lastlogin = '$now' WHERE ID = $this->local_id");
  //       
  //       return true;
  //   } else {
  //       return false;
  //   }
  // }
  
  
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
      $fields = $this->buildLocalUser();
      $this->_user->bind($fields, array());
      $lid = $this->_user->save();
    }
    
    return $lid;
  }
  
  /**
   * Build an employee array ready to be
   * used for OHRM user creation
   *
   * @return AddEmployeeForm object
   */
  protected function buildLocalUser()
  {
    $fields = array();
    $fields["firstName"] = $this->name;
    $fields["middleName"] = "";
    $fields["lastName"] = $this->surname;
    $fields["employeeId"] = "";
    $fields["user_name"] = $this->email;
    $fields["user_password"] = $this->generatePassword();
    $fields["re_password"] = $fields["user_password"];
    $fields["status"] = "Enabled";
    $fields["empNumber"] = "0";
    
    $this->_user = new AddEmployeeForm(array(), $fields, false);
    $this->_user->createUserAccount = 1;
    
    return $fields;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     // if($this->local_id) {
     //   $upd = $this->connection->query("UPDATE user SET name = {$this->connection->quote($this->name . ' ' . $this->surname)}, email = {$this->connection->quote($this->email)} WHERE ID = $this->local_id");
     //   return $upd;
     // }
     
     return false;
   }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
    $result = null;
    echo 'in getLocalIdByUid <br/><br/>';
    $q = Doctrine_Manager::getInstance()->getCurrentConnection();
    echo 'after currentconnection';
    try {
      $result = $q->execute("SELECT emp_number from hs_hr_employee WHERE mno_uid = '$this->uid'")->fetch();
    } catch (Exception $e) {
      echo $e;
    }
    
    //$result = Doctrine :: getTable('Employee')->findOneBy(array('mnoUid' => $this->uid));
    echo 'After query';
    echo("Result is " . var_dump($result));
    
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
    $result = null;
    echo 'in getLocalIdByUid <br/><br/>';
    $q = Doctrine_Manager::getInstance()->getCurrentConnection();
    echo 'after currentconnection';
    try {
      $result = $q->execute("SELECT emp_number from hs_hr_employee WHERE emp_work_email = '$this->email'")->fetch();
    } catch (Exception $e) {
      echo $e;
    }
    //$result = Doctrine :: getTable('Employee')->findOneBy(array('emp_work_email' => $this->email));
    
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