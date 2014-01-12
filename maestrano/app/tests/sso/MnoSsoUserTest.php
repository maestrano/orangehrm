<?php

// Class helper for database connection
class PDOMock extends PDO {
    public function __construct() {}
    
    // Make it final to avoid stubbing
    public final function quote($arg)
    {
      return "'$arg'";
    }
}

// Class Test
class MnoSsoUserTest extends PHPUnit_Framework_TestCase
{
    private $_saml_settings;
    
    public function setUp()
    {
      parent::setUp();
      
      // Create SESSION
      $_SESSION = array();
      
      $settings = new OneLogin_Saml_Settings;
      $settings->idpSingleSignOnUrl = 'http://localhost:3000/api/v1/auth/saml';

      // The certificate for the users account in the IdP
      $settings->idpPublicCertificate = <<<CERTIFICATE
-----BEGIN CERTIFICATE-----
MIIDezCCAuSgAwIBAgIJAOehBr+YIrhjMA0GCSqGSIb3DQEBBQUAMIGGMQswCQYD
VQQGEwJBVTEMMAoGA1UECBMDTlNXMQ8wDQYDVQQHEwZTeWRuZXkxGjAYBgNVBAoT
EU1hZXN0cmFubyBQdHkgTHRkMRYwFAYDVQQDEw1tYWVzdHJhbm8uY29tMSQwIgYJ
KoZIhvcNAQkBFhVzdXBwb3J0QG1hZXN0cmFuby5jb20wHhcNMTQwMTA0MDUyMjM5
WhcNMzMxMjMwMDUyMjM5WjCBhjELMAkGA1UEBhMCQVUxDDAKBgNVBAgTA05TVzEP
MA0GA1UEBxMGU3lkbmV5MRowGAYDVQQKExFNYWVzdHJhbm8gUHR5IEx0ZDEWMBQG
A1UEAxMNbWFlc3RyYW5vLmNvbTEkMCIGCSqGSIb3DQEJARYVc3VwcG9ydEBtYWVz
dHJhbm8uY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVkIqo5t5Paflu
P2zbSbzxn29n6HxKnTcsubycLBEs0jkTkdG7seF1LPqnXl8jFM9NGPiBFkiaR15I
5w482IW6mC7s8T2CbZEL3qqQEAzztEPnxQg0twswyIZWNyuHYzf9fw0AnohBhGu2
28EZWaezzT2F333FOVGSsTn1+u6tFwIDAQABo4HuMIHrMB0GA1UdDgQWBBSvrNxo
eHDm9nhKnkdpe0lZjYD1GzCBuwYDVR0jBIGzMIGwgBSvrNxoeHDm9nhKnkdpe0lZ
jYD1G6GBjKSBiTCBhjELMAkGA1UEBhMCQVUxDDAKBgNVBAgTA05TVzEPMA0GA1UE
BxMGU3lkbmV5MRowGAYDVQQKExFNYWVzdHJhbm8gUHR5IEx0ZDEWMBQGA1UEAxMN
bWFlc3RyYW5vLmNvbTEkMCIGCSqGSIb3DQEJARYVc3VwcG9ydEBtYWVzdHJhbm8u
Y29tggkA56EGv5giuGMwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOBgQCc
MPgV0CpumKRMulOeZwdpnyLQI/NTr3VVHhDDxxCzcB0zlZ2xyDACGnIG2cQJJxfc
2GcsFnb0BMw48K6TEhAaV92Q7bt1/TYRvprvhxUNMX2N8PHaYELFG2nWfQ4vqxES
Rkjkjqy+H7vir/MOF3rlFjiv5twAbDKYHXDT7v1YCg==
-----END CERTIFICATE-----
CERTIFICATE;

      // The URL where to the SAML Response/SAML Assertion will be posted
      $settings->spReturnUrl = 'http://localhost:8888/maestrano/auth/saml/consume.php';

      // Name of this application
      $settings->spIssuer = 'bla.app.dev.maestrano.io';

      // Tells the IdP to return the email address of the current user
      $settings->requestedNameIdFormat = 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent';
      
      $this->_saml_settings = $settings;
    }
    
    // Used to test protected methods
    protected static function getMethod($name) {
      $class = new ReflectionClass('MnoSsoUser');
      $method = $class->getMethod($name);
      $method->setAccessible(true);
      return $method;
    }
    
    public function testFunctionGetLocalIdByUid()
    {
    }
    
    public function testFunctionGetLocalIdByEmail()
    {
    }
    
    public function testFunctionSetLocalUid()
    {
    }
    
    public function testFunctionSyncLocalDetails()
    {
    }
    
    public function testFunctionCreateLocalUser()
    {
    }
    
    public function testFunctionBuildLocalEmployee()
    {
      // Specify which protected method get tested
      $protected_method = self::getMethod('buildLocalEmployee');
      
      // Build User
      $assertion = file_get_contents(TEST_ROOT . '/support/sso-responses/response_ext_user.xml.base64');
      $sso_user = new MnoSsoUser(new OneLogin_Saml_Response($this->_saml_settings, $assertion));
      $sso_user->local_id = null;
      $sso_user->app_owner = true; 
      
      // Run method
      $f = $protected_method->invokeArgs($sso_user,array());
      
      // Test that user fields have been populated correctly
      $this->assertEquals($sso_user->name, $f["firstName"]);
      $this->assertEquals("", $f["middleName"]);
      $this->assertEquals($sso_user->surname, $f["lastName"]);
      $this->assertEquals("", $f["employeeId"]);
      $this->assertEquals($sso_user->email, $f["user_name"]);
      $this->assertEquals(20, strlen($f["user_password"]));
      $this->assertEquals($f["user_password"], $f["re_password"]);
      $this->assertEquals("Enabled", $f["status"]);
      $this->assertEquals("", $f["empNumber"]);
      $this->assertEquals("", $f["empNumber"]);
      $this->assertEquals($sso_user->email, $f["emp_work_email"]);
    }
    
    public function testFunctionSignIn()
    {
    }
}