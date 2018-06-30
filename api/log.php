<?php

function userlog($action, $data1 = '', $data2 = '', $userid = 0, $refid = 0){

  $exists = pmc("SELECT count(*) FROM information_schema.TABLES WHERE (TABLE_SCHEMA = ?) AND (TABLE_NAME = ?)", [ 'indosps', 'userlog' ]);

  if($exists){
    $timestamp = date('YmdHis');
    $query = "INSERT INTO userlog(`action`, data1, data2, `timestamp`, `userid`, `refid`) VALUES (?, ?, ?, ?, ?, ?)";
    $params = array($action, serialize($data1), serialize($data2), $timestamp, $userid, $refid);
    pm($query, $params);
  }

}

?>