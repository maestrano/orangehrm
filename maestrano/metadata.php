<?php
  require_once 'init.php';
  header('Content-Type: application/json');

  if (Maestrano::authenticate($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])) {
    echo Maestrano::toMetadata();
  } else {
    echo "Sorry! I'm not giving you my API metadata";
  }
  
?>