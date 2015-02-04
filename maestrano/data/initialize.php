<?php

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../'));
}

require_once(MAESTRANO_ROOT . '/app/init/soa.php');

$maestrano = MaestranoService::getInstance();

if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
    $filepath = MAESTRANO_ROOT . '/var/_data_sequence';
    $status = false;
    
    if (file_exists($filepath)) {
        $timestamp = trim(file_get_contents($filepath));
        $current_timestamp = round(microtime(true) * 1000);
        
        if (empty($timestamp)) { $timestamp = 0; } 

        $mno_entity = new MnoSoaEntity($opts['db_connection']);
        $status = $mno_entity->getUpdates($timestamp);
    }
    
    if ($status) {
        file_put_contents($filepath, $current_timestamp);
    }
}

?>
