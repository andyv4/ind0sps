<?php

$t1 = microtime(1);

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventory.php';

$id = isset($argv[1]) ? $argv[1] : null;
$date = isset($argv[2]) ? $argv[2] : null;

inventorycostprice($id, $date, function($message){

  echo $message . PHP_EOL;

});

echo "Completed in " . (microtime(1) - $t1) . "s" . PHP_EOL;

?>