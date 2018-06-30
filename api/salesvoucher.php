<?php

function salesvouchercode(){
  $index = 0;
  $value = pmc("SELECT `value` FROM systemvar WHERE `key` = ?", array('salesvoucherindexofyear#' . date('Y')));
  if($value) $index = intval($value);
  else pm("INSERT INTO systemvar(`key`, `value`, lastupdatedon) VALUES (?, ?, ?)", array('salesvoucherindexofyear#' . date('Y'), $index, date('YmdHis')));

  $index++;
  $code = "SV/" . date('y') . "/" . str_pad($index, 5, '0', STR_PAD_LEFT);
  pm("UPDATE systemvar SET `value` = ?, lastupdatedon = ? WHERE `key` = ?", array($index, date('YmdHis'), 'salesvoucherindexofyear#' . date('Y')));

  return $code;
}
function salesvoucherdetail($columns, $filters){
  if($columns == null) $columns = array('*');
  $salesvoucher = mysql_get_row('salesvoucher', $filters, $columns);

  if($salesvoucher){
    $salesvoucher['receipts'] = pmrs("SELECT * FROM salesvoucherreceipt WHERE salesvoucherid = ?", array($salesvoucher['id']));
  }

  return $salesvoucher;
}
function salesvoucherlist($columns, $filters, $sorts = null){
  $query = "SELECT * FROM salesvoucher";
  $salesvouchers = pmrs($query);
  return $salesvouchers;
}

function salesvoucherentry($obj){
  $logid = logstart('salesvoucherentry', $obj);

  $code = ov('code', $obj, 1);
  $salesvoucher = salesvoucherdetail(null, array('code'=>$code));
  if($salesvoucher) throw new Exception('Kode kwintansi sudah ada.');
  $date = ov('date', $obj, 1, array('type'=>'date'));
  $customerdescription = ov('customerdescription', $obj, 1);
  $customer = customerdetail(null, array('description'=>$customerdescription));
  if(!$customer) throw new Exception('Pelanggan tidak terdaftar.');
  $address = ov('address', $obj, 1, array('notempty'=>1));
  $amount = 0;
  $receipts = ov('receipts', $obj, 1);
  if(!is_array($receipts) || count($receipts) == 0) throw new Exception('Tidak ada pelunasan.');
  for($i = 0 ; $i < count($receipts) ; $i++){
    $receipt = $receipts[$i];
    $receiptcode = ov('code', $receipt, 1);
    $salesreceipt = salesreceiptdetail(null, array('code'=>$receiptcode));
    if(!$salesreceipt) throw new Exception('Terjadi kesalahan, tidak dapat menyimpan kwitansi.');
    $salesreceipt_amount = $salesreceipt['amount'];
    $amount += $salesreceipt_amount;
  }

  $status = 0;
  $customerid = $customer['id'];
  $note = ov('note', $obj, 0, '');
  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  $query = "INSERT INTO salesvoucher(`status`, code, `date`, customerid, customerdescription, address, amount, note, createdon, createdby)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($status, $code, $date, $customerid, $customerdescription, $address, $amount, $note, $createdon, $createdby));

  $params = $paramstr = array();
  $salesreceiptids = array(); // For updating salesvoucherrefid
  for($i = 0 ; $i < count($receipts) ; $i++){
    $receipt = $receipts[$i];
    $salesreceiptcode = $receipt['code'];
    $salesreceipt = salesreceiptdetail(null, array('code'=>$salesreceiptcode));
    $salesreceiptid = $salesreceipt['id'];
    $salesreceiptcode = $salesreceipt['code'];
    $salesreceiptdate = $salesreceipt['date'];
    $salesreceiptamount = $salesreceipt['amount'];

    $paramstr[] = "(?, ?, ?, ?, ?)";
    array_push($params, $id, $salesreceiptid, $salesreceiptcode, $salesreceiptdate, $salesreceiptamount);
    $salesreceiptids[] = $salesreceiptid;
  }
  pm("INSERT INTO salesvoucherreceipt (salesvoucherid, salesreceiptid, code, `date`, amount) VALUES " . implode(', ', $paramstr), $params);

  pm("UPDATE salesreceipt SET salesvoucherrefid = ? WHERE `id` IN (" . implode(', ', $salesreceiptids) . ")", array($id));

  $result = array('id'=>$id);
  logend($logid, $result);
  return $result;
}
function salesvoucherremove($filters){
  if(isset($filters['id'])){
    $id = $filters['id'];
    $salesvoucher = salesvoucherdetail(null, array('id'=>$id));
    if(!$salesvoucher) throw new Exception('Kwitansi tidak ada.');

    // Update salesreceipt's salesvoucherrefid field
    pm("UPDATE salesreceipt SET salesvoucherrefid = 0 WHERE salesvoucherrefid = ?", array($id));

    // Remove salesvoucher
    pm("DELETE FROM salesvoucher WHERE `id` = ?", array($id));
  }
}

?>