<?php

if(php_sapi_name() != 'cli') cli_error("This command is only executable from cli.");
if(count($_SERVER['argv']) < 2) cli_error("Invalid argument");

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventory.php';

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

switch($_SERVER['argv'][1]){

  case 'check':
    inventory_check();
    break;

}

function inventory_check(){

  if(!isset($_SERVER['argv'][2])) cli_error('inventory check require inventory code parameter.');

  $inventory_codes = [];
  $param = $_SERVER['argv'][2];

  // All inventorys
  if($param == '*'){

    $rows = pmrs("select code from indosps.inventory");
    if(is_array($rows))
      foreach($rows as $row)
        $inventory_codes[$row['code']] = 1;

  }

  // Specific inventorys
  else{

    $param = explode(',', $_SERVER['argv'][2]);

    /**
     * Retrieve inventory code to process, uniquely
     */
    foreach($param as $inventory_code){
      $inventory_code = trim($inventory_code);
      $inventory_codes[$inventory_code] = 1;
    }

  }

  $inventory_codes = array_keys($inventory_codes);
  if(count($inventory_codes) == 0) cli_error("Invalid product code.");
  $inventories = pmrs("select `id`, code from indosps.inventory where code in ('" . implode("', '", $inventory_codes) . "')");
  $inventory_ids = [];
  foreach($inventories as $inventory)
    $inventory_ids[] = $inventory['id'];

  /**
   * Check cost price
   */
  foreach($inventory_ids as $inventoryid){

    inventorycostprice($inventoryid);

  }

}

function cli_error($message){

  die($message . "\n");

}
function cli_info($message){

  echo($message . "\n");

}

?>