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
require_once __DIR__ . '/../api/purchaseorder.php';

// Gather existing purchaseorderid
$rows = pmrs("select distinct(purchaseorderid) as purchaseorderid from purchaseorderpayment");
$existing_purchaseorderid = [];
if(is_array($rows))
  foreach($rows as $row)
    $existing_purchaseorderid[$row['purchaseorderid']] = 1;

$offset = 0;
$limit = 1000; // Process every 1000 row
$inserts = $params = $deletes = [];
$counter = 0;
do{

  $rows = pmrs("select * from purchaseorder order by `id` limit {$limit} offset {$offset}");

  if(is_array($rows)){
    foreach($rows as $row){

      $id = $row['id'];
      $code = $row['code'];
      $ispaid = $row['ispaid'];
      $currencyrate = $row['currencyrate'];
      $paymentdate = $row['paymentdate'];
      $totalpaymentamount = $row['paymentamount'];
      $paymentaccountid = $row['paymentaccountid'];

      if($ispaid){

        $paymentamount = $totalpaymentamount / $currencyrate;

        if($paymentamount > 0 && $paymentaccountid > 0){

          echo $row['code'] . "\t" . $row['ispaid'] . "\t" . $row['paymentdate'] . "\t" . $row['paymentamount'] . "\t" . $row['paymentaccountid'] . "\n";

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

          if(isset($existing_purchaseorderid[$id]))
            $deletes[] = "delete from purchaseorderpayment where purchaseorderid = " . $id . ";";

        }

      }

      if(count($inserts) > 500){

        $counter += count($inserts);

        if(count($deletes) > 0){
          pm(implode(';', $deletes));
          $deletes = [];
        }

        pm("insert into purchaseorderpayment (purchaseorderid, chartofaccountid, `type`, `date`, `amount`,
          currencyrate, totalamount) values " . implode(', ', $params), $inserts);
        $inserts = $params = [];

        echo $counter . "\n";

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

  pm("insert into purchaseorderpayment (purchaseorderid, chartofaccountid, `type`, `date`, `amount`,
          currencyrate, totalamount) values " . implode(', ', $params), $inserts);
  $inserts = $params = [];

  echo $counter . "\n";

}

?>