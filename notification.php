<?php

require_once __DIR__ . '/api/notification.php';

notification_generate();

$notifications = notification_list();

ui_async();

?>
<style type="text/css">

  .notification-list{

  }
  .notification-indice{
    width: 8px;
    height: 8px;
    background: #ddd;
    border-radius: 50%;
    vertical-align: middle;
    margin-right: 7px;
  }
  .notification-indice.unread{
    background: #666;
  }
  .notification-item{
    padding: 7px;
  }
  .notification-item h5{
    vertical-align: middle;
  }
  .notification-item .notification-time{
    color: #aaa;
  }
  .notification-separator{
    height: 1px;
    background: #eee;
  }

</style>
<div class="padding20">

  <?php if(count($notifications) > 0){ ?>
    <h4><?=count($notifications)?> Notifications</h4>
  <?php } else { ?>
    <h4>No Notification</h4>
  <?php } ?>

  <div class="height10"></div>

  <div class="notification-list">

    <?php foreach($notifications as $index=>$notification){ ?>
      <?php if($index > 0){ ?><div class="notification-separator"></div><?php } ?>
      <div class="notification-item">
        <span class="notification-indice"></span>
        <span>
        <h5><?=$notification['title']?></h5>
        <div class="height5"></div>
        <small class="notification-time"><?=date('j-M-Y H:i:s', strtotime($notification['createdon']))?></small>
      </span>
      </div>
    <?php } ?>

  </div>


</div>