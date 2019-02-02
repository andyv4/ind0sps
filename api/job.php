<?php

function job_create_and_run($function, $params){

  //return call_user_func_array($function, $params);

  $id = mysql_insert_row('jobs', [
    'createdon'=>date('YmdHis'),
    'attempt'=>0,
    'status'=>0,
    'target'=>$function,
    'payload'=>json_encode($params)
  ]);

  job_run($id);

}

function job_run($id){

  $path = realpath(__DIR__ . '/../jobs/job.php');

  exec("/usr/bin/php {$path} {$id} >/dev/null 2>&1 &");

}

?>