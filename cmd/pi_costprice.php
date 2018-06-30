<?php

$t1 = microtime(1);

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = isset($argv[1]) ? $argv[1] : 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/purchaseinvoice.php';

inventorybalance_calc_refitemid();
//$updated = purchaseinvoice_calc_zero_costprice(3343);



echo "Completed in " . (microtime(1) - $t1) . PHP_EOL;

?>