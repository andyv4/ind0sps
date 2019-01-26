<?php

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventory.php';
require_once __DIR__ . '/../api/job.php';

$inventoryids = [];

inventory_check(function($index, $total, $row) use(&$inventoryids){

  $ok = $row['qty'] == $row['qty_calculated'] ? 1 : 0;

  if(!$ok){
    echo str_pad("#{$index}", 6, ' ', STR_PAD_RIGHT) . "\t" .
      str_pad($row['id'], 10, ' ', STR_PAD_RIGHT) . "\t" .
      str_pad($row['code'], 20, ' ', STR_PAD_RIGHT) . "\t" .
      str_pad($row['qty'], 10, ' ', STR_PAD_LEFT) . "\t" .
      str_pad($row['qty_calculated'], 10, ' ', STR_PAD_LEFT) . "\t" .
      cli_add_color("NOT MATCH", 'red') . "\n";

    $inventoryids[] = $row['id'];
  }

});

if(count($inventoryids) > 0){

  $warehouseids = [];
  $rows = pmrs("select `id` from warehouse");
  foreach($rows as $row)
    $warehouseids[$row['id']] = 1;
  $warehouseids = array_keys($warehouseids);

  $related_inventories = [];
  foreach($inventoryids as $inventoryid)
    $related_inventories[$inventoryid] = $warehouseids;
  job_create_and_run('inventory_calc_qty', [ $related_inventories ]);

  echo count($inventoryids) . " repaired \n";

}

?>