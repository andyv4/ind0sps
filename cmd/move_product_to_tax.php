<?php

$src_file = 'tax_inventory.csv';
$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';
$projectid = 'T1-';
$date = date('Y-m-d');

$start_time = microtime(1);
require_once '../rcfx/php/pdo.php';
require_once '../rcfx/php/util.php';
require_once '../api/inventory.php';
require_once '../api/inventoryadjustment.php';

// 0. REMOVE INVENTORY ADJUSTMENT
console_write("Removing previous adjustment...");
$inventory_adjustments = pmrs("select `id` from inventoryadjustment where code like ?", [ $projectid . "%" ]);
foreach($inventory_adjustments as $inventory_adjustment)
  inventoryadjustmentremove([ 'id'=>$inventory_adjustment['id'] ]);
console_write("Previous adjustment removed.");

console_write('--------------------------------------------');

console_write("Calculating inventories...");
inventory_warehouse_calc();
inventoryqty_calculateall();
console_write("Inventory calculated.");

// 1. Open inventories to process
$inventories = explode(PHP_EOL, file_get_contents($src_file));
if(!is_array($inventories) || count($inventories) == 0) console_error('No inventory to move.' . PHP_EOL);
console_write("Total inventories to process: " . count($inventories));

// Set inventory code as active
foreach($inventories as $inventory_code){
  $inventoryid = pmc("select `id` from inventory where code = ?", [ $inventory_code ]);
  if($inventoryid) inventorymodify([ 'id'=>$inventoryid, 'isactive'=>1 ]);
}

// 2. Process
$invalid_inventories = [];
$initial_inventories = [];
foreach($inventories as $inventory_code){

  console_write('--------------------------------------------');

  $inventory = inventorydetail([ 'warehouses', 'costprices' ], [ 'code'=>$inventory_code ]);
  if(!$inventory){ console_write("Inventory not exists, code: " . $inventory_code); continue; }
  if(!$inventory['isactive']){ console_write("Inventory not active, code: " . $inventory_code); continue; }
  console_write("Inventory to process, code: " . $inventory['code']);

  $inventoryid = $inventory['id'];
  $inventory_code = $inventory['code'];

  $tax_inventory = inventorydetail([ 'warehouses', 'costprices' ], [ 'code'=>$inventory_code . 'T' ]);
  if(!$tax_inventory){
    $tax_inventory_code = $inventory['code'] . 'T';
    $tax_inventory = inventoryentry([
      'isactive'=>$inventory['isactive'],
      'code'=>$inventory['code'] . 'T',
      'description'=>$inventory['description'],
      'unit'=>$inventory['unit'],
      'price'=>$inventory['price'],
      'minqty'=>$inventory['minqty'],
      'defaultsalesmanid'=>$inventory['defaultsalesmanid']
    ]);
    $tax_inventory = inventorydetail(null, [ 'id'=>$tax_inventory['id'] ]);
    console_write("Tax inventory created. code: " . $tax_inventory['code']);
  }
  else
    console_write("Tax inventory selected, code: " . $tax_inventory['code']);

  $tax_inventoryid = $tax_inventory['id'];

  $initial_inventory = [ 'code'=>$inventory_code ];
  if(is_array($inventory['warehouses'])){
    $total_warehouse_qty = 0;
    foreach($inventory['warehouses'] as $warehouse){
      if(intval($warehouse['qty']) == 0) continue;
      $total_warehouse_qty += $warehouse['qty'];
      console_write($warehouse['code'] . ', qty: ' . $warehouse['qty']);
      $initial_inventory[$warehouse['code']] = $warehouse['qty'];
    }
    console_write("Total Warehouse Qty: $total_warehouse_qty");
  }
  $initial_inventory['total'] = $total_warehouse_qty;
  $initial_inventory['warehouses'] = $inventory['warehouses'];
  $initial_inventories[] = $initial_inventory;

  if($total_warehouse_qty == 0){
    console_write("Product not available in any warehouse");
    continue;
  }

  if(is_array($inventory['costprices'])){
    $total_costprice_qty = 0;
    foreach($inventory['costprices'] as $costprice){
      console_write("Qty: $costprice[qty], Costprice: $costprice[price]");
      $total_costprice_qty += $costprice['qty'];
    }
    console_write("Total Costprice Qty: $total_costprice_qty");
  }

  // Inventory adjustment
  foreach($inventory['warehouses'] as $warehouse){
    if(intval($warehouse['qty']) == 0) continue;

    $items = [];
    $warehouse_qty = $warehouse['qty'];
    foreach($inventory['costprices'] as $index=>$costprice){
      if($warehouse_qty == 0 || $costprice['qty'] == 0) continue;
      $current_qty = $warehouse_qty <= $costprice['qty'] ? $warehouse_qty : $costprice['qty'];
      $current_costprice = $costprice['price'];
      $warehouse_qty -= $current_qty;
      $inventory['costprices'][$index]['qty'] -= $current_qty;

      array_push($items,
        [
          'inventoryid'=>$inventoryid,
          'inventorycode'=>$inventory['code'],
          'qty' => $current_qty * -1,
          'unitprice' => $current_costprice
        ],
        [
          'inventoryid'=>$tax_inventory['id'],
          'inventorycode'=>$tax_inventory['code'],
          'qty' => $current_qty,
          'unitprice' => $current_costprice
        ]
      );
    }

    if($warehouse_qty > 0){
      array_push($items,
        [
          'inventoryid'=>$inventoryid,
          'inventorycode'=>$inventory['code'],
          'qty' => $warehouse_qty * -1,
          'unitprice' => 0
        ],
        [
          'inventoryid'=>$tax_inventory['id'],
          'inventorycode'=>$tax_inventory['code'],
          'qty' => $warehouse_qty,
          'unitprice' => 0
        ]
      );
    }

    if(count($items) > 0){
      $adj = [
        'date'=>$date,
        'code'=>$projectid . str_pad($inventoryid, 5, '0', STR_PAD_LEFT) . str_pad($warehouse['id'], 2, '0', STR_PAD_LEFT),
        'description'=>"ADJUST $inventory[code] to $tax_inventory[code] in $warehouse[name]",
        'warehouseid'=>$warehouse['id'],
        'details'=>$items
      ];
      inventoryadjustmententry($adj);
      console_write($adj['date'] . "\t" . $adj['code'] . "\t" . $adj['description']);
      foreach($items as $item)
        console_write("\t\t" . "Code: $item[inventorycode], Qty: $item[qty], Costprice: $item[unitprice]");
    }

    // Set non taxed inventory to inactive
    inventorymodify([ 'id'=>$inventory['id'], 'isactive'=>0 ]);

  }

  // Customer inventory
  pm("delete from customerinventory where inventoryid = ?", [ $tax_inventory['id'] ]);
  pm("insert into customerinventory select null, customerid, $tax_inventoryid, price / 1.1 from customerinventory where inventoryid = ?", [ $inventoryid ]);

}

console_write('--------------------------------------------');

console_write("Calculating inventories...");
inventory_warehouse_calc();
inventoryqty_calculateall();
console_write("Inventory calculated.");

console_write('--------------------------------------------');

if(count($initial_inventories) > 0){
  foreach($initial_inventories as $inventory){

    $inventory_code = $inventory['code'];

    $tax_inventory = inventorydetail([ 'warehouses', 'costprices' ], [ 'code'=>$inventory_code . 'T' ]);
    $tax_inventory_warehouses = $tax_inventory['warehouses'];
    $tax_inventory_warehouses = array_index($tax_inventory_warehouses, [ 'code' ], 1);

    $initial_inventory['tax_code'] = $tax_inventory['code'];

    $log = [];
    $log[] = str_pad($inventory_code . '/' . $tax_inventory['code'], 15, ' ', STR_PAD_RIGHT);
    $error = 0;
    foreach($inventory['warehouses'] as $warehouse){
      if($warehouse['qty'] == 0) continue;
      $log[] = str_pad($warehouse['code'] . ":" . $warehouse['qty'] . '/' . $tax_inventory_warehouses[$warehouse['code']]['qty'], 20, ' ', STR_PAD_RIGHT);
      if($warehouse['qty'] != $tax_inventory_warehouses[$warehouse['code']]['qty']) $error = 1;
    }

    $log[] = $error ? ">>ERR" : ">>>OK";


    console_write(implode("\t", $log));

  }
}
else{
  console_write("No inventory processed.");
}

console_write('--------------------------------------------');

console_write('Completed in ' . (microtime(1) - $start_time) . PHP_EOL . PHP_EOL);


function onshutdown(){
  $content = ob_get_contents();
  if(!empty($content)) ob_end_clean();
  $err = error_get_last();
  if($err && $err['type'] == 1){
    $message = $err["message"];
    echo "ERROR: " . $message . PHP_EOL;
  }
  else{
    echo $content;
  }
}

function console_write($message){
  echo $message . PHP_EOL;
}

function console_error($message){
  die("ERROR:$message" . PHP_EOL);
}


?>