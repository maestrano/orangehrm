<?php
/**
 * This controller creates a SAML request and redirects to
 * Maestrano SAML Identity Provider
 *
 */

require_once '../../init.php';

$req = new Maestrano_Saml_Request($_GET);
header('Location: ' . $req->getRedirectUrl());
