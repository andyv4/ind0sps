<?php

require_once dirname(__FILE__) . '/salesinvoice.php';

/*
create table salesreconcile(`id` int(10) auto_increment, salesinvoiceid int(10), isreconciled int(1), `remark` text, createdon datetime, lastupdatedon datetime, createdby int(10), lastupdatedby int(10), primary key(`id`), foreign key (salesinvoiceid) references salesinvoice(`id`) on delete cascade on update cascade)engine=InnoDB, default charset=utf8;
 */

function salesreconcile_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>30, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>50, 'type'=>'html', 'html'=>'grid_ispaid', 'align'=>'center'),
    array('active'=>1, 'name'=>'isreconciled', 'text'=>'Rekonsil', 'width'=>50, 'type'=>'html', 'html'=>'grid_isreconciled', 'align'=>'center'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>70, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>90),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>240),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>70, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'paymentaccountname', 'text'=>'Akun Pembayaran', 'width'=>120),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>70, 'datatype'=>'datetime'),
  );
  return $columns;

}
function salesreconciledetail($columns, $filters){

  $id = ov('id', $filters);
  $obj = salesinvoicedetail(array('id', 'date', 'code', 'customerdescription', 'paymentaccountname', 'paymentamount'), array('id'=>$id));

  $obj2 = pmr("SELECT * FROM salesreconcile WHERE salesinvoiceid = ?", array($id));
  if(is_array($obj2)){
    $obj['isreconciled'] = $obj2['isreconciled'];
    $obj['remark'] = $obj2['remark'];
    $obj['createdon'] = $obj2['createdon'];
  }

  return $obj;

}
function salesreconcilelist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
      'ispaid'=>'t1.ispaid',
      'status'=>'t1.status',
      'isprint'=>'t1.isprint',
      'isreconciled'=>'t1.isreconciled',
      'date'=>'t1.date',
      'code'=>'t1.code',
      'customerid'=>'t1.customerid',
      'customerdescription'=>'t1.customerdescription',
      'subtotal'=>'t1.subtotal',
      'total'=>'t1.total',
      'paymentamount'=>'t1.paymentamount',
      'createdon'=>'t1.createdon',
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases);
  $wherequery = 'WHERE t1.paymentaccountid = t2.id AND t1.ispaid = 1' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limitoffset);

  $query = "SELECT 'salesreconcile' as `type`, t1.id, $columnquery FROM salesinvoice t1, chartofaccount t2 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function salesreconcileentry($obj){

  $lock_file = __DIR__ . "/../usr/system/salesreconcile_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $id = ov('id', $obj);
  $isreconciled = ov('isreconciled', $obj, 0, 0);
  $remark = ov('remark', $obj);

  // Check if salesinvoiceid exists
  if(intval(pmc("SELECT COUNT(*) FROM salesreconcile WHERE `salesinvoiceid` = ?", array($id))) > 0)
    throw new Exception('Tidak dapat membuat data ini, data sudah ada.');

  // Insert to table
  pm("INSERT INTO salesreconcile (salesinvoiceid, isreconciled, remark, createdon, createdby, lastupdatedon, lastupdatedby) VALUES (?, ?, ?, ?, ?, ?, ?)",
    array($id, $isreconciled, $remark, date('YmdHis'), $_SESSION['user']['id'], date('YmdHis'), $_SESSION['user']['id']));

  // Update salesinvoice isreconciled
  //salesinvoicemodify(array('id'=>$id, 'isreconciled'=>$isreconciled));
  pm("UPDATE salesinvoice SET isreconciled = ? WHERE `id` = ?", array($isreconciled, $id));

  userlog('salesreconcileentry', $obj, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}
function salesreconcilemodify($obj){

  $id = ov('id', $obj);

  // Check if salesinvoiceid exists
  if(intval(pmc("SELECT COUNT(*) FROM salesreconcile WHERE `salesinvoiceid` = ?", array($id))) < 1){

    salesreconcileentry($obj);

  }
  else{

    $lock_file = __DIR__ . "/../usr/system/salesreconcile_modify_" . $id . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

    // Gather updated cols
    $current = pmr("SELECT * FROM salesreconcile WHERE salesinvoiceid = ?", array($id));
    $updatedcol = array();
    if(isset($obj['isreconciled']) && intval($obj['isreconciled']) != $current['isreconciled'])
      $updatedcol['isreconciled'] = $obj['isreconciled'];
    if(isset($obj['remark']) && intval($obj['remark']) != $current['remark'])
      $updatedcol['remark'] = $obj['remark'];

    // Update changed col to table
    if(count($updatedcol) > 0){
      $updatedcol['lastupdatedon'] = date('YmdHis');
      $updatedcol['lastupdatedby'] = $_SESSION['user']['id'];
      mysql_update_row('salesreconcile', $updatedcol, array('salesinvoiceid'=>$id));
    }

    // Update salesinvoice isreconciled
    //if(isset($updatedcol['isreconciled']))
    //  salesinvoicemodify(array('id'=>$id, 'isreconciled'=>$updatedcol['isreconciled']));
    pm("UPDATE salesinvoice SET isreconciled = ? WHERE `id` = ?", array($obj['isreconciled'], $id));

    userlog('salesreconcilemodify', $current, $updatedcol, $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }

}


?>