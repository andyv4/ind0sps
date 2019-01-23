<?php

include 'config.php';

$jobs = pmrs("select `id` from jobs where `status` != 1 and running < 1 order by `id` asc limit 10");
echo json_encode($jobs);
if(!$jobs) return;

foreach($jobs as $job){
  job_run($job['id']);
  sleep(5);
}

?>