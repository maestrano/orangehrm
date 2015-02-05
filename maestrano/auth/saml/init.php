<?php
/**
 * This controller creates a SAML request and redirects to
 * Maestrano SAML Identity Provider
 *
 */

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
define("ROOT_PATH", realpath(dirname(__FILE__) . '/../../../'));

error_reporting(0);

require ROOT_PATH . '/vendor/autoload.php';
Maestrano::configure(ROOT_PATH . "/maestrano.json");

$req = new Maestrano_Saml_Request($_GET);
header('Location: ' . $req->getRedirectUrl());
