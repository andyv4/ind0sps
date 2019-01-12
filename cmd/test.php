<?php

/*$pdo_con = new PDO('mysql:host=127.0.0.1;dbname=indosps', 'root', 'webapp', array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false
));*/

require_once __DIR__ . '/../rcfx/php/pdo.php';

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';


require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/chartofaccount.php';
require_once __DIR__ . '/../api/code.php';
require_once __DIR__ . '/../api/inventory.php';
require_once __DIR__ . '/../api/customer.php';
require_once __DIR__ . '/../api/supplier.php';
require_once __DIR__ . '/../api/warehouse.php';
date_default_timezone_set('Asia/Jakarta');
ini_set('memory_limit', '256M');

$t1 = microtime(1);
//chartofaccountrecalculate(5);
customer_suspended_calc();
echo "customercalculateall " . (microtime(1) - $t1) . PHP_EOL;

?>