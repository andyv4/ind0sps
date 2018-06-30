<?php

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/purchaseinvoice.php';
require_once __DIR__ . '/../api/purchaseorder.php';
require_once __DIR__ . '/../api/journalvoucher.php';


$purchaseinvoiceid = 4176;
$purchaseinvoice = purchaseinvoicedetail(null, [ 'id'=>$purchaseinvoiceid ]);
$purchaseorderid = $purchaseinvoice['purchaseorderid'];
$jv = journalvoucherlist('*', null, [
  [ 'type'=>'(' ],
  [ 'type'=>'(' ],
  [ 'name'=>'ref', 'operator'=>'=', 'value'=>'PI' ],
  [ 'name'=>'refid', 'operator'=>'=', 'value'=>$purchaseinvoiceid ],
  [ 'type'=>')' ],
  [ 'type'=>'OR' ],
  [ 'type'=>'(' ],
  [ 'name'=>'ref', 'operator'=>'=', 'value'=>'PO' ],
  [ 'name'=>'refid', 'operator'=>'=', 'value'=>$purchaseorderid ],
  [ 'type'=>')' ],
  [ 'type'=>')' ],
]);

$jv = purchaseinvoicecalculate($purchaseinvoiceid);

//echo json_encode($jv, JSON_PRETTY_PRINT);


?>