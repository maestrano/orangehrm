<?php

define('TEST_ROOT', __DIR__);

// Dependency: php-saml
define('PHP_SAML_DIR', './../../lib/php-saml/src/OneLogin/Saml/');
require PHP_SAML_DIR . 'AuthRequest.php';
require PHP_SAML_DIR . 'Response.php';
require PHP_SAML_DIR . 'Settings.php';
require PHP_SAML_DIR . 'XmlSec.php';

// Dependency: mno-php/sso
define('MNO_PHP_SSO_DIR', './../../lib/mno-php/src/sso/');
require MNO_PHP_SSO_DIR . 'MnoSsoBaseUser.php';

// Define Autoload manager
define('AUTOLOAD_MGT_DIR', './../..//lib/autoload-manager/');
require AUTOLOAD_MGT_DIR . 'autoloadManager.php';

$autoloadManager = new AutoloadManager();
$autoloadManager->setSaveFile('./_autoload_hash_map.php');

// Dependencies: your app files
define('MY_APP_DIR', './../../../');
$autoloadManager->addFolder(MY_APP_DIR . 'symfony/');
$autoloadManager->register();
require MY_APP_DIR . 'symfony/lib/vendor/symfony/lib/helper/I18NHelper.php';
// require MY_APP_DIR . 'symfony/lib/vendor/symfony/lib/widget/sfWidget.class.php';
// require MY_APP_DIR . 'symfony/lib/vendor/symfony/lib/widget/sfWidgetForm.class.php';
// require MY_APP_DIR . 'symfony/lib/vendor/symfony/lib/widget/sfWidgetFormSchema.class.php';
// require MY_APP_DIR . 'symfony/lib/vendor/symfony/lib/validator/sfValidatorBase.class.php';
// require MY_APP_DIR . 'symfony/lib/vendor/symfony/lib/validator/sfValidatorSchema.class.php';
// require MY_APP_DIR . 'symfony/lib/vendor/symfony/lib/form/sfForm.class.php';
// require MY_APP_DIR . 'symfony/plugins/orangehrmPimPlugin/lib/form/AddEmployeeForm.php';


// Tested class: 
define('TEST_INT_SSO_DIR', './../sso/');
require TEST_INT_SSO_DIR . 'MnoSsoUser.php';

// Set timezone
date_default_timezone_set('UTC');