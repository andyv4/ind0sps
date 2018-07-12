<?php

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventoryadjustment.php';
require_once __DIR__ . '/../api/salesinvoice.php';


$log = pmr("select data1 from userlog where data1 like ? order by `timestamp` desc limit 1", [ "%SPS/18/05773%" ]);
$log = unserialize($log['data1']);
$result = salesinvoicemodify($log);
echo json_encode($result);

//inventoryadjustmentremove([ 'id'=>2092 ]);
echo 'Completed' . PHP_EOL;

?>