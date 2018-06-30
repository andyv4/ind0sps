<?php

// Prerequisites
$start_time = microtime(1);

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/chartofaccount.php';
require_once __DIR__ . '/../api/inventory.php';
date_default_timezone_set('Asia/Jakarta');


echo inventorycostprice_get(410);
echo PHP_EOL;

echo "COMPLETED in " . (microtime(1) - $start_time) . "s" . PHP_EOL . PHP_EOL;
?>