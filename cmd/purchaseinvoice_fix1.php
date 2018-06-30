<?php

/**
 * This will fix purchase invoice inventory issue
 * Issue:
 * - inventory description filled with inventory code
 * - Null supplier id
 *
 */

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = isset($argv[1]) ? $argv[1] : 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';

/*
 * Phase 1 : inventory description = inventory code
 */
$query = "select `id`, inventorycode, inventorydescription from purchaseinvoiceinventory where inventorycode = inventorydescription";
$rows = pmrs($query);

echo "#1 Found " . count($rows) . " issues." . PHP_EOL;

if(is_array($rows)){
  foreach($rows as $row){
    $id = $row['id'];
    $inventorycode = $row['inventorycode'];
    $inventorydescription = pmc("select description from inventory where `code` = ?", [ $inventorycode ]);
    if($inventorydescription){
      pm("update purchaseinvoiceinventory set inventorydescription = ? where `id` = ?", [ $inventorydescription, $id ]);
      $row['inventorydescription'] = $inventorydescription;
    }
    echo json_encode($row) . PHP_EOL;
  }
}
echo "#1 Completed." . PHP_EOL;

/*
 * Phase 2 : supplier id null
 */
$query = "select `id`, supplierdescription from purchaseinvoice where supplierid is null";
$rows = pmrs($query);

echo "#2 Found " . count($rows) . " issues." . PHP_EOL;

if(is_array($rows)) {
  foreach($rows as $row){
    $id = $row['id'];
    $supplierid = pmc("select `id` from supplier where description = ?", [ $row['supplierdescription'] ]);
    $row['supplierid'] = $supplierid;
    pm("update purchaseinvoice set supplierid = ? where `id` = ?", [ $supplierid, $id ]);
    echo json_encode($row) . PHP_EOL;
  }
}

echo "#2 Completed." . PHP_EOL;

echo "Completed." . PHP_EOL;

?>