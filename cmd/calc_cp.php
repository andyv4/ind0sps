<?php

$t1 = microtime(1);

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventory.php';

$inventoryids = [];
$rows = pmrs("select distinct(inventoryid) as inventoryid from inventorybalance order by inventoryid");
foreach($rows as $row)
  $inventoryids[] = $row['inventoryid'];

//inventorycostprice_fifo_id(2155);
foreach($inventoryids as $inventoryid){
  echo "Calculating {$inventoryid} ...\n";
  inventorycostprice_fifo_id($inventoryid);
}

echo "Completed in " . (microtime(1) - $t1) . "s\n";

?>