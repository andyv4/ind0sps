<?php

function userdb(){

  mysql_droptable('user');
  mysql_droptable('userkeystore');
  mysql_droptable('userprivilege');

  mysql_createtable('user', array(
    array('name'=>'id', 'type'=>'id'),
    array('name'=>'isactive', 'type'=>'int', 'maxlength'=>1),
    array('name'=>'name', 'type'=>'string', 'maxlength'=>100),
    array('name'=>'userid', 'type'=>'string', 'maxlength'=>100),
    array('name'=>'email', 'type'=>'string', 'maxlength'=>100),
    array('name'=>'password', 'type'=>'string', 'maxlength'=>32),
    array('name'=>'salesable', 'type'=>'int', 'maxlength'=>1),
    array('name'=>'createdon', 'type'=>'datetime')
  ));

  mysql_createtable('userkeystore', array(
    array('name'=>'id', 'type'=>'id'),
    array('name'=>'userid', 'type'=>'fk', 'fkref'=>'user', 'fkrefid'=>'id'),
    array('name'=>'key', 'type'=>'string', 'maxlength'=>100),
    array('name'=>'value', 'type'=>'text')
  ));

  mysql_createtable('userprivilege', array(
    array('name'=>'id', 'type'=>'id'),
    array('name'=>'userid', 'type'=>'fk', 'fkref'=>'user', 'fkrefid'=>'id'),
    array('name'=>'module', 'type'=>'string', 'maxlength'=>100),
    array('name'=>'key', 'type'=>'string', 'maxlength'=>100),
    array('name'=>'value', 'type'=>'text')
  ));
}

function userdetail($columns, $filters){
  $user = mysql_get_row('user', $filters, $columns);

  if($user){
    $query = "SELECT * FROM userprivilege WHERE `userid` = ?";
    $privileges = pmrs($query, array($user['id']), array('log'=>0));
    $user['privileges'] = $privileges;
  }

  return $user;
}
function userlist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'isactive'=>'t1.isactive',
    'name'=>'t1.name',
    'email'=>'t1.email',
    'salesable'=>'t1.salesable',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.name'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM `user` t1 $wherequery $sortquery $limitquery";
  $users = pmrs($query, $params);
  return $users;
}

function userentry($user){
  $name = ov('name', $user, 1, array('notempty'=>1));
  $password = ov('password', $user, 1, array('notempty'));
  $passwordconf = ov('passwordconf', $user, 0);
  $userid = ov('userid', $user);
  $isactive = 1;
  $salesable = ov('salesable', $user, 0, 0);
  $privileges = ov('privileges', $user);
  $createdon = date('YmdHis');
  $loginhour = ov('loginhour', $user);

  if(strlen($userid) < 1) throw new Exception('User ID harus diisi.');
  if(intval(pmc("SELECT COUNT(*) FROM `user` WHERE userid = ?", array($userid)))) throw new Exception('User ID sudah ada, silakan menggunakan nama lain.');
  if(strlen($password) < 3) throw new Exception('Password minimal 3 huruf/angka.');
  if($password != $passwordconf) throw new Exception('Konfirmasi password tidak sama.');

  $query = "INSERT INTO `user` (`name`, isactive, userid, password, salesable, createdon, loginhour) VALUES (?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($name, $isactive, $userid, md5($password), $salesable, $createdon, $loginhour));

  userdefaultconfig($id);
  if(is_array($privileges))
    usermodify(array('id'=>$id, 'privileges'=>$user['privileges']));

  $user = array('id'=>$id);
  return $user;
}
function usermodify($obj){
  $id = ov('id', $obj, 1);
  $staff = staffdetail(null, array('id'=>$id));
  if(!$staff) throw new Exception('Staff tidak ada.');

  $updatedrow = array();
  if(isset($obj['isactive']) && $obj['isactive'] != $staff['isactive']) $updatedrow['isactive'] = $obj['isactive'];
  if(isset($obj['name']) && $obj['name'] != $staff['name']) $updatedrow['name'] = $obj['name'];
  if(isset($obj['email']) && $obj['email'] != $staff['email']) $updatedrow['email'] = $obj['email'];
  if(isset($obj['salesable']) && $obj['salesable'] != $staff['salesable']) $updatedrow['salesable'] = $obj['salesable'];
  if(isset($obj['userid']) && $obj['userid'] != $staff['userid']){
    if(strlen($obj['userid']) < 1) throw new Exception('User ID harus diisi.');
    if(intval(pmc("SELECT COUNT(*) FROM `user` WHERE userid = ?", array($obj['userid'])))) throw new Exception('User ID sudah ada, silakan menggunakan nama lain.');
    $updatedrow['userid'] = $obj['userid'];
  }
  if(isset($obj['password']) && !empty($obj['password']) && md5($obj['password']) != $staff['password']){
    if(strlen($obj['password']) < 3) throw new Exception('Password minimal 3 huruf/angka.');
    if($obj['password'] != $obj['passwordconf']) throw new Exception('Konfirmasi password tidak sama.');
    $updatedrow['password'] = md5($obj['password']);
  }
  if(isset($obj['loginhour']) && $obj['loginhour'] != $staff['loginhour'])
    $updatedrow['loginhour'] = $obj['loginhour'];
  if(count($updatedrow) > 0) mysql_update_row('user', $updatedrow, array('id'=>$id));

  if(isset($obj['privileges']) && is_array($obj['privileges'])){
    pm("DELETE FROM userprivilege WHERE userid = ?", array($id));

    $privileges = $obj['privileges'];

    $params = $paramstr = array();
    for($i = 0 ; $i < count($privileges) ; $i++){
      $privilege = $privileges[$i];
      $module = $privilege['module'];
      $list = $privilege['list'] == 'yes' ? 2 : ($privilege['list'] == 'no' ? 0 : 1);
      $new = $privilege['new'] ? 1 : 0;
      $modify = $privilege['modify'] ? 1 : 0;
      $delete = $privilege['delete'] ? 1 : 0;
      $print = $privilege['print'] ? 1 : 0;
      if($module == 'inventory') $costprice = $privilege['costprice'] ? 1 : 0;

      $paramstr[] = '(?, ?, ?, ?)';
      $paramstr[] = '(?, ?, ?, ?)';
      $paramstr[] = '(?, ?, ?, ?)';
      $paramstr[] = '(?, ?, ?, ?)';
      $paramstr[] = '(?, ?, ?, ?)';
      if($module == 'inventory') $paramstr[] = '(?, ?, ?, ?)';
      array_push($params, $id, $module, 'list', $list);
      array_push($params, $id, $module, 'new', $new);
      array_push($params, $id, $module, 'modify', $modify);
      array_push($params, $id, $module, 'delete', $delete);
      array_push($params, $id, $module, 'print', $print);
      if($module == 'inventory') array_push($params, $id, $module, 'costprice', $costprice);
    }
    $query = "INSERT INTO userprivilege(userid, `module`, `key`, `value`) VALUES " . implode(', ', $paramstr);
    pm($query, $params);
  }
}
function userremove($filters){
  $id = ov('id', $filters, 1);
  $query = "DELETE FROM `user` WHERE `id` = ?";
  pm($query, array($id));
}

function userkeystoreadd($userid, $key, $value){

  $query = "SELECT COUNT(*) FROM userkeystore WHERE userid = ? AND `key` = ?";
  $exists = intval(pmc($query, array($userid, $key), array('log'=>0)));
  if($exists){
    $query = "UPDATE userkeystore SET `value` = ? WHERE userid = ? AND `key` = ?";
    pm($query, array($value, $userid, $key), array('log'=>0));
  }
  else{
    $query = "INSERT INTO userkeystore(userid, `key`, `value`) VALUES (?, ?, ?)";
    pm($query, array($userid, $key, $value), array('log'=>0));
  }

}
function userkeystoreget($userid, $key, $default_value = ''){
  $exists = pmc("SELECT count(*) FROM userkeystore WHERE userid = ? AND `key` = ?", [ $userid, $key ]);
  if($exists){
    $query = "SELECT `value` FROM userkeystore WHERE userid = ? AND `key` = ?";
    $value = pmc($query, array($userid, $key), array('log'=>0));
  }
  else
    $value = $default_value;
  return $value;
}
function userkeystoreremove($userid, $key){
  pm("DELETE FROM userkeystore WHERE userid = ? AND `key` = ?", array($userid, $key), array('log'=>0));
}

function userprivilegeadd($userid, $module, $key, $value){
  $query = "INSERT INTO userprivilege(`userid`, `module`, `key`, `value`) VALUES (?, ?, ?, ?)";
  pm($query, array($userid, $module, $key, $value));
}

function userdefaultconfig($id, $fullaccess = 1){
  userkeystoreadd($id, 'sidebarstate', 1);
  userkeystoreadd($id, 'lasturl', 'datamanagement');
  userkeystoreadd($id, 'salesorderlisttemplates', serialize(array(
    array(
      'text'=>'Hari ini',
      'columns'=>array(
        array('text'=>'Status', 'active'=>1, 'name'=>'status', 'type'=>'enum', 'value'=>array('Baru','Cetak','Bayar')),
        array('text'=>'Tanggal', 'active'=>0, 'name'=>'date', 'type'=>'date'),
        array('text'=>'Nomor Invoice', 'active'=>1, 'name'=>'code', 'type'=>'string'),
        array('text'=>'Pelanggan', 'active'=>1, 'name'=>'customerdescription', 'type'=>'string'),
        array('text'=>'Subtotal', 'active'=>0, 'name'=>'subtotal', 'type'=>'money'),
        array('text'=>'Diskon%', 'active'=>0, 'name'=>'discount', 'type'=>'money'),
        array('text'=>'Diskon', 'active'=>0, 'name'=>'discountamount', 'type'=>'money'),
        array('text'=>'PPn', 'active'=>0, 'name'=>'taxamount', 'type'=>'money'),
        array('text'=>'Jumlah', 'active'=>1, 'name'=>'total', 'type'=>'money'),
        array('text'=>'Salesman', 'active'=>0, 'name'=>'salesmanname', 'width'=>'60px'),
        array('text'=>'Barang', 'active'=>1, 'name'=>'inventorysummary', 'type'=>'string'),
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'=', 'value'=>"date('Ymd')")
        ))
      )
    ),
    array(
      'text'=>'Bulan ini',
      'columns'=>array(
        array('text'=>'Status', 'active'=>1, 'name'=>'status', 'type'=>'enum', 'value'=>array('Baru','Cetak','Bayar')),
        array('text'=>'Tanggal', 'active'=>1, 'name'=>'date', 'type'=>'date'),
        array('text'=>'Nomor Invoice', 'active'=>1, 'name'=>'code', 'type'=>'string'),
        array('text'=>'Pelanggan', 'active'=>1, 'name'=>'customerdescription', 'type'=>'string'),
        array('text'=>'Subtotal', 'active'=>0, 'name'=>'subtotal', 'type'=>'money'),
        array('text'=>'Diskon%', 'active'=>0, 'name'=>'discount', 'type'=>'money'),
        array('text'=>'Diskon', 'active'=>0, 'name'=>'discountamount', 'type'=>'money'),
        array('text'=>'PPn', 'active'=>0, 'name'=>'taxamount', 'type'=>'money'),
        array('text'=>'Jumlah', 'active'=>1, 'name'=>'total', 'type'=>'money'),
        array('text'=>'Salesman', 'active'=>0, 'name'=>'salesmanname', 'width'=>'60px'),
        array('text'=>'Barang', 'active'=>1, 'name'=>'inventorysummary', 'type'=>'string'),
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'>=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m'), 1, 2014))"),
          array('operator'=>'<=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, 2014))")
        ))
      )
    ),
  )));
  userkeystoreadd($id, 'salesorderlisttemplatesidx', 0);
  userkeystoreadd($id, 'salesinvoicelisttemplates', serialize(array(
    array(
      'text'=>'Hari ini',
      'columns'=>array(
        array('text'=>'Status', 'active'=>1, 'name'=>'status', 'type'=>'enum', 'value'=>array('Baru','Partial','Bayar')),
        array('text'=>'Printed', 'active'=>1, 'name'=>'isprint'),
        array('text'=>'Tanggal', 'active'=>0, 'name'=>'date', 'type'=>'date'),
        array('text'=>'Nomor Invoice', 'active'=>1, 'name'=>'code', 'type'=>'string'),
        array('text'=>'Pelanggan', 'active'=>1, 'name'=>'customerdescription', 'type'=>'string'),
        array('text'=>'Subtotal', 'active'=>0, 'name'=>'subtotal', 'type'=>'money'),
        array('text'=>'Diskon%', 'active'=>0, 'name'=>'discount', 'type'=>'money'),
        array('text'=>'Diskon', 'active'=>0, 'name'=>'discountamount', 'type'=>'money'),
        array('text'=>'PPn', 'active'=>0, 'name'=>'taxamount', 'type'=>'money'),
        array('text'=>'Margin(%)', 'active'=>1, 'name'=>'avgsalesmargin', 'type'=>'percent'),
        array('text'=>'Jth Tempo', 'active'=>1, 'name'=>'duedays', 'type'=>'number'),
        array('text'=>'Jumlah', 'active'=>1, 'name'=>'total', 'type'=>'money'),
        array('text'=>'Barang', 'active'=>1, 'name'=>'inventorydescription', 'type'=>'string'),
        array('text'=>'Kts', 'active'=>1, 'name'=>'qty', 'align'=>'right'),
        array('text'=>'Harga', 'active'=>1, 'name'=>'unitprice', 'type'=>'money'),
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'=', 'value'=>"date('Ymd')")
        ))
      )
    ),
    array(
      'text'=>'Bulan ini',
      'columns'=>array(
        array('text'=>'Status', 'active'=>1, 'name'=>'status', 'type'=>'enum', 'value'=>array('Baru','Cetak','Bayar')),
        array('text'=>'Printed', 'active'=>1, 'name'=>'isprint'),
        array('text'=>'Tanggal', 'active'=>1, 'name'=>'date', 'type'=>'date'),
        array('text'=>'Nomor Invoice', 'active'=>1, 'name'=>'code', 'type'=>'string'),
        array('text'=>'Pelanggan', 'active'=>1, 'name'=>'customerdescription', 'type'=>'string'),
        array('text'=>'Subtotal', 'active'=>0, 'name'=>'subtotal', 'type'=>'money'),
        array('text'=>'Diskon%', 'active'=>0, 'name'=>'discount', 'type'=>'money'),
        array('text'=>'Diskon', 'active'=>0, 'name'=>'discountamount', 'type'=>'money'),
        array('text'=>'PPn', 'active'=>0, 'name'=>'taxamount', 'type'=>'money'),
        array('text'=>'Margin(%)', 'active'=>1, 'name'=>'avgsalesmargin', 'type'=>'percent'),
        array('text'=>'Jth Tempo', 'active'=>1, 'name'=>'duedays', 'type'=>'number'),
        array('text'=>'Jumlah', 'active'=>1, 'name'=>'total', 'type'=>'money'),
        array('text'=>'Barang', 'active'=>1, 'name'=>'inventorydescription', 'type'=>'string'),
        array('text'=>'Kts', 'active'=>1, 'name'=>'qty', 'align'=>'right'),
        array('text'=>'Harga', 'active'=>1, 'name'=>'unitprice', 'type'=>'money'),
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'>=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m'), 1, 2014))"),
          array('operator'=>'<=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, 2014))")
        ))
      )
    ),
    array(
      'text'=>'Jatuh Tempo',
      'columns'=>array(
        array('text'=>'Status', 'active'=>1, 'name'=>'status', 'type'=>'enum', 'value'=>array('Baru','Cetak','Bayar')),
        array('text'=>'Printed', 'active'=>1, 'name'=>'isprint'),
        array('text'=>'Tanggal', 'active'=>1, 'name'=>'date', 'type'=>'date'),
        array('text'=>'Nomor Invoice', 'active'=>1, 'name'=>'code', 'type'=>'string'),
        array('text'=>'Pelanggan', 'active'=>1, 'name'=>'customerdescription', 'type'=>'string'),
        array('text'=>'Subtotal', 'active'=>0, 'name'=>'subtotal', 'type'=>'money'),
        array('text'=>'Diskon%', 'active'=>0, 'name'=>'discount', 'type'=>'money'),
        array('text'=>'Diskon', 'active'=>0, 'name'=>'discountamount', 'type'=>'money'),
        array('text'=>'PPn', 'active'=>0, 'name'=>'taxamount', 'type'=>'money'),
        array('text'=>'Margin(%)', 'active'=>1, 'name'=>'avgsalesmargin', 'type'=>'percent'),
        array('text'=>'Jth Tempo', 'active'=>1, 'name'=>'duedays', 'type'=>'number'),
        array('text'=>'Jumlah', 'active'=>1, 'name'=>'total', 'type'=>'money'),
        array('text'=>'Barang', 'active'=>1, 'name'=>'inventorydescription', 'type'=>'string'),
        array('text'=>'Kts', 'active'=>1, 'name'=>'qty', 'align'=>'right'),
        array('text'=>'Harga', 'active'=>1, 'name'=>'unitprice', 'type'=>'money'),
      ),
      'filters'=>array(
        array('name'=>'duedays')
      )
    ),
    array(
      'text'=>'Semua',
      'columns'=>array(
        array('text'=>'Status', 'active'=>1, 'name'=>'status', 'type'=>'enum', 'value'=>array('Baru','Cetak','Bayar')),
        array('text'=>'Printed', 'active'=>1, 'name'=>'isprint'),
        array('text'=>'Tanggal', 'active'=>1, 'name'=>'date', 'type'=>'date'),
        array('text'=>'Nomor Invoice', 'active'=>1, 'name'=>'code', 'type'=>'string'),
        array('text'=>'Pelanggan', 'active'=>1, 'name'=>'customerdescription', 'type'=>'string'),
        array('text'=>'Subtotal', 'active'=>0, 'name'=>'subtotal', 'type'=>'money'),
        array('text'=>'Diskon%', 'active'=>0, 'name'=>'discount', 'type'=>'money'),
        array('text'=>'Diskon', 'active'=>0, 'name'=>'discountamount', 'type'=>'money'),
        array('text'=>'PPn', 'active'=>0, 'name'=>'taxamount', 'type'=>'money'),
        array('text'=>'Margin(%)', 'active'=>1, 'name'=>'avgsalesmargin', 'type'=>'percent'),
        array('text'=>'Jth Tempo', 'active'=>1, 'name'=>'duedays', 'type'=>'number'),
        array('text'=>'Jumlah', 'active'=>1, 'name'=>'total', 'type'=>'money'),
        array('text'=>'Barang', 'active'=>1, 'name'=>'inventorydescription', 'type'=>'string'),
        array('text'=>'Kts', 'active'=>1, 'name'=>'qty', 'align'=>'right'),
        array('text'=>'Harga', 'active'=>1, 'name'=>'unitprice', 'type'=>'money'),
      ),
      'filters'=>array()
    ),
  )));
  userkeystoreadd($id, 'salesinvoicelisttemplatesidx', 0);
  userkeystoreadd($id, 'salesreceiptlisttemplates', serialize(array(
    array(
      'text'=>'Hari ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'', 'name'=>'selector', 'width'=>'16px'),
        array('active'=>1, 'text'=>'Status', 'name'=>'status', 'width'=>'45px'),
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Nomor', 'name'=>'code', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Pelanggan', 'name'=>'customerdescription', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'amount', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Kwitansi', 'name'=>'salesvouchercode'),
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'=', 'value'=>"date('Ymd')")
        ))
      )
    ),
    array(
      'text'=>'Bulan ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'', 'name'=>'selector'),
        array('active'=>1, 'text'=>'Status', 'name'=>'status'),
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date'),
        array('active'=>1, 'text'=>'Nomor', 'name'=>'code'),
        array('active'=>1, 'text'=>'Pelanggan', 'name'=>'customerdescription'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'amount'),
        array('active'=>1, 'text'=>'Kwitansi', 'name'=>'salesvouchercode'),
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'>=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m'), 1, 2014))"),
          array('operator'=>'<=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, 2014))")
        ))
      )
    ),
  )));
  userkeystoreadd($id, 'salesreceiptlisttemplatesidx', 0);
  userkeystoreadd($id, 'purchaseorderlisttemplates', serialize(array(
    array(
      'text'=>'Hari ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'Status', 'name'=>'status', 'type'=>'html', 'callback'=>'ui_purchaseorderlist_status', 'width'=>'45px'),
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Nomor Invoice', 'name'=>'code', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Supplier', 'name'=>'supplierdescription', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'total', 'width'=>'150px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'=', 'value'=>"date('Ymd')")
        ))
      )
    ),
    array(
      'text'=>'Bulan ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'Status', 'name'=>'status', 'type'=>'html', 'callback'=>'ui_purchaseorderlist_status', 'width'=>'45px'),
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Nomor Invoice', 'name'=>'code', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Supplier', 'name'=>'supplierdescription', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'total', 'width'=>'150px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'>=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m'), 1, 2014))"),
          array('operator'=>'<=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, 2014))")
        ))
      )
    ),
  )));
  userkeystoreadd($id, 'purchaseorderlisttemplatesidx', 0);
  userkeystoreadd($id, 'purchaseinvoicelisttemplates', serialize(array(
    array(
      'text'=>'Hari ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'Status', 'name'=>'status', 'type'=>'html', 'callback'=>'ui_purchaseinvoicelist_status', 'width'=>'45px'),
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Nomor Invoice', 'name'=>'code', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Supplier', 'name'=>'supplierdescription', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'total', 'width'=>'150px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'=', 'value'=>"date('Ymd')")
        ))
      )
    ),
    array(
      'text'=>'Bulan ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'Status', 'name'=>'status', 'type'=>'html', 'callback'=>'ui_purchaseinvoicelist_status', 'width'=>'45px'),
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Nomor Invoice', 'name'=>'code', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Supplier', 'name'=>'supplierdescription', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'total', 'width'=>'150px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'>=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m'), 1, date('Y')))"),
          array('operator'=>'<=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, date('Y')))")
        ))
      )
    ),
    array(
      'text'=>'Tahun ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'Status', 'name'=>'status', 'type'=>'html', 'callback'=>'ui_purchaseinvoicelist_status', 'width'=>'45px'),
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Nomor Invoice', 'name'=>'code', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Supplier', 'name'=>'supplierdescription', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'total', 'width'=>'150px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'>=', 'value'=>"date('Ymd', mktime(0, 0, 0, 1, 1, date('Y')))"),
          array('operator'=>'<=', 'value'=>"date('Ymd', mktime(0, 0, 0, 12, 31, date('Y')))")
        ))
      )
    ),
  )));
  userkeystoreadd($id, 'purchaseinvoicelisttemplatesidx', 0);
  userkeystoreadd($id, 'pettycashlisttemplates', serialize(array(
    array(
      'text'=>'Hari ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Deskripsi', 'name'=>'description', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'total', 'width'=>'150px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'=', 'value'=>"date('Ymd')")
        ))
      )
    ),
    array(
      'text'=>'Bulan ini',
      'columns'=>array(
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Deskripsi', 'name'=>'description', 'width'=>'150px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'total', 'width'=>'150px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'date', 'value'=>array(
          array('operator'=>'>=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m'), 1, 2014))"),
          array('operator'=>'<=', 'value'=>"date('Ymd', mktime(0, 0, 0, date('m') + 1, 0, 2014))")
        ))
      )
    ),
  )));
  userkeystoreadd($id, 'pettycashlisttemplatesidx', 0);
  userkeystoreadd($id, 'customerlisttemplates', serialize(array(
    array(
      'text'=>'Aktif',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'70px'),
        array('active'=>1, 'text'=>'Deskripsi Pelanggan', 'name'=>'description', 'width'=>'240px'),
        array('active'=>1, 'text'=>'Kota', 'name'=>'city', 'width'=>'80px'),
        array('active'=>1, 'text'=>'Diskon', 'name'=>'discount', 'width'=>'40px', 'datatype'=>'discount'),
        array('active'=>1, 'text'=>'Pajak', 'name'=>'taxable', 'width'=>'40px', 'datatype'=>'bool'),
        array('active'=>1, 'text'=>'Piutang', 'name'=>'receivable', 'width'=>'100px', 'datatype'=>'money'),
        array('active'=>1, 'text'=>'Batas Kredit', 'name'=>'creditlimit', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Lama Kredit', 'name'=>'creditterm', 'width'=>'100px')
      ),
      'filters'=>array(
        array('name'=>'isactive', 'value'=>array(
          array('operator'=>'=', 'value'=>"1")
        ))
      ),
      'sorts'=>array(
        array('name'=>'description', 'type'=>'asc')
      )
    ),
    array(
      'text'=>'Piutang',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'70px'),
        array('active'=>1, 'text'=>'Deskripsi Pelanggan', 'name'=>'description', 'width'=>'240px'),
        array('active'=>1, 'text'=>'Kota', 'name'=>'city', 'width'=>'80px'),
        array('active'=>1, 'text'=>'Diskon', 'name'=>'discount', 'width'=>'40px', 'datatype'=>'discount'),
        array('active'=>1, 'text'=>'Pajak', 'name'=>'taxable', 'width'=>'40px', 'datatype'=>'bool'),
        array('active'=>1, 'text'=>'Piutang', 'name'=>'receivable', 'width'=>'100px', 'datatype'=>'money'),
        array('active'=>1, 'text'=>'Batas Kredit', 'name'=>'creditlimit', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Lama Kredit', 'name'=>'creditterm', 'width'=>'100px')
      ),
      'filters'=>array(),
      'sorts'=>array(
        array('name'=>'receivable', 'type'=>'desc')
      )
    ),
    array(
      'text'=>'Non Aktif',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'70px'),
        array('active'=>1, 'text'=>'Deskripsi Pelanggan', 'name'=>'description', 'width'=>'240px'),
        array('active'=>1, 'text'=>'Kota', 'name'=>'city', 'width'=>'80px'),
        array('active'=>1, 'text'=>'Diskon', 'name'=>'discount', 'width'=>'40px', 'datatype'=>'discount'),
        array('active'=>1, 'text'=>'Pajak', 'name'=>'taxable', 'width'=>'40px', 'datatype'=>'bool'),
        array('active'=>1, 'text'=>'Piutang', 'name'=>'receivable', 'width'=>'100px', 'datatype'=>'money'),
        array('active'=>1, 'text'=>'Batas Kredit', 'name'=>'creditlimit', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Lama Kredit', 'name'=>'creditterm', 'width'=>'100px')
      ),
      'filters'=>array(
        array('name'=>'isactive', 'value'=>array(
          array('operator'=>'=', 'value'=>"0")
        ))
      ),
      'sorts'=>array(
        array('name'=>'description', 'type'=>'desc')
      )
    ),
  )));
  userkeystoreadd($id, 'customerlisttemplatesidx', 0);
  userkeystoreadd($id, 'supplierlisttemplates', serialize(array(
    array(
      'text'=>'Aktif',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'70px'),
        array('active'=>1, 'text'=>'Deskripsi Pelanggan', 'name'=>'description', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Kota', 'name'=>'city', 'width'=>'80px'),
        array('active'=>1, 'text'=>'Hutang', 'name'=>'payable', 'width'=>'100px', 'datatype'=>'money'),
      ),
      'filters'=>array(
        array('name'=>'isactive', 'value'=>array(
          array('operator'=>'=', 'value'=>"1")
        ))
      ),
      'sorts'=>array(
        array('name'=>'description', 'type'=>'asc')
      )
    ),
    array(
      'text'=>'Hutang',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'70px'),
        array('active'=>1, 'text'=>'Deskripsi Pelanggan', 'name'=>'description', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Kota', 'name'=>'city', 'width'=>'80px'),
        array('active'=>1, 'text'=>'Hutang', 'name'=>'payable', 'width'=>'100px', 'datatype'=>'money'),
      ),
      'filters'=>array(
        array('name'=>'isactive', 'value'=>array(
          array('operator'=>'=', 'value'=>"1")
        ))
      ),
      'sorts'=>array(
        array('name'=>'payable', 'type'=>'desc')
      )
    ),
    array(
      'text'=>'Non Aktif',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'70px'),
        array('active'=>1, 'text'=>'Deskripsi Pelanggan', 'name'=>'description', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Kota', 'name'=>'city', 'width'=>'80px'),
        array('active'=>1, 'text'=>'Hutang', 'name'=>'payable', 'width'=>'100px', 'datatype'=>'money'),
      ),
      'filters'=>array(
        array('name'=>'isactive', 'value'=>array(
          array('operator'=>'=', 'value'=>"0")
        ))
      ),
      'sorts'=>array()
    )
  )));
  userkeystoreadd($id, 'supplierlisttemplatesidx', 0);
  userkeystoreadd($id, 'chartofaccountlisttemplates', serialize(array(
    array(
      'text'=>'Semua',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'100px'),
        array('active'=>1, 'text'=>'', 'name'=>'id', 'width'=>'20px'),
        array('active'=>1, 'text'=>'Nama Akun', 'name'=>'name', 'width'=>'300px'),
        array('active'=>1, 'text'=>'Mata Uang', 'name'=>'currencycode', 'width'=>'80px'),
        array('active'=>1, 'text'=>'Jumlah', 'name'=>'amount', 'width'=>'150px', 'datatype'=>'money'),
      ),
      'filters'=>array(),
      'sorts'=>array(
        array('name'=>'code', 'type'=>'asc')
      )
    )
  )));
  userkeystoreadd($id, 'chartofaccountlisttemplatesidx', 0);
  userkeystoreadd($id, 'currencylisttemplates', serialize(array(
    array(
      'text'=>'Semua',
      'columns'=>array(
        array('active'=>1, 'text'=>'Nama', 'name'=>'name', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Simbol', 'name'=>'code', 'width'=>'50px')
      ),
      'filters'=>array(),
      'sorts'=>array(
        array('name'=>'name', 'type'=>'asc')
      )
    )
  )));
  userkeystoreadd($id, 'currencylisttemplatesidx', 0);
  userkeystoreadd($id, 'warehouselisttemplates', serialize(array(
    array(
      'text'=>'Semua',
      'columns'=>array(
        array('active'=>1, 'text'=>'Nama Gudang', 'name'=>'name', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Alamat', 'name'=>'address', 'width'=>'200px'),
        array('active'=>1, 'text'=>'City', 'name'=>'city', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Country', 'name'=>'country', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Stok', 'name'=>'total', 'width'=>'60px', 'align'=>'right')
      ),
      'filters'=>array(),
      'sorts'=>array(
        array('name'=>'name', 'type'=>'asc')
      )
    ),
    array(
      'text'=>'Stok',
      'columns'=>array(
        array('active'=>1, 'text'=>'Nama Gudang', 'name'=>'name', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Alamat', 'name'=>'address', 'width'=>'200px'),
        array('active'=>1, 'text'=>'City', 'name'=>'city', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Country', 'name'=>'country', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Stok', 'name'=>'total', 'width'=>'60px', 'align'=>'right')
      ),
      'filters'=>array(),
      'sorts'=>array(
        array('name'=>'total', 'type'=>'desc')
      )
    )
  )));
  userkeystoreadd($id, 'warehouselisttemplatesidx', 0);
  userkeystoreadd($id, 'inventorylisttemplates', serialize(array(
    array(
      'text'=>'In Stok',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Deskripsi Barang', 'name'=>'description', 'width'=>'300px'),
        array('active'=>1, 'text'=>'Kts', 'name'=>'qty', 'width'=>'60px', 'align'=>'right'),
        array('active'=>0, 'text'=>'Min Kts', 'name'=>'minqty', 'width'=>'60px', 'align'=>'right'),
        array('active'=>1, 'text'=>'Satuan', 'name'=>'unit', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Harga Jual', 'name'=>'price', 'width'=>'100px', 'datatype'=>'money'),
        array('active'=>1, 'text'=>'Modal Rata2', 'name'=>'avgcostprice', 'width'=>'100px', 'datatype'=>'money')
      ),
      'filters'=>array(
        array('name'=>'qty', 'value'=>array(
          array('operator'=>'>', 'value'=>0)
        ))
      ),
      'sorts'=>array(
        array('name'=>'qty', 'type'=>'desc')
      )
    ),
    array(
      'text'=>'Semua',
      'columns'=>array(
        array('active'=>1, 'text'=>'Kode', 'name'=>'code', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Deskripsi Barang', 'name'=>'description', 'width'=>'300px'),
        array('active'=>1, 'text'=>'Kts', 'name'=>'qty', 'width'=>'60px', 'align'=>'right'),
        array('active'=>0, 'text'=>'Min Kts', 'name'=>'minqty', 'width'=>'60px', 'align'=>'right'),
        array('active'=>1, 'text'=>'Satuan', 'name'=>'unit', 'width'=>'100px'),
        array('active'=>1, 'text'=>'Harga Jual', 'name'=>'price', 'width'=>'100px', 'datatype'=>'money'),
        array('active'=>1, 'text'=>'Modal Rata2', 'name'=>'avgcostprice', 'width'=>'100px', 'datatype'=>'money')
      ),
      'filters'=>array(),
      'sorts'=>array(
        array('name'=>'description', 'type'=>'asc')
      )
    )
  )));
  userkeystoreadd($id, 'inventorylisttemplatesidx', 0);
  userkeystoreadd($id, 'stafflisttemplates', serialize(array(
    array(
      'text'=>'Aktif',
      'columns'=>array(
        array('active'=>1, 'text'=>'Aktif', 'name'=>'isactive', 'width'=>'40px'),
        array('active'=>1, 'text'=>'Name', 'name'=>'name', 'width'=>'200px'),
        array('active'=>1, 'text'=>'User ID', 'name'=>'userid', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Sales', 'name'=>'salesable', 'width'=>'40px'),
        array('active'=>1, 'text'=>'Dibuat', 'name'=>'createdon', 'width'=>'200px', 'datetype'=>'datetime')
      ),
      'filters'=>array(
        array('name'=>'isactive', 'value'=>array(
          array('operator'=>'=', 'value'=>"1")
        ))
      ),
      'sorts'=>array(
        array('name'=>'name', 'type'=>'asc')
      )
    ),
    array(
      'text'=>'Non Aktif',
      'columns'=>array(
        array('active'=>1, 'text'=>'Aktif', 'name'=>'isactive', 'width'=>'40px'),
        array('active'=>1, 'text'=>'Name', 'name'=>'name', 'width'=>'200px'),
        array('active'=>1, 'text'=>'User ID', 'name'=>'userid', 'width'=>'200px'),
        array('active'=>1, 'text'=>'Sales', 'name'=>'salesable', 'width'=>'40px'),
        array('active'=>1, 'text'=>'Dibuat', 'name'=>'createdon', 'width'=>'200px', 'datetype'=>'datetime')
      ),
      'filters'=>array(
        array('name'=>'isactive', 'value'=>array(
          array('operator'=>'=', 'value'=>"0")
        ))
      ),
      'sorts'=>array(
        array('name'=>'name', 'type'=>'asc')
      )
    )
  )));
  userkeystoreadd($id, 'stafflisttemplatesidx', 0);
  userkeystoreadd($id, 'inventoryadjustmentlisttemplates', serialize(array(
    array(
      'text'=>'Semua',
      'columns'=>array(
        array('active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>'100px', 'datatype'=>'date'),
        array('active'=>1, 'text'=>'Nomor', 'name'=>'code', 'width'=>'100px'),
      ),
      'filters'=>array(
      ),
      'sorts'=>array(
        array('name'=>'date', 'type'=>'asc')
      )
    )
  )));
  userkeystoreadd($id, 'inventoryadjustmentlisttemplatesidx', 0);

  userkeystoreadd($id, 'sidebarlisthidden', 1);
  userkeystoreadd($id, 'sidebarsaleshidden', 1);
  userkeystoreadd($id, 'sidebarpurchasehidden', 1);
  userkeystoreadd($id, 'sidebarothershidden', 1);
  userkeystoreadd($id, 'sidebarconfhidden', 1);

  /*$modules = array('chartofaccount', 'currency', 'customer', 'inventory', 'journalvoucher',
    'pettycash', 'purchaseinvoice', 'salesinvoice', 'salesorder', 'salesreceipt',
    'staff', 'supplier', 'warehouse', 'warehousetransfer', 'inventoryadjustment', 'log', 'datamanagement');

  $query = "DELETE FROM userprivilege WHERE `userid` = ?";
  pm($query, array($id));

  for($i = 0 ; $i < count($modules) ; $i++){
    $module = $modules[$i];

    userprivilegeadd($id, $module, 'list', $fullaccess == 1 ? 2 : 0);
    userprivilegeadd($id, $module, 'new', $fullaccess);
    userprivilegeadd($id, $module, 'modify', $fullaccess);
    userprivilegeadd($id, $module, 'delete', $fullaccess);
    userprivilegeadd($id, $module, 'print', $fullaccess);
  }*/
}

?>