<?php


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
require_once __DIR__ . '/../api/journalvoucher.php';


journalvoucherremove([ 'ref'=>'PI', 'refid'=>5091 ]);

?>