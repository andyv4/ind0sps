<?php

if(!isset($argv[1]) || date('Y', strtotime($argv[1])) <= 1970) die("Usage: php cutoff.php {cutoff date}\n\n");

$cutoff_date = date('Ymd', strtotime($argv[1]));

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/chartofaccount.php';
require_once __DIR__ . '/../api/inventory.php';
require_once __DIR__ . '/../api/journalvoucher.php';

$t1 = microtime(1);

$ib_start_date = pmc("select min(`date`) from inventorybalance where `date` < ?", [ $cutoff_date ]);
$jv_start_date = pmc("select min(`date`) from journalvoucher where `date` < ?", [ $cutoff_date ]);
$start_date = strtotime($ib_start_date) < strtotime($jv_start_date) ? $ib_start_date : $jv_start_date;
$end_date = date('Ymd', strtotime($cutoff_date));

// Create backup schema
$tmp = sys_get_temp_dir() . '/tmp.sql';
$tmps = $mysqlpdo_database . '_' . date('y', strtotime($start_date)) . '_' . date('y', strtotime($end_date));
exec("mysqldump -u{$mysqlpdo_username} -p{$mysqlpdo_password} indosps > {$tmp}");
pm("drop schema if exists {$tmps}");
pm("create schema {$tmps}");
exec("mysql -u{$mysqlpdo_username} -p{$mysqlpdo_password} {$tmps} < {$tmp}");
pm("drop schema if exists indosps_full");
pm("create schema indosps_full");
exec("mysql -u{$mysqlpdo_username} -p{$mysqlpdo_password} indosps_full < {$tmp}");

$mysqlpdo_database = $tmps; pdo_con(1, 1);

// Process backup schema
$tschms = [ 'purchaseinvoice', 'purchaseorder', 'pettycash', 'warehousetransfer', 'inventoryadjustment',
  'salesreturn', 'salesreceipt', 'salesinvoicegroup', 'salesinvoice', 'sampleinvoice', 'salesorder', 'journalvoucher',
  'inventorybalance' ];
pm("SET foreign_key_checks = 0");
foreach($tschms as $scm){
  echo "{$tmps}.{$scm}\n";
  pm("delete from {$tmps}.{$scm} where `date` > ?", [ $end_date ]);
}
pm("SET foreign_key_checks = 1");

echo "inventoryqty_calculate\n";
inventoryqty_calculate([]);
echo "inventorywarehouse_calc\n";
inventorywarehouse_calc([]);
echo "chartofaccountrecalculate\n";
$rows = pmrs("select `id` from {$tmps}.chartofaccount");
foreach($rows as $row)
  chartofaccountrecalculate($row['id']);

// Process db

$mysqlpdo_database = 'indosps'; pdo_con(1, 1);

pm("SET foreign_key_checks = 0");
foreach($tschms as $scm){
  echo "indosps.{$scm}\n";
  pm("delete from indosps.{$scm} where `date` <= ?", [ $end_date ]);
}
pm("SET foreign_key_checks = 1");

// Create opening balance

// - Chart of account
$details = [];
$rows = pmrs("select `id` from indosps.chartofaccount");
$op_amount = 0;
foreach($rows as $row){
  $coaid = $row['id'];
  $amount = pmc("select amount from {$tmps}.chartofaccount where `id` = ?", [ $coaid ]);
  $op_amount += $amount * -1;
  $detail = [
    'coaid'=>$row['id'],
    'debitamount'=>$amount < 0 ? 0 : abs($amount),
    'creditamount'=>$amount < 0 ? abs($amount) : 0,
  ];
  $details[] = $detail;
}
$details[] = [
  'coaid'=>9,
  'debitamount'=>$op_amount < 0 ? 0 : abs($op_amount),
  'creditamount'=>$op_amount < 0 ? abs($op_amount) : 0
];
$journal = array(
  'ref'=>'OP',
  'refid'=>date('Y', strtotime($end_date)),
  'type'=>'A',
  'date'=>$end_date,
  'description'=>'Cutoff-' . date('Y', strtotime($end_date)) . ' Opening Balance',
  'details'=>$details
);
journalvoucherremove([ 'ref'=>'OP', 'refid'=>date('Y', $end_date) ]);
journalvoucherentries([ $journal ]);

echo "chartofaccountrecalculate\n";
$rows = pmrs("select `id` from {$tmps}.chartofaccount");
foreach($rows as $row)
  chartofaccountrecalculate($row['id']);

// - Inventory balance
$rows = pmrs("select `id` from warehouse");
$warehouseids = [];
foreach($rows as $row)
  $warehouseids[] = $row['id'];
$inventoryids = [];
$rows = pmrs("select `id` from inventory");
foreach($rows as $row)
  $inventoryids[] = $row['id'];

$queries = $params = [];
pm("delete from inventorybalance where section = 7");
foreach($inventoryids as $inventoryid){
  foreach($warehouseids as $warehouseid){

    $obj = pmr("select SUM(`in` - `out`) as qty, AVG(unitamount) as unitamount from {$tmps}.inventorybalance where inventoryid = ? and warehouseid = ? and `date` <= ?",
      [ $inventoryid, $warehouseid, $end_date ]);
    $qty = $obj['qty'];
    $unitamount = $obj['unitamount'];

    if($qty === 0) continue;

    echo $inventoryid . '/' . $warehouseid . '/' . $qty . '/' . $unitamount . PHP_EOL;

    $queries[] = "(?, ?, ?, ?, ?, ?, ?, ?)";
    array_push($params, $inventoryid, $warehouseid, 7, $end_date, $qty, 0, $unitamount, ($qty * $unitamount));

    if(count($queries) > 1000){
      pm("insert into inventorybalance(inventoryid, warehouseid, `section`, `date`, `in`, `out`, unitamount, amount) values " .
        implode(', ', $queries), $params);
      $queries = $params = [];
    }
  }
}
if(count($queries) > 0){
  pm("insert into inventorybalance(inventoryid, warehouseid, `section`, `date`, `in`, `out`, unitamount, amount) values " .
    implode(', ', $queries), $params);
}
echo "inventoryqty_calculate\n";
inventoryqty_calculate([]);
echo "inventorywarehouse_calc\n";
inventorywarehouse_calc([]);

exec("mysqldump -u{$mysqlpdo_username} -p{$mysqlpdo_password} indosps > {$tmp}");
pm("drop schema if exists indosps");
pm("create schema indosps");
exec("mysql -u{$mysqlpdo_username} -p{$mysqlpdo_password} indosps < {$tmp}");



echo "\nOK in " . (microtime(1) - $t1) . "\n";


?>