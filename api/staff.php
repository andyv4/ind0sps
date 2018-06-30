<?php

require_once dirname(__FILE__) . '/log.php';

function staff_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>60, 'type'=>'html', 'html'=>'staff_options'),
    array('active'=>1, 'name'=>'online', 'text'=>'Online', 'width'=>40, 'type'=>'html', 'html'=>'staff_online'),
    array('active'=>1, 'name'=>'multilogin', 'text'=>'Multi Login', 'width'=>70, 'type'=>'html', 'html'=>'staff_multilogin'),
    array('active'=>1, 'name'=>'name', 'text'=>'Nama', 'width'=>120),
    array('active'=>1, 'name'=>'dept', 'text'=>'Dept', 'width'=>100),
    array('active'=>1, 'name'=>'position', 'text'=>'Position', 'width'=>100),
    array('active'=>0, 'name'=>'userid', 'text'=>'ID Login', 'width'=>100),
    array('active'=>1, 'name'=>'session_lasturl', 'text'=>'Akses Terakhir', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'session_lastupdatedon', 'text'=>'Akses Terakhir Pada', 'width'=>120, 'datatype'=>'datetime'),
    array('active'=>1, 'name'=>'session_browser', 'text'=>'Browser', 'width'=>200, 'nodittomark'=>1),
  );
  return $columns;

}
function staffdetail($columns, $filters){

  $staff = mysql_get_row('user', $filters, $columns);

  if($staff){
    $privileges = pmrs("SELECT * FROM userprivilege WHERE `userid` = ?", array($staff['id']));
    $staff['privileges'] = $privileges;
  }

  return $staff;

}
function stafflist($columns, $filters, $sorts = null){
  $staffs = mysql_get_rows('user', $columns, $filters);
  return $staffs;
}

function staffentry($obj){

  $lock_file = __DIR__ . "/../usr/system/staff_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $name = $obj['name'];
  $userid = $obj['userid'];
  $password = $obj['password'];
  $dept = $obj['dept'];
  $position = $obj['position'];
  $confirm = $obj['password_confirm'];
  $accesslevel = $obj['accesslevel'];
  $multilogin = $obj['multilogin'];

  if(empty($password)) throw new Exception('Password tidak boleh kosong.');
  if($password != $confirm) throw new Exception('Password tidak sama.');

  $id = pmi("INSERT INTO user(name, userid, password, salesable, createdon, createdby, accesslevel, multilogin, dept, `position`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    array($name, $userid, md5($password), 1, date('YmdHis'), $_SESSION['user']['id'], $accesslevel, $multilogin, $dept, $position));

  if(isset($obj['privilege_inventorycostprice'])){
    $privilege_inventorycostprice = $obj['privilege_inventorycostprice'] ? 1 : 0;
    pm("UPDATE userprivilege SET `value` = ? WHERE userid = ? AND `key` = ? AND `module` = ?", array($privilege_inventorycostprice, $id, 'inventory', 'costprice'));
  }

  if(isset($obj['salesinvoicetype']))
    userkeystoreadd($id, 'privilege.salesinvoicetype', $obj['salesinvoicetype']);

  if(isset($obj['sales_allowed_salesman']))
    userkeystoreadd($id, 'privilege.sales_allowed_salesman', $obj['sales_allowed_salesman']);

  if(isset($obj['purchaseinvoicetype']))
    userkeystoreadd($id, 'privilege.purchaseinvoicetype', $obj['purchaseinvoicetype']);

  if(isset($obj['salesinvoice_modifytaxcode']))
    userkeystoreadd($id, 'privilege.salesinvoice_modifytaxcode', $obj['salesinvoice_modifytaxcode']);

  if(isset($obj['chartofaccounttype']) && is_array($obj['chartofaccounttype'])){
    $chartofaccounttotal = pmc("select count(*) from chartofaccount");
    $chartofaccounttype = [];
    foreach($obj['chartofaccounttype'] as $temp)
      if($temp['granted']) $chartofaccounttype[] = $temp['id'];
    $chartofaccounttype = count($chartofaccounttype) == $chartofaccounttotal ? '*' : implode(',', $chartofaccounttype);
    userkeystoreadd($id, 'privilege.chartofaccounttype', $chartofaccounttype);
  }

  userlog('staffentry', $obj, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);

}
function staffmodify($obj){

  $id = $obj['id'];
  $staff = staffdetail(null, array('id'=>$id));

  if(!$staff) exc('Staff tidak terdaftar.');

  $lock_file = __DIR__ . "/../usr/system/staff_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedcols = array();

  if(isset($obj['name']))
    $updatedcols['name'] = $obj['name'];

  if(isset($obj['userid']))
    $updatedcols['userid'] = $obj['userid'];

  if(isset($obj['dept']))
    $updatedcols['dept'] = $obj['dept'];

  if(isset($obj['position']))
    $updatedcols['position'] = $obj['position'];

  if(isset($obj['password']) && !empty($password)){

    $password = $obj['password'];
    $confirm = $obj['password_confirm'];

    if(empty($password)) throw new Exception('Password tidak boleh kosong.');
    if($password != $confirm) throw new Exception('Password tidak sama.');

    $updatedcols['password'] = md5($password);

  }

  if(isset($obj['loginhour']))
    $updatedcols['loginhour'] = $obj['loginhour'];

  if(isset($obj['accesslevel'])){
    $updatedcols['accesslevel'] = $obj['accesslevel'];
  }
  if(isset($obj['multilogin'])){
    $updatedcols['multilogin'] = $obj['multilogin'] ? 1 : 0;
  }

  if(isset($obj['privilege_inventorycostprice'])){
    $privilege_inventorycostprice = $obj['privilege_inventorycostprice'] ? 1 : 0;
    pm("UPDATE userprivilege SET `value` = ? WHERE userid = ? AND `module` = ? AND `key` = ?", array($privilege_inventorycostprice, $id, 'inventory', 'costprice'));
  }

  if(count($updatedcols) > 0)
    mysql_update_row('user', $updatedcols, array('id'=>$id));

  if(isset($obj['salesinvoicetype']))
    userkeystoreadd($id, 'privilege.salesinvoicetype', $obj['salesinvoicetype']);

  if(isset($obj['notification_enabled']))
    userkeystoreadd($id, 'privilege.notification_enabled', $obj['notification_enabled']);

  if(isset($obj['sales_allowed_salesman']))
    userkeystoreadd($id, 'privilege.sales_allowed_salesman', $obj['sales_allowed_salesman']);

  if(isset($obj['purchaseinvoicetype']))
    userkeystoreadd($id, 'privilege.purchaseinvoicetype', $obj['purchaseinvoicetype']);

  if(isset($obj['salesinvoice_modifytaxcode']))
    userkeystoreadd($id, 'privilege.salesinvoice_modifytaxcode', $obj['salesinvoice_modifytaxcode']);

  if(isset($obj['chartofaccounttype']) && is_array($obj['chartofaccounttype'])){
    $chartofaccounttotal = pmc("select count(*) from chartofaccount");
    $chartofaccounttype = [];
    foreach($obj['chartofaccounttype'] as $temp)
      if($temp['granted']) $chartofaccounttype[] = $temp['id'];
    $chartofaccounttype = count($chartofaccounttype) == $chartofaccounttotal ? '*' : implode(',', $chartofaccounttype);
    userkeystoreadd($id, 'privilege.chartofaccounttype', $chartofaccounttype);
  }

  userlog('staffmodify', $staff, $updatedcols, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);

}

?>