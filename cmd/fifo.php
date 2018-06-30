<?php
$start_time = microtime(1);

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
function onexception($ex){
  echo $ex->getMessage();
}
register_shutdown_function("onshutdown");
set_exception_handler('onexception');

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';
$applog_dest = 'echo';
//$applog_keys = [ 'log' ];
$timezone = 'Asia/Jakarta';

date_default_timezone_set($timezone);

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventory.php';

inventorycostprice($argv[1], $argv[2]);
inventoryqty_calculate([ $argv[1] ]);
//inventoryqty_calculateall();
warehousecalculateall();

echo "Completed in " . (microtime(1) - $start_time) . "s" . PHP_EOL;
?>