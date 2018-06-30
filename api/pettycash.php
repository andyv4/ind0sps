<?php
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/staff.php';
require_once dirname(__FILE__) . '/system.php';

function pettycash_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'options', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'description', 'text'=>'Nama', 'width'=>200),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'creditaccountname', 'text'=>'Akun Kredit', 'width'=>100),
    array('active'=>1, 'name'=>'debitaccountname', 'text'=>'Akun Debit', 'width'=>100),
    array('active'=>1, 'name'=>'debitamount', 'text'=>'Jumlah Debit', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function pettycashcreditaccounts(){

  $accounts = chartofaccountlist(null, array('accounttype'=>'Asset'));
  return $accounts;

}
function pettycashdebitaccounts(){

  $accounts = chartofaccountlist(null, array('accounttype'=>'Expense'));
  return $accounts;

}
function pettycashcode(){

  $prefix = systemvarget('warehousetransferprefix', 'PE');
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM pettycash WHERE code LIKE ?";
  $rows = pmrs($query, array("%$prefix_plus_year%"));
  $blankcounter = -1;
  if(is_array($rows)){
    $numbers = array();
    for($i = 0 ; $i < count($rows) ; $i++){
      $code = $rows[$i]['code'];
      $counter = intval(str_replace($prefix_plus_year, '', $code));
      $numbers[$counter] = 1;
    }
    for($i = 1 ; $i <= 99999 ; $i++){
      if(!isset($numbers[$i])){
        $blankcounter = $i;
        break;
      }
    }
  }
  $code = "$prefix/" . date('y') . "/" . str_pad($blankcounter, 5, '0', STR_PAD_LEFT);

  return $code;

}
function pettycashdetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $pettycash = mysql_get_row('pettycash', $filters, $columns);

  if($pettycash){
    $debitaccounts = mysql_get_rows('pettycashdebitaccount', array('*'), array('pettycashid'=>$pettycash['id']));
    for($i = 0 ; $i < count($debitaccounts) ; $i++){
      $debitaccounts[$i]['debitaccountname'] = chartofaccountdetail(null, array('id'=>$debitaccounts[$i]['debitaccountid']))['name'];
    }
    $pettycash['debitaccounts'] = $debitaccounts;
    $pettycash['creditaccountname'] = chartofaccountdetail(null, array('id'=>$pettycash['creditaccountid']))['name'];
  }

  return $pettycash;

}
function pettycashlist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'status'=>'t1.status',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'creditaccountid'=>'t1.creditaccountid',
    'creditaccountname'=>'t2.name as creditaccountname',
    'total'=>'t1.total',
    'createdon'=>'t1.createdon',
    'createdbyname'=>'t3.name'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = 'WHERE t1.creditaccountid = t2.id AND t1.createdby = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM pettycash t1, chartofaccount t2, `user` t3 $wherequery $sortquery $limitquery";
  $rows = pmrs($query, $params);

  return $rows;

}
function pettycashtotal($type){

  switch($type){
    case 'today':
      $total = floatval(pmc("SELECT SUM(`total`) FROM pettycash WHERE `date` = ?", array(date('Ymd'))));
      break;
    case 'thisweek':
      $total = floatval(pmc("SELECT SUM(`total`) FROM pettycash WHERE WEEK(`date`) = ?", array(array(date('W') - 1))));
      break;
    case 'thismonth':
      $total = floatval(pmc("SELECT SUM(`total`) FROM pettycash WHERE MONTH(`date`) = ?", array(date('m'))));
      break;
    case 'thisyear':
      $total = floatval(pmc("SELECT SUM(`total`) FROM pettycash WHERE YEAR(`date`) = ?", array(date('Y'))));
      break;
  }

  return $total;

}

function pettycashentry($pettycash){

  $lock_file = __DIR__ . "/../usr/system/pettycash_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  // Extract parameters
  $code = ov('code', $pettycash);
  $description = ov('description', $pettycash);
  $date = ov('date', $pettycash);
  $creditaccountid = ov('creditaccountid', $pettycash);
  $debitaccounts = ov('debitaccounts', $pettycash, 1);
	$status = 0;
  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  // Validation
  if(empty($code)) throw new Exception(excmsg('pe01'));
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(pettycashdetail(null, array('code'=>$code))) throw new Exception(excmsg('pe02'));
  if(empty($description)) throw new Exception(excmsg('pe03'));
  if(!$creditaccountid) throw new Exception(excmsg('pe05'));
  if(!chartofaccountdetail(null, array('id'=>$creditaccountid))) throw new Exception(excmsg('pe06'));
  if(!is_array($debitaccounts) || count($debitaccounts) == 0) throw new Exception(excmsg('pe07'));
  for($i = 0 ; $i < count($debitaccounts) ; $i++){
    $debitaccount = $debitaccounts[$i];
    $debitaccountid = ov('debitaccountid', $debitaccount);
    $debitamount = ov('amount', $debitaccount);
    if(!$debitaccountid && !$debitamount) continue;
    if(empty($debitaccountid)) throw new Exception(excmsg('pe08', array($i + 1)));
    if(!chartofaccountdetail(null, array('id'=>$debitaccountid))) throw new Exception(excmsg('pe09', array($i + 1)));
    if(!floatval($debitamount)) throw new Exception(excmsg('pe10', array($i + 1)));
  }

  // Insert to pettycash table
  $query = "INSERT INTO pettycash(`status`, code, `date`, description, creditaccountid, createdon, createdby) VALUES (?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($status, $code, $date, $description, $creditaccountid, $createdon, $createdby));

  try{
    $paramstr = $params = array();
    for($i = 0 ; $i < count($debitaccounts) ; $i++){
      $debitaccount = $debitaccounts[$i];
      $debitaccountid = $debitaccount['debitaccountid'];
      $debitamount = ov('amount', $debitaccount);
      $remark = ov('remark', $debitaccount);

      if(!$debitaccountid && !$debitamount) continue;

      $paramstr[] = "(?, ?, ?, ?)";
      array_push($params, $id, $debitaccountid, $debitamount, $remark);
    }
    $query = "INSERT INTO pettycashdebitaccount(pettycashid, debitaccountid, amount, remark) VALUES " . implode(',', $paramstr);
    pm($query, $params);

    pettycashrecalculate($id);

    userlog('pettycashentry', $pettycash, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

    return array('id'=>$id);
  }
  catch(Exception $ex){
		pettycashremove(array('id'=>$id));

    fclose($fp);
    unlink($lock_file);

    throw $ex;
  }
  
}
function pettycashmodify($pettycash){

  $id = ov('id', $pettycash, 1);
  $is_recalculate = 0;
  $current_pettycash = pettycashdetail(null, array('id'=>$id));
  if(!$current_pettycash) throw new Exception("Terjadi kesalahan, silakan mencoba kembali.");

  $lock_file = __DIR__ . "/../usr/system/pettycash_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();

  if(isset($pettycash['code']) && $pettycash['code'] != $current_pettycash['code']){
    if(empty($pettycash['code'])) throw new Exception(excmsg('pe01'));
    if(pettycashdetail(null, array('code'=>$pettycash['code']))) throw new Exception(excmsg('pe02'));
    $updatedrow['code'] = $pettycash['code'];
  }

  if(isset($pettycash['description']) && $pettycash['description'] != $current_pettycash['description']){
    if(empty($pettycash['description'])) throw new Exception(excmsg('pe03'));
    $updatedrow['description'] = $pettycash['description'];
  }

  if(isset($pettycash['date']) && $pettycash['date'] != $current_pettycash['date']){
    if(!isdate($pettycash['date'])) exc('Format tanggal salah');
    $updatedrow['date'] = ov('date', $pettycash, 1, array('type'=>'date'));
    $is_recalculate = 1;
  }

  if(isset($pettycash['creditaccountid']) && $pettycash['creditaccountid'] && $pettycash['creditaccountid'] != $current_pettycash['creditaccountid']){
    if(!chartofaccountdetail(null, array('id'=>$pettycash['creditaccountid']))) throw new Exception(excmsg('pe06'));
    $updatedrow['creditaccountid'] = $pettycash['creditaccountid'];
    $is_recalculate = 1;
  }

  if(count($updatedrow) > 0) 
  	mysql_update_row('pettycash', $updatedrow, array('id'=>$id));

  if(isset($pettycash['debitaccounts'])){

    if(!is_array($pettycash['debitaccounts']) || count($pettycash['debitaccounts']) == 0) throw new Exception(excmsg('pe07'));
    for($i = 0 ; $i < count($pettycash['debitaccounts']) ; $i++){
      $debitaccount = $pettycash['debitaccounts'][$i];
      $debitaccountid = ov('debitaccountid', $debitaccount);
      $debitamount = ov('amount', $debitaccount);
      if(!$debitaccountid && !$debitamount) continue;
      if(empty($debitaccountid)) throw new Exception(excmsg('pe08', array($i + 1)));
      if(!chartofaccountdetail(null, array('id'=>$debitaccountid))) throw new Exception(excmsg('pe09', array($i + 1)));
      if(!floatval($debitamount)) throw new Exception(excmsg('pe10', array($i + 1)));
    }

    pm("DELETE FROM pettycashdebitaccount WHERE pettycashid = ?", array($id));

    $paramstr = $params = array();
    for($i = 0 ; $i < count($pettycash['debitaccounts']) ; $i++){
      $debitaccount = $pettycash['debitaccounts'][$i];
      $debitaccountid = $debitaccount['debitaccountid'];
      $debitamount = $debitaccount['amount'];
      $remark = ov('remark', $debitaccount);
      if(!$debitaccountid && !$debitamount) continue;

      $paramstr[] = "(?, ?, ?, ?)";
      array_push($params, $id, $debitaccountid, $debitamount, $remark);
    }
    $query = "INSERT INTO pettycashdebitaccount(pettycashid, debitaccountid, amount, remark) VALUES " . implode(',', $paramstr);
    pm($query, $params);

    $is_recalculate = 1;
    $updatedrow['debitaccounts'] = $pettycash['debitaccounts'];
  }

  if($is_recalculate) pettycashrecalculate($id);

  userlog('pettycashmodify', $current_pettycash, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);
  
}
function pettycashremove($filters){

  if(isset($filters['id'])){
    $id = ov('id', $filters);
    $pettycash = pettycashdetail(null, array('id'=>$id));

    if(!$pettycash) exc('Kas kecil tidak terdaftar.');

    $lock_file = __DIR__ . "/../usr/system/pettycash_remove_" . $id . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    journalvoucherremove(array('ref'=>'PE', 'refid'=>$id));
    $query = "DELETE FROM pettycash WHERE `id` = ?";
    pm($query, array($id));

    userlog('pettycashremove', $pettycash, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }

}

function pettycashrecalculate($id){

  $pettycash = pettycashdetail(null, array('id'=>$id));
  $date = $pettycash['date'];
  $code = $pettycash['code'];
  $creditaccountid = $pettycash['creditaccountid'];
  $debitaccounts = $pettycash['debitaccounts'];
  $debitaccountstotal = array();
  $total = 0;
  for($i = 0 ; $i < count($debitaccounts) ; $i++){
    $debitaccount = $debitaccounts[$i];
    $debitaccountid = $debitaccount['debitaccountid'];
    $amount = ov('amount', $debitaccount, 1);

    $total += $amount;
    if(!isset($debitaccountstotal[$debitaccountid])) $debitaccountstotal[$debitaccountid] = 0;
    $debitaccountstotal[$debitaccountid] += $amount;
  }

  $details = array(array('coaid'=>$creditaccountid, 'debitamount'=>'', 'creditamount'=>$total));
  foreach($debitaccountstotal as $debitaccountid=>$debitaccounttotal)
    $details[] = array('coaid'=>$debitaccountid, 'debitamount'=>$debitaccounttotal, 'creditamount'=>0);
  journalvoucherremove(array('ref'=>'PE', 'refid'=>$id));
  journalvoucherentry(array(
    'date'=>$date,
    'description'=>$pettycash['description'],
    'type'=>'A',
    'ref'=>'PE',
    'refid'=>$id,
    'details'=>$details
  ));

  $query = "UPDATE pettycash SET total = ? WHERE `id` = ?";
  pm($query, array($total, $id));

  global $_REQUIRE_WORKER;
  $_REQUIRE_WORKER = true;

}

function pettycash_notification_generate(){

  $notifications = [];

  /*
   * pettycash.notification.today
   */
  $total = pmc("select sum(total) from pettycash where date(`date`) = ?", [ date('Ymd') ]);
  if($total > 0){

    $time = date('H:m');

    // User with pettycash list
    $users = pmrs("select userid from userprivilege where `module` = 'pettycash' and `key` = 'list'");

    $notifications[] = [
      'key'=>'pettycash.notification.today',
      'date'=>date('Ymd'),
      'title'=>"Total biaya yang keluar dari pettycash hari ini adalah Rp. $total (s/d jam $time)",
      'description'=>'',
      'users'=>$users
    ];

  }

  return $notifications;

}

?>