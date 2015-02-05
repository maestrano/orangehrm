<?php

// Include required libraries
define("ROOT_PATH", realpath(dirname(__FILE__) . '/../../'));

error_reporting(0);

require_once ROOT_PATH . '/vendor/autoload.php';
Maestrano::configure(ROOT_PATH . "/maestrano.json");

require_once 'sso/MnoSsoUser.php';
