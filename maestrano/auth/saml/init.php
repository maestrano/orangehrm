<?php
/**
 * This controller creates a SAML request and redirects to
 * Maestrano SAML Identity Provider
 *
 */

require_once '../../init.php';

$_SESSION['mno_previous_uri'] = $_SERVER['HTTP_REFERER'];
error_log("INIT REFERER URL: " . $_SESSION['mno_previous_uri']);

$req = new Maestrano_Saml_Request($_GET);
header('Location: ' . $req->getRedirectUrl());
