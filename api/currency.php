<?php
require_once dirname(__FILE__) . '/log.php';

function currency_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>50),
    array('active'=>1, 'name'=>'name', 'text'=>'Nama Mata Uang', 'width'=>200),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'datetime')
  );
  return $columns;

}
function currencydetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $currency = mysql_get_row('currency', $filters, $columns);
  return $currency;

}
function currencylist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $currencies = pmrs("SELECT * FROM currency");
  return $currencies;

}

function currencyentry($currency){

  $name = ov('name', $currency, 1, null, 'string', array('notempty'=>1));
  $code = ov('code', $currency);
  $isdefault = ov('isdefault', $currency, 0, 0);
  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  if(empty($code)) throw new Exception(excmsg('cr01'));
  if(empty($name)) throw new Exception(excmsg('cr02'));
  if(currencydetail(null, array('code'=>$code))) throw new Exception(excmsg('cr03'));
  if(currencydetail(null, array('name'=>$name))) throw new Exception(excmsg('cr04'));

  $lock_file = __DIR__ . "/../usr/system/currency_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $query = "INSERT INTO currency(`name`, code, isdefault, createdon, createdby) VALUES (?, ?, ?, ?, ?)";
  $id = pmi($query, array($name, $code, $isdefault, $createdon, $createdby));

  if(ov('isdefault', $currency)) currency_default_set($id);

  userlog('currencyentry', $currency, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;

}
function currencymodify($currency){

  $id = ov('id', $currency, 1);
  $current_currency = currencydetail(null, array('id'=>$id));

  if(!$current_currency) throw new Exception('Mata uang tidak terdaftar');

  $lock_file = __DIR__ . "/../usr/system/currency_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();

  if(isset($currency['code']) && $current_currency['code'] != $currency['code']){
    if(empty($currency['code'])) throw new Exception(excmsg('cr01'));
    if(currencydetail(null, array('code'=>$currency['code']))) throw new Exception(excmsg('cr03'));
    $updatedrow['code'] = $currency['code'];
  }

  if(isset($currency['name']) && $current_currency['name'] != $currency['name']){
    if(empty($currency['name'])) throw new Exception(excmsg('cr02'));
    if(currencydetail(null, array('name'=>$currency['name']))) throw new Exception(excmsg('cr04'));
    $updatedrow['name'] = $currency['name'];
  }

  if(count($updatedrow) > 0)
    mysql_update_row('currency', $updatedrow, array('id'=>$id));

  if(ov('isdefault', $currency)) currency_default_set($id);

  userlog('currencymodify', $current_currency, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;

}
function currencyremove($filters){

  if(isset($filters['id'])){
    $id = ov('id', $filters);
    $current_currency = currencydetail(null, array('id'=>$id));

    // Check if currency has been used
    $exists = pmc("SELECT COUNT(*) FROM chartofaccount WHERE currencyid = ?", array($id));
    if(!$exists) $exists = pmc("SELECT COUNT(*) FROM purchaseorder WHERE currencyid = ?", array($id));
    if(!$exists) $exists = pmc("SELECT COUNT(*) FROM purchaseinvoice WHERE currencyid = ?", array($id));

    if($exists) throw new Exception('Tidak dapat menghapus mata uang ini, sudah dipakai dalam transaksi.');

    $lock_file = __DIR__ . "/../usr/system/currency_remove_" . $id . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    $query = "DELETE FROM currency WHERE `id` = ?";
    pm($query, array($id));

    userlog('currencyremove', $current_currency, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }

}
function currency_default_set($id){

  pm("UPDATE currency SET isdefault = 0");
  pm("UPDATE currency SET isdefault = 1 WHERE `id` = ?", array($id));

}

?>