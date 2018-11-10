<?php

function session_expire($userid){

  $sessions = pmrs("select uid from `session` where isopen = 1 and userid = ?", [ $userid ]); //  and timediff(now(), lastupdatedon) > '00:00:03'
  if(is_array($sessions))
    foreach($sessions as $session)
      pm("update session set isopen = 0 where uid = ?", [ $session['uid'] ]);

}

?>