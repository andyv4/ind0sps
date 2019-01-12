<?php

/**
 * This will fix purchase invoice inventory issue
 * Issue:
 * - inventory description filled with inventory code
 * - Null supplier id
 * - Double payment amount
 */

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/purchaseinvoice.php';

$fix = isset($argv[1]) && $argv[1] == 1 ? true : false;

/*
 * Phase 1 : inventory description = inventory code
 */
$query = "select t2.`id`, t1.date, t1.code, t2.inventorycode, t2.inventorydescription from purchaseinvoice t1, purchaseinvoiceinventory t2 
  where t1.id = t2.purchaseinvoiceid and t2.inventorycode = t2.inventorydescription";
$rows = pmrs($query);

echo "#1 Found " . count($rows) . " issues." . PHP_EOL;

if($fix){
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
}


/*
 * Phase 2 : supplier id null
 */
$query = "select `id`, code, date, supplierdescription from purchaseinvoice where supplierid is null";
$rows = pmrs($query);

echo "#2 Found " . count($rows) . " issues." . PHP_EOL;

if($fix) {
  if (is_array($rows)) {
    foreach ($rows as $row) {
      $id = $row['id'];
      $supplierid = pmc("select `id` from supplier where description = ?", [$row['supplierdescription']]);
      $row['supplierid'] = $supplierid;
      pm("update purchaseinvoice set supplierid = ? where `id` = ?", [$supplierid, $id]);
      echo json_encode($row) . PHP_EOL;
    }
  }
  echo "#2 Completed." . PHP_EOL;
}

/*
 * Phase 3: Double payment amount
 *
 */
$query = "select t1.id, t1.code as pi_code, t2.code as po_code, t1.paymentamount as pi_paymentamount, t2.paymentamount as po_paymentamount,
          t1.paymentaccountid as pi_paymentaccountid, t2.paymentaccountid as po_paymentaccountid
          from purchaseinvoice t1, purchaseorder t2 where t1.purchaseorderid = t2.id
          and t1.paymentamount > 0 and t2.paymentamount > 0";
$rows = pmrs($query);

echo "#3 Found " . count($rows) . " issues." . PHP_EOL;

if($fix){
  if (is_array($rows)) {
    foreach ($rows as $row) {
      $id = $row['id'];
      pm("update purchaseinvoice set paymentamount = 0 where `id` = ?", [ $id ]);
      echo json_encode($row) . PHP_EOL;
    }
  }
  echo "#3 Completed." . PHP_EOL;
}


/*
 * Phase 4: No journal for unpaid invoice
 *
 */
$query = "select t1.id from purchaseinvoice t1 where t1.id not in (select refid from journalvoucher where ref = 'PI')";
$rows = pmrs($query);

echo "#4 Found " . count($rows) . " issues." . PHP_EOL;

if($fix) {
  if (is_array($rows)) {
    foreach ($rows as $row) {
      purchaseinvoicecalculate($row['id']);
    }
    echo "#4 Completed." . PHP_EOL;
  }
}

echo "Completed." . PHP_EOL;

?>