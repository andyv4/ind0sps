<?php

$start_time = microtime(1);

echo "<pre>";

set_time_limit(-1);

$projectid = 'T1';
$date = date('Y-m-d');
$log_file = __DIR__ . '/usr/tx_log_' . date('Y-m-d-H-i-s') . '.log';

require_once __DIR__ . '/api/inventory.php';
require_once __DIR__ . '/api/inventoryadjustment.php';

ui_async();

$path = __DIR__ . '/usr/tax_inventory.csv';
if(!file_exists($path)) die("Path not exists. ($path)");
$inventory_codes = explode(PHP_EOL, file_get_contents($path));
//$inventory_codes = [ 'B025' ];
//$inventory_codes = array_splice($inventory_codes, 0, 1);

$adjs = pmrs("select `id` from inventoryadjustment where code like '" . $projectid . "%'");
if(is_array($adjs))
  foreach($adjs as $adj)
    inventoryadjustmentremove([ 'id'=>$adj['id'] ]);
echo_and_log("Removed existing project data. " . PHP_EOL . PHP_EOL);

$inventories = pmrs("select inventoryid, min(`date`) as `date`, max(lastupdatedon) as lastupdatedon from inventorybalance where inventoryid = 62 group by inventoryid");
if(is_array($inventories) && count($inventories) > 0){
  echo "Calculating cost price..." . PHP_EOL;
  foreach($inventories as $inventory){
    if(!$inventory['inventoryid']) continue;
    if(isset($inventoryids[$inventory['inventoryid']])) continue;
    inventorycostprice($inventory['inventoryid'], $inventory['date']);
    echo 'Id: ' . $inventory['inventoryid'] . ' ' . $inventory['date'] . ' ' . $inventory['lastupdatedon'] . ' calculated.' . PHP_EOL;
  }
}
inventoryqty_calculateall();
inventory_warehouse_calc();

file_put_contents($log_file, '');

$inexists_inventories = [];
$proceeded_inventories = [];
foreach($inventory_codes as $inventory_code){

  echo PHP_EOL . '***';

  // Check if inventory code exists
  $inventory = pmr("select isactive, code, description, unit, price, minqty, defaultsalesmanid from inventory where code = ?", [ $inventory_code ]);
  if(!$inventory){
    $inexists_inventories[] = $inventory_code;
    continue;
  }

  $inventory = inventorydetail([ 'warehouses', 'costprices' ], [ 'code'=>$inventory_code ]);
  $proceeded_inventories[$inventory_code] = $inventory;
  $inventoryid = $inventory['id'];

  echo_and_log("[$inventory[code] - $inventory[description]]" . PHP_EOL);

  // Find tax inventory
  $tax_inventory = pmr("select `id`, isactive, code, description, unit, price, minqty, defaultsalesmanid from inventory where description = ? and taxable = 1", [ $inventory['description'] ]);

  // If tax inventory not found, create new
  if(!$tax_inventory){
    $tax_inventory_code = $inventory['code'] . 'T';

    $tax_inventory = inventoryentry([
      'isactive'=>$inventory['isactive'],
      'code'=>$inventory['code'],
      'description'=>$inventory['description'],
      'unit'=>$inventory['unit'],
      'price'=>$inventory['price'],
      'minqty'=>$inventory['minqty'],
      'defaultsalesmanid'=>$inventory['defaultsalesmanid']
    ]);
    $tax_inventory = inventorydetail(null, [ 'id'=>$tax_inventory['id'] ]);

    echo_and_log("Created $tax_inventory[code]" . PHP_EOL);
  }

  echo_and_log("Tax Inventory Code: $tax_inventory[code]" . PHP_EOL);

  if(is_array($inventory['warehouses'])){
    $total_warehouse_qty = 0;
    foreach($inventory['warehouses'] as $warehouse){
      if(intval($warehouse['qty']) == 0) continue;
      $total_warehouse_qty += $warehouse['qty'];
      echo_and_log(ucwords(strtolower($warehouse['name'])) . ', qty: ' . $warehouse['qty'] . PHP_EOL);
    }
    echo_and_log("Total Warehouse Qty: $total_warehouse_qty" . PHP_EOL);
  }

  if(is_array($inventory['costprices'])){
    $total_costprice_qty = 0;
    foreach($inventory['costprices'] as $costprice){
      echo_and_log("Qty: $costprice[qty], Costprice: $costprice[price]" . PHP_EOL);
      $total_costprice_qty += $costprice['qty'];
    }
    echo_and_log("Total Costprice Qty: $total_costprice_qty" . PHP_EOL . PHP_EOL);
  }

  // Inventory adjustment
  foreach($inventory['warehouses'] as $warehouse){
    if(intval($warehouse['qty']) == 0) continue;

    $items = [];
    $warehouse_qty = $warehouse['qty'];
    do{
      if(!is_array($inventory['costprices']) || count($inventory['costprices']) == 0) break;

      foreach($inventory['costprices'] as $costprice){
        if($warehouse_qty == 0) continue;
        $current_qty = $warehouse_qty <= $costprice['qty'] ? $warehouse_qty : $costprice['qty'];
        $current_costprice = $costprice['price'];
        $warehouse_qty -= $current_qty;

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
    }
    while($warehouse_qty > 0);

    if(count($items) > 0){
      $adj = [
        'date'=>$date,
        'code'=>$projectid . date('ymdHis') . str_pad($inventoryid, 3, '0', STR_PAD_LEFT) . str_pad($warehouse['id'], 2, '0', STR_PAD_LEFT),
        'description'=>"ADJUST $inventory[code] to $tax_inventory[code] in $warehouse[name]",
        'warehouseid'=>$warehouse['id'],
        'details'=>$items
      ];
      inventoryadjustmententry($adj);

      echo_and_log($adj['date'] . "\t" . $adj['code'] . "\t" . $adj['description'] . PHP_EOL);
      foreach($items as $item){
        echo_and_log("\t\t" . "Code: $item[inventorycode], Qty: $item[qty], Costprice: $item[unitprice]" . PHP_EOL);
      }
    }

  }

  // Send output immediately to browser
  flush();
  ob_flush();

}

inventoryqty_calculateall();
inventory_warehouse_calc();

echo_and_log(PHP_EOL . PHP_EOL . PHP_EOL);

foreach($inventory_codes as $inventory_code){

  $tax_inventory_code = $inventory_code . 'T';

  $inventory = $proceeded_inventories[$inventory_code];
  $tax_inventory = inventorydetail([ 'costprices', 'warehouses' ], [ 'code'=>$tax_inventory_code ]);

  $echo = $inventory['code'] . "\t";
  foreach($inventory['warehouses'] as $warehouse){
    if($warehouse['qty'] == 0) continue;
    $echo .= $warehouse['id'] . ":" . $warehouse['qty'] . "\t";
  }
  echo_and_log($echo . PHP_EOL);

  $inventory_warehouses_index = array_index($inventory['warehouses'], [ 'id' ], 1);

  $echo = $tax_inventory['code'] . "\t";
  foreach($tax_inventory['warehouses'] as $warehouse){
    if($warehouse['qty'] == 0) continue;
    $echo .= $warehouse['id'] . ":" . $warehouse['qty'] . ":" . $inventory_warehouses_index[$warehouse['id']]['qty'] . "\t";
  }
  echo_and_log($echo . PHP_EOL);

}

echo PHP_EOL . PHP_EOL . "Completed in " . (microtime(1) - $start_time) . PHP_EOL;

echo "</pre>";


function echo_and_log($message){

  echo $message;
  global $log_file;
  file_put_contents($log_file, $message, FILE_APPEND);

}

?>