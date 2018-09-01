<?php

$time = microtime(1);

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/salesinvoice.php';

$rows = pmrs("select refid from userlog where `action` like 'salesinvoice%' and date(`timestamp`) >= 20180825 group by refid");
$salesinvoiceids = [];
echo count($salesinvoiceids) . "<br /><br />";
foreach($rows as $row)
  $salesinvoiceids[] = $row['refid'];

$salesinvoices = pmrs("select `id`, code, total from salesinvoice where `id` in (" . implode(', ', $salesinvoiceids) . ")");

echo "<table border=1>";
foreach($salesinvoices as $salesinvoice){

  $id = $salesinvoice['id'];
  $code = $salesinvoice['code'];
  $total = $salesinvoice['total'];
  $real_total = salesinvoice_total(salesinvoicedetail(null, array('id'=>$id)));

  echo "<tr>";
  echo "<td>$code</td>";
  echo "<td>$total</td>";
  echo "<td>$real_total</td>";
  echo "</tr>";

}
echo "</table>";


echo "<br /><br /> Completed in " . (microtime(1) - $time) . "\n" . count($salesinvoiceids);

?>