<?php
// Get full host (protocal + server host)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
$full_host = $protocol . $_SERVER['HTTP_HOST'];

// Id of the application
$mno_settings->app_id = 'orangehrm.app.dev.maestrano.io';

// Name of your application
$mno_settings->app_name = 'OrangeHRM';

// Enable Maestrano SSO for this app
$mno_settings->sso_enabled = true;

// SSO initialization URL
$mno_settings->sso_init_url = $full_host . '/maestrano/auth/saml/index.php';

// SSO processing url
$mno_settings->sso_return_url = $full_host . '/maestrano/auth/saml/consume.php';

// SSO initialization URL
$mno_settings->soa_init_url = $full_host . '/maestrano/data/initialize.php';

// Enable Maestrano SSO for this app
$mno_settings->soa_enabled = true;
