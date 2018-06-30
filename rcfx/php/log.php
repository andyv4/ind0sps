<?php


$__LOGS = array();
function log_write($message){
  global $__LOGS;
  $__LOGS[] = $message;
}
function log_write_end(){
  global $__LOGS;
  //$log = implode("\n", $__LOGS) . "\n\n";
  //file_put_contents('/Applications/XAMPP/htdocs/reconv/usr/log.txt', $log);
}

$__LOGS_BENCH = array();
$__LOGS_BENCH_KEY = array();
function log_bench_start($key){
  global $__LOGS_BENCH, $__LOGS_BENCH_KEY;
  $__LOGS_BENCH[] = microtime(1);
  $__LOGS_BENCH_KEY[] = $key;
}
function log_bench_end($message = ''){
  global $__LOGS_BENCH, $__LOGS_BENCH_KEY;
  if(count($__LOGS_BENCH) > 0)
    log_write(array_pop($__LOGS_BENCH_KEY) . (strlen($message) > 0 ? '(' . $message . ')' : '') . ' in ' . sprintf('%.6f', microtime(1) - array_pop($__LOGS_BENCH)));
}

?>