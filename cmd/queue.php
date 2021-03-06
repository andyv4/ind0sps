<?php

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = isset($argv[1]) ? $argv[1] : 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

require_once __DIR__ . '/../rcfx/php/pdo.php';
require_once __DIR__ . '/../rcfx/php/util.php';
require_once __DIR__ . '/../api/inventory.php';


$queue_dir = realpath(__DIR__ . '/../queue');
if(!file_exists($queue_dir)) die("Queue directory not exists.");

$log_path = realpath(__DIR__ . '/../usr/system/queue.log');
if(!file_exists($log_path)) file_put_contents($log_path);
if(!is_writable($log_path)) die("Unable to write log file.");

while(true){

  try{

    $files = glob($queue_dir . '/*');
    if(is_array($files)) usort($files, "sort_file_by_time_asc");

    foreach($files as $file){

      $t1 = microtime(1);
      $queue = json_decode(file_get_contents($file), true);
      $queue_str = implode(';', $queue);
      if(count($queue) > 0) $queue_str .= ';';
      $func = create_function('', $queue_str);
      $func();

      echo $file . " executed in " . (microtime(1) - $t1) . "\n";
      unlink($file);

    }

  }
  catch(Exception $ex){

    file_put_contents($log_path, $ex->getMessage() . " on " . $ex->getFile() . ":" . $ex->getLine() . "\n", FILE_APPEND);

  }

  sleep(50);

}


function sort_file_by_time_asc($a, $b){

  return filemtime($a) - filemtime($b);

}

?>