<?php

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';

migrate_view_from_file_to_db();
echo app_dir();
function migrate_view_from_file_to_db(){

  global $mysqlpdo_database;

  $module_names = [
    'category',
    'chartofaccount',
    'currency',
    'customer',
    'inventory2',
    'inventoryadjustment',
    'inventoryanalysis',
    'inventorybalance',
    'inventorycard',
    'inventoryformula',
    'journalvoucher',
    'pettycash',
    'purchaseinvoice',
    'purchaseorder',
    'salesinvoice',
    'salesinvoicegroup',
    'salesorder',
    'salesreceipt',
    'salesreturn',
    'salesreconcile',
    'salesreturn',
    'sampleinvoice',
    'staff',
    'supplier',
    'warehouse',
    'warehousetransfer',
  ];

  $users = pmrs("select `id`, userid from user");

  $queries = $params = [];

  $system_modules = [];

  foreach($users as $user){

    $userid = $user['id'];
    $user_name = $user['userid'];
    $cachedir = md5($userid . $mysqlpdo_database);

    if(file_exists(usr_dir() . '/' . $cachedir)){

      echo "Folder {$userid}:{$user_name} exists \n";

      foreach($module_names as $module_name){

        if(file_exists(usr_dir() . '/' . $cachedir . '/' . md5($module_name))){

          echo "\tModule $module_name exists \n";

          $data = file_get_contents(usr_dir() . '/' . $cachedir . '/' . md5($module_name));
          $obj = unserialize($data);

          if(isset($obj['presets'])){

            foreach($obj['presets'] as $preset){

              $view_name = $preset['text'];
              if(!$view_name) $view_name = 'View-' . time();

              $queries[] = "(?, ?, ?, ?)";
              array_push($params, $module_name, $userid, $view_name, serialize($preset));

              if(count($queries) > 0){
                pm("insert into module_view (module_name, userid, view_name, `data`) values " . implode(', ', $queries)
                  . "on duplicate key update `data` = values(`data`)", $params);
                $queries = $params = [];
              }

              $system_module_key = $module_name . '/' . $view_name . '/' . md5(json_encode($obj['presets']));
              if(!isset($system_modules[$system_module_key]))
                $system_modules[$system_module_key] = 0;
              $system_modules[$system_module_key]++;

            }

          }

        }
        else{

        }

      }

    }
    else{
      echo "Folder $userid not exists \n";
    }


  }

  echo json_encode($system_modules, JSON_PRETTY_PRINT) . "\n";

}

?>