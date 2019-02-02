<?php

/**
 * Job function
 *
 *
 * Cron:
 * add this to cron to retry unsuccesful job
 * // * * * * * cd /var/www/indosps/3.9.2/jobs && php retry.php >> /dev/null 2>&1
 *
 */

if(!isset($argv[1])) return;

$job_id = $argv[1];

include 'config.php';

// Retrieve job
$job = pmr("select * from jobs where `id` = ? and running < 1", [ $job_id ]);
if(!$job) return;

// Job can only be executed once at a time
$log_path = realpath(__DIR__ . '/../usr/system');
if(file_exists($log_path . '/job-' . $job['id'])) return;
file_put_contents($log_path . '/job-' . $job['id'], json_encode($job));

// make sure job is unique
$log_path = realpath(__DIR__ . '/../usr/system');

$target = $job['target'];
$payload = json_decode($job['payload'], true);
$attempt = $job['attempt'];
$target = explode('_', $target);
$file = $target[0];
$function = implode('_', $target);

if($attempt >= MAX_ATTEMPT) exit();

ob_start();
register_shutdown_function("onshutdown");
$start_time = microtime(1);

mysql_update_row('jobs',
  [
    'running'=>1,
    'pid'=>getmypid()
  ],
  [
    'id'=>$job['id']
  ]
);

try{

  include __DIR__ . '/../api/' . $file . '.php';
  $result = call_user_func_array($function, $payload);

  $status = 1;
  $message = $result ? json_encode($result) : '';

}
catch(Exception $ex){

  $status = 2;
  $message = $ex->getMessage() . "@" . $ex->getFile() . ":" . $ex->getLine();

}

// Mark job as available
unlink($log_path . '/job-' . $job['id']);

function onshutdown(){

  $content = ob_get_contents();
  ob_end_clean();

  global $attempt, $ellapsed, $status, $message, $job_id, $start_time;

  $err = error_get_last();
  if(isset($err['type']) && $err['type'] == E_ERROR){

    $status = 2;
    $message = json_encode($err);

  }

  $ellapsed = microtime(1) - $start_time;

  mysql_update_row("jobs", [
    'attempt'=>$attempt + 1,
    'ellapsed'=>$ellapsed,
    'running'=>0,
    'status'=>$status,
    'message'=>$message,
    'completedon'=>date('YmdHis')
  ], [
    'id'=>$job_id
  ]);

  exit();

}

?>