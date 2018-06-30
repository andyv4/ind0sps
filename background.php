<?php

$starttime = microtime(1);
set_time_limit(-1);
if(session_id() == '') session_start();

date_default_timezone_set("Asia/Jakarta");
error_reporting(E_ERROR | E_WARNING | E_PARSE);
set_time_limit(-1);
ini_set('memory_limit', '1G');

$mysqlpdo_database = 'indosps';
$verbose = 1;

require_once 'rcfx/php/pdo.php';
require_once 'rcfx/php/util.php';
require_once 'api/config.php';
require_once 'api/inventory.php';
require_once 'api/chartofaccount.php';

//inventorycostpricecalculateall();

inventorycostprice_fifo(isset($argv[1]) ? $argv[1] : 0, isset($argv[2]) ? $argv[2] : 'null');

//inventorycostprice_fifo(150, 'null');

//if(file_exists('usr/' . $mysqlpdo_database . '.lock')){ echo 'Background process is running. no other threads spawned.'; exit; }
//file_put_contents('usr/' . $mysqlpdo_database . '.lock', '');

// Inventory cost price calculation
//inventorycostpricecalculateall();

// Chart of account amount calculation
//chartofaccountrecalculateall();

// Warehouse update
//warehousecalculateall();

//customerreceivablecalculateall();

//inventoryqty_calculateall();

//$_SESSION['dbschema'] = $prev_db;

echo 'Database: ' . $current_db . "\n";
echo 'Completed in ' . (microtime(1) - $starttime) . 's' . "\n";
echo "\n";
echo 'RAM used: ' . (memory_get_usage(1) / 1024) . 'Kb' . "\n";

//file_put_contents('/var/www/3.1/usr/background.log', "Completed in " . (microtime(1) - $starttime) . "s \n", FILE_APPEND);

//unlink('usr/' . $mysqlpdo_database . '.lock');

pm("INSERT INTO systemvar(`key`, `value`, lastupdatedon) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?, lastupdatedon = ?",
  array('background_log', (microtime(1) - $starttime), date('YmdHis'), (microtime(1) - $starttime), date('YmdHis')));

?>
<?php
if(isset($_GET['autorefresh']) && $_GET['autorefresh'] > 0){
?>
<script type="text/javascript">

  window.setTimeout("window.location = window.location", <?=$_GET['autorefresh'] * 1000?>);

</script>
<?php } ?>