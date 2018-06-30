<?php

$stateurl = __DIR__ . '/../usr/system/.fifo';
$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';
$applog_dest = 'echo';
$timezone = 'Asia/Jakarta';

$start_time = microtime(1);
date_default_timezone_set($timezone);

function onshutdown(){

  $content = ob_get_contents();

  $err = error_get_last();
  if($err){
    $message = $err["message"];
    $file = $err["file"];
    $line = $err["line"];

    applog('Error', $message . " " . $file . ":" . $line);
  }
  else{
    echo $content;
  }

}
function onexception($ex){
  applog('Exception', $ex->getMessage() . " " . $ex->getFile() . ":" . $ex->getLine());
}
register_shutdown_function("onshutdown");
set_exception_handler('onexception');

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventory.php';

pm("UPDATE inventorybalance t1 SET createdon = (SELECT createdon FROM purchaseinvoice WHERE id = t1.refid) WHERE ref = 'PI';");
applog(__FILE__, "Ref:PI updated");
pm("UPDATE inventorybalance t1 SET createdon = (SELECT createdon FROM salesinvoice WHERE id = t1.refid) WHERE ref = 'SI';");
applog(__FILE__, "Ref:SI updated");
pm("UPDATE inventorybalance t1 SET createdon = (SELECT createdon FROM inventoryadjustment WHERE id = t1.refid) WHERE ref = 'IA';");
applog(__FILE__, "Ref:IA updated");
pm("UPDATE inventorybalance t1 SET createdon = (SELECT createdon FROM sampleinvoice WHERE id = t1.refid) WHERE ref = 'SJS';");
applog(__FILE__, "Ref:SJS updated");
pm("UPDATE inventorybalance t1 SET createdon = (SELECT createdon FROM warehousetransfer WHERE id = t1.refid) WHERE ref = 'WT';");
applog(__FILE__, "Ref:WT updated");
pm("DELETE FROM inventorybalance WHERE section = 9");
applog(__FILE__, "Section 9 cleared");

pm("
CREATE TABLE `indosps`.`inventoryformula` (
  `inventoryid` INT UNSIGNED NOT NULL,
  `date` DATETIME NOT NULL,
  `m3` DOUBLE(19,6) NULL,
  `cbmperkg` DOUBLE(19,6) NULL,
  `freightcharge` DOUBLE(16,2) NULL,
  `lastupdatedon` DATETIME NULL,
  PRIMARY KEY (`inventoryid`, `date`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_bin;
");
applog(__FILE__, "Inventory formula created");

pm("
ALTER TABLE `indosps`.`inventorybalance` 
ADD COLUMN `fifo_info` TEXT NULL AFTER `lastupdatedon`,
ADD COLUMN `avg_info` TEXT NULL AFTER `fifo_info`,
ADD COLUMN `m3` DOUBLE(19,6) NULL AFTER `avg_info`,
ADD COLUMN `cbmperkg` DOUBLE(19,6) NULL AFTER `m3`,
ADD COLUMN `freightcharge` DOUBLE(16,2) NULL AFTER `cbmperkg`,
ADD COLUMN `purchaseprice` DOUBLE(16,2) NULL,
ADD INDEX `fk_inventorybalance_3` (`ref` ASC, `refid` ASC);
");
applog(__FILE__, "Inventory balance updated");

pm("
ALTER TABLE `indosps`.`inventory` 
ADD INDEX `fk_inventory_idx_0` (`code` ASC, `description` ASC);
");
applog(__FILE__, "Inventory updated");

echo "Completed in " . (microtime(1) - $start_time) . "s" . PHP_EOL;

?>