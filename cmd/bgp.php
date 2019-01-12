<?php

$stateurl = __DIR__ . '/../usr/system/.bgp';
$runurl = __DIR__ . '/../usr/system/.bgpr';
$waiturl = __DIR__ . '/../usr/system/.bgpw';
$logurl = __DIR__ . '/../usr/system/.bgpl';

// Prerequisites
$start_time = microtime(1);
$__system = 'bgp';

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = isset($argv[1]) ? $argv[1] : 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/chartofaccount.php';
require_once __DIR__ . '/../api/code.php';
require_once __DIR__ . '/../api/inventory.php';
require_once __DIR__ . '/../api/customer.php';
require_once __DIR__ . '/../api/supplier.php';
require_once __DIR__ . '/../api/warehouse.php';
date_default_timezone_set('Asia/Jakarta');
ini_set('memory_limit', '256M');

$applog_dest = 'echo';
ob_start();
register_shutdown_function("onshutdown");

// Validation
if(!is_writable(dirname($stateurl))){ echo "Unable to start, data path is not writtable." . PHP_EOL; exit(); }
if(!is_writable(dirname($runurl))){ echo 'Unable to write process state.'; exit(); }
if(file_exists($runurl)){
  echo 'Another process running...';
  file_put_contents($waiturl, 1);
  exit();
}

// Load state
$state = file_exists($stateurl) ? json_decode(file_get_contents($stateurl), true) : [];

// Process
$counter = 0;
do{
  echo 'bgp-' . $counter . PHP_EOL;
  run_bgp();
  $counter++;
}
while(file_exists($waiturl));

// Save state
$state['lastrunon'] = date('Y-m-d H:i:s');
$state['duration'] = microtime(1) - $start_time;
$state['cycle_count'] = $counter;
file_put_contents($stateurl, json_encode($state) . PHP_EOL);
chmod($stateurl, 0666);

echo "COMPLETED in " . (microtime(1) - $start_time) . "s" . PHP_EOL . PHP_EOL;

function run_bgp(){

  global $runurl, $waiturl, $state;

  file_put_contents($runurl, '');
  if(file_exists($waiturl)) unlink($waiturl);

  $inventoryids = [];

  // Calculate costprice
  /*
  $t1 = microtime(1);
  $cp_lastupdatedon = isset($state['cp_lastupdatedon']) ? $state['cp_lastupdatedon'] : -1;
  if($cp_lastupdatedon == -1)
    $inventories = pmrs("select inventoryid, min(`date`) as `date`, max(lastupdatedon) as lastupdatedon from inventorybalance group by inventoryid");
  else
    $inventories = pmrs("select inventoryid, `date`, lastupdatedon from inventorybalance where lastupdatedon > ?", [ $cp_lastupdatedon ]);

  $np_lastupdatedon = pmc("select max(lastupdatedon) from inventorybalance where lastupdatedon > ?", [ $cp_lastupdatedon ]);
  $cp_lastupdatedon = $np_lastupdatedon > 0 ? $np_lastupdatedon : $cp_lastupdatedon;

  if(is_array($inventories) && count($inventories) > 0){
    foreach($inventories as $inventory){
      if(!$inventory['inventoryid']) continue;
      if(isset($inventoryids[$inventory['inventoryid']])) continue;
      inventorycostprice($inventory['inventoryid'], $inventory['date']);
      //echo 'Id: ' . $inventory['inventoryid'] . ' ' . $inventory['date'] . ' ' . $inventory['lastupdatedon'] . ' calculated.' . PHP_EOL;
      $inventoryids[$inventory['inventoryid']] = [];
    }
  }
  $state['cp_lastupdatedon'] = $cp_lastupdatedon;
  echo "Last updated on: $cp_lastupdatedon" . PHP_EOL;
  echo "costprice_calculate " . (microtime(1) - $t1) . PHP_EOL;
  */

  // Post processing...
  //inventoryqty_calculate(array_keys($inventoryids));

  $t1 = microtime(1);
  inventoryqty_calculateall();
  echo "inventoryqty_calculateall " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  inventory_warehouse_calc();
  echo "inventory_warehouse_calc " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  inventorysoldqty_calculate(array_keys($inventoryids));
  echo "inventorysoldqty_calculate " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  warehousecalculateall();
  echo "warehousecalculateall " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  inventory_purchaseorderqty();
  echo "inventory_purchaseorderqty " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  customercalculateall();
  echo "customercalculateall " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  supplierpayablecalculateall();
  echo "supplierpayablecalculateall " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  chartofaccountrecalculateall();
  echo "chartofaccountrecalculateall " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  if(in_array(date('g'), [ 6, 12, 18 ]))inventoryanalysisgenerate();
  echo "inventoryanalysisgenerate " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  purchaseinvoice_release_unused();
  echo "purchaseinvoice_release_unused " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  salesinvoice_fill_unfilled_cogs();
  echo "salesinvoice_fill_unfilled_cogs " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  salesinvoice_release_unused();
  echo "salesinvoice_release_unused " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  code_release_unused();
  echo "code_release_unused " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  taxreservationpool_release_unused();
  echo "taxreservationpool_release_unused " . (microtime(1) - $t1) . PHP_EOL;

  $t1 = microtime(1);
  $sql = 'select code from salesinvoice where `id` not in (select salesinvoiceid from salesinvoiceinventory);';
  $rows = pmrs($sql);
  if(is_array($rows))
    foreach($rows as $row){
      $code = $row['code'];
      $id = pmc("select `id` from salesinvoice where code = ?", [ $code ]);

      $data1 = pmr("select `id`, data1 from userlog where data1 like ? and data1 like '%inventorycode%' order by `id` desc limit 1", [ "%$code%" ]);
      $data1id = $data1['id'];
      $data1 = $data1['data1'];
      if($id > 0 && $data1){
        $data1 = unserialize($data1);
        $data1['id'] = $id;
        if(isset($data1['inventories']) && is_array($data1['inventories']) && count($data1['inventories']) > 0)
          salesinvoicemodify($data1);
      }
    }
  echo "salesinvoicemodify " . (microtime(1) - $t1) . PHP_EOL;

  customer_suspended_calc();

  // Check code
  $code_exists = pmc("select count(*) from code where `year` = ?", [ system_date('Y') ]);
  if(!$code_exists) code_build(system_date('Y'));
 //SPS/18/10805
}

function onshutdown(){

  global $runurl, $logurl;
  $content = ob_get_contents();
  ob_end_clean();

  $err = error_get_last();
  if(isset($err['type']) && $err['type'] == 1){
    $type = $err["type"];
    $message = $err["message"];
    $file = $err["file"];
    $line = $err["line"];
    if(is_writable($logurl)) file_put_contents($logurl, "[" . date('Y-m-d H:i:s') . "]\n" . $message . "\n" . $file . ":" . $line . "\n\n", FILE_APPEND);
    echo $message . "\n" . $file . ":" . $line . "\n";
  }
  else{
    echo $content;
  }

  if(file_exists($runurl) && is_writable($runurl)) unlink($runurl);

}

?>