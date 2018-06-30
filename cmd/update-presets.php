<?php

// Prerequisites
$start_time = microtime(1);

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/chartofaccount.php';
require_once __DIR__ . '/../api/inventory.php';
require_once __DIR__ . '/../api/customer.php';
require_once __DIR__ . '/../api/supplier.php';
date_default_timezone_set('Asia/Jakarta');
register_shutdown_function("onshutdown");

echo 'BEGIN...' . PHP_EOL;

system_presets_update();

echo "COMPLETED in " . (microtime(1) - $start_time) . "s" . PHP_EOL . PHP_EOL;

function onshutdown(){

  $content = ob_get_contents();

  $err = error_get_last();
  if($err){
    $message = $err["message"];
    $file = $err["file"];
    $line = $err["line"];


  }
  else{
    echo $content;
  }

}

?>