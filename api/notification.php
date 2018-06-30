<?php

require_once __DIR__ . '/customer.php'; // Require customer module
require_once __DIR__ . '/salesinvoice.php'; // Require customer module
require_once __DIR__ . '/pettycash.php'; // Require customer module

function notification_generate(){

  $queries = $params = [];

  $key = 'customer.due.salesinvoice';
  $due_customers = customer_suspended_calc();
  if(count($due_customers) > 0){
    $title = 'Pelanggan dengan hutang jatuh tempo.';
    $description = count($due_customers) . ' pelanggan belum melunasi faktur yang jatuh tempo/macet.';
    $queries[] = "insert into notification (`key`, title, description, lastupdatedon) VALUES (?, ?, ?, ?) on duplicate key update `title` = ?, description = ?, lastupdatedon = ?";
    array_push($params, $key, $title, $description, system_date('YmdHis'), $title, $description, system_date('YmdHis'));
  }
  else{
    $queries[] = "delete from notification where `key` = ?";
    $params[] = $key;
  }

  $key = 'salesinvoice.cash.due';
  $count = pmc("select count(*) from salesinvoice where customerdescription like 'cash' and ispaid = 0");
  if($count > 0){
    $title = 'Faktur cash belum lunas';
    $description = $count . ' faktur cash belum lunas.';
    $queries[] = "insert into notification (`key`, title, description, lastupdatedon) VALUES (?, ?, ?, ?) on duplicate key update `title` = ?, description = ?, lastupdatedon = ?";
    array_push($params, $key, $title, $description, system_date('YmdHis'), $title, $description, system_date('YmdHis'));
  }
  else{
    $queries[] = "delete from notification where `key` = ?";
    $params[] = $key;
  }

  if(count($queries) > 0){
    pm(implode(';', $queries), $params);
  }

}

function notification_list(){

  //notification_generate();

  return [];

  $rows = pmrs("select * from notification", [ date('Ymd') ]);

  $notifications = [];
  if(is_array($rows)){
    foreach($rows as $row){

      switch($row['key']){

        case 'customer.due.salesinvoice':
          if(privilege_get('customer', 'list')) $notifications[] = $row;
          break;

        case 'salesinvoice.cash.due':
          if(privilege_get('salesinvoice', 'list')) $notifications[] = $row;
          break;

      }

    }
  }
  return $notifications;

}

?>