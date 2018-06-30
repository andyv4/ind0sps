<?php

$start_time = microtime(1);

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/notification.php';

notification_generate();

echo "Completed in " . (microtime(1) - $start_time) . PHP_EOL;

?>