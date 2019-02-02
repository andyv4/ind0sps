<?php

/**
 * Migrate purchase order payment from old model to new model
 * - Find all old purchase order, create payment entry on purchase order payment
 *
 */

// Requirement
$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';
require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/purchaseinvoice.php';

// Gather existing purchaseinvoiceid
$rows = pmrs("select distinct(purchaseinvoiceid) as purchaseinvoiceid from purchaseinvoicepayment");
$existing_purchaseinvoiceid = [];
if(is_array($rows))
  foreach($rows as $row)
    $existing_purchaseinvoiceid[$row['purchaseinvoiceid']] = 1;

$offset = 0;
$limit = 1000; // Process every 1000 row
$inserts = $params = $deletes = [];
$counter = 0;
do{

  $rows = pmrs("select * from purchaseinvoice order by `id` limit {$limit} offset {$offset}");

  if(is_array($rows)){
    foreach($rows as $row){

      $id = $row['id'];
      $code = $row['code'];
      $ispaid = $row['ispaid'];
      $currencyrate = $row['currencyrate'];
      $paymentdate = $row['paymentdate'];
      $paymentdate = $row['paymentdate'];
      $totalpaymentamount = $row['paymentamount'];
      $paymentaccountid = $row['paymentaccountid'];

      if($totalpaymentamount > 0 && $paymentaccountid > 0 && strlen($paymentdate) == 10 && $paymentdate != '0000-00-00'){

        $paymentamount = $totalpaymentamount / $currencyrate;

        echo $row['code'] . "\t" . $row['paymentdate'] . "\t" . $row['paymentamount'] . "\t" . $row['paymentaccountid'] . "\n";

        array_push($inserts,
          $id,
          $paymentaccountid,
          1,
          $paymentdate,
          $paymentamount,
          $currencyrate,
          $totalpaymentamount
        );
        $params[] = "(?, ?, ?, ?, ?, ?, ?)";

        if(isset($existing_purchaseinvoiceid[$id]))
          $deletes[] = "delete from purchaseinvoicepayment where purchaseinvoiceid = " . $id;

      }

      if(count($inserts) > 500){

        $counter += count($inserts);

        if(count($deletes) > 0){
          pm(implode(';', $deletes));
          $deletes = [];
        }

        pm("insert into purchaseinvoicepayment (purchaseinvoiceid, chartofaccountid, `type`, `date`, `amount`,
          currencyrate, totalamount) values " . implode(', ', $params), $inserts);
        $inserts = $params = [];

      }

    }
  }

  $offset += $limit;

}
while($rows != null);

if(count($inserts) > 0){

  $counter += count($inserts);

  if(count($deletes) > 0){
    pm(implode(';', $deletes));
    $deletes = [];
  }

  pm("insert into purchaseinvoicepayment (purchaseinvoiceid, chartofaccountid, `type`, `date`, `amount`,
          currencyrate, totalamount) values " . implode(', ', $params), $inserts);
  $inserts = $params = [];

}

?>