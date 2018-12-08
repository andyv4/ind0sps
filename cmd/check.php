<?php

$t1 = microtime(1);

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/purchaseorder.php';

$fix = isset($argv[1]) && $argv[1] == 'fix' ? true : false;

echo "Fix:" . ($fix ? ' true' : ' false') . "\n";

purchaseorder_check_journal($fix, function($message){

  echo $message . "\n";

});


echo "Completed in " . (microtime(1) - $t1) . "\n";


?>