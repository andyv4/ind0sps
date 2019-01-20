<?php
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/salesinvoice.php';
require_once dirname(__FILE__) . '/salesinvoicegroup.php';

function salesreceipt_uicolumns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>40, 'type'=>'html', 'align'=>'center', 'html'=>'salesreceiptlist_ispaid'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>0, 'name'=>'customerid', 'text'=>'Id Pelanggan', 'width'=>30),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>0, 'name'=>'address', 'text'=>'Alamat', 'width'=>150),
    array('active'=>0, 'name'=>'note', 'text'=>'Catatan', 'width'=>150),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'paymentaccountid', 'text'=>'ID Akun Pembayaran', 'width'=>30, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'paymentaccountname', 'text'=>'Akun Pembayaran', 'width'=>90),
    array('active'=>0, 'name'=>'paymentdate', 'text'=>'Tgl Pembayaran', 'width'=>100, 'datatype'=>'date'),
    array('active'=>0, 'name'=>'paymentamount', 'text'=>'Pembayaran', 'width'=>90, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'salesinvoicegroupcode', 'text'=>'Kode Grup Faktur', 'width'=>100),
    array('active'=>1, 'name'=>'salesinvoicegroupdate', 'text'=>'Tanggal Grup Faktur', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'salesinvoicegrouptotal', 'text'=>'Total Grup Faktur', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'beneficiarydetail', 'text'=>'Beneficiary Detail', 'width'=>100),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'datetime'),
  );
  return $columns;

}
function salesreceiptcode(){

  $prefix = systemvarget('salesreceiptprefix', 'KW');
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM salesreceipt WHERE code LIKE ?";
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
function salesreceiptdetail($columns, $filters){

  $salesreceipt = mysql_get_row('salesreceipt', $filters, $columns);

  if($salesreceipt){
    $items = array();
    $id = $salesreceipt['id'];

    // Search salesinvoice
    // Search salesinvoicegroup
    $salesinvoicegroups = pmrs("SELECT * FROM salesinvoicegroup WHERE salesreceiptid = ?", array($id));
    $salesreceipt['items'] = $salesinvoicegroups;
    
    // Calculate total amount
    $totalamount = 0;
    for($i = 0 ; $i < count($salesinvoicegroups) ; $i++){
    	$totalamount += $salesinvoicegroups[$i]['total'];
    }
    $salesreceipt['total'] = $totalamount;
    pm("UPDATE salesreceipt SET total = ? WHERE id = ?", array($totalamount, $id));

    $paymentaccount = chartofaccountdetail(null, array('id'=>$salesreceipt['paymentaccountid']));
    $salesreceipt['paymentaccountname'] = $paymentaccount ? $paymentaccount['name'] : '';
  }

  return $salesreceipt;

}
function salesreceiptlist($columns, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'code'=>'t1.code',
    'date'=>'t1.date',
    'customerdescription'=>'t1.customerdescription',
    'total'=>'t1.total',
    'invoicegroupcode'=>'t2.code as invoicegroupcode',
    'invoicegroupispaid'=>'t2.ispaid as invoicegroupispaid',
    'invoicegrouptotal'=>'t2.total as invoicegrouptotal'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = "WHERE t1.id = t2.salesreceiptid" . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM salesreceipt t1, salesinvoicegroup t2 $wherequery $sortquery $limitquery";
  $salesreceipts = pmrs($query, $params);

  return $salesreceipts;

}
function salesreceiptpaymentaccountlist(){

  return chartofaccountlist(null, null);

}
function salesreceipt_customerhint($hint){

  $query = "SELECT `id`, code, `date`, customerdescription, address, total, ispaid, paymentamount FROM salesinvoicegroup WHERE customerdescription LIKE ? AND (`isreceipt` is null OR `isreceipt` = 0 OR `isreceipt` != 1) GROUP BY code ORDER BY `date`, `id`";
  $rows = pmrs($query, array("%$hint%"));

  $results = array();
  if(is_array($rows) && count($rows) > 0){
    $rows = array_index($rows, array('customerdescription'));

    foreach($rows as $customerdescription=>$arr){
      if(count($arr) == 0) continue;

      $salesreceipt = $arr[0];

      $results[] = array(
          'customerdescription'=>$customerdescription,
          'address'=>ov('address', $salesreceipt),
          'items'=>$arr
      );
    }
  }
  return $results;

}

function salesreceiptentry($salesreceipt){

  $code = ov('code', $salesreceipt, 1);
  $date = ov('date', $salesreceipt, 1, array('type'=>'date'));
  $customerdescription = ov('customerdescription', $salesreceipt, 1);
  $address = ov('address', $salesreceipt);
  $items = ov('items', $salesreceipt, 1, array('type'=>'array'));
  $ispaid = ov('ispaid', $salesreceipt);
  $paymentamount = ov('paymentamount', $salesreceipt);
  $paymentdate = ov('paymentdate', $salesreceipt);
  $paymentaccountid = ov('paymentaccountid', $salesreceipt);
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];
  $beneficiarydetail = ov('beneficiarydetail', $salesreceipt);

  if(salesreceiptdetail(null, array('code'=>$code))) throw new Exception("Kode sudah ada.");
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(count($items) <= 0) throw new Exception('Grup faktur harus diisi.');
  $total = 0;
  $salesinvoicegroupids = array();
  for($i = 0 ; $i < count($items) ; $i++){
    $item = $items[$i];
    if(!isset($item['id'])) throw new Exception('Item required parameter id.');
    $itemid = $item['id'];
    $salesinvoicegroup = salesinvoicegroupdetail(null, array('id'=>$itemid));
    if($salesinvoicegroup['isreceipt']) throw new Exception('Grup faktur sudah ada kwitansi. (' . $code . ')');
    $total += $salesinvoicegroup['total'];
    $salesinvoicegroupids[] = $salesinvoicegroup['id'];
  }
  if(!$paymentaccountid) $paymentaccountid = chartofaccountdetail(null, array('code'=>'000.00'))['id'];

  try{

    pdo_begin_transaction();

    $query = "INSERT INTO salesreceipt(code, `date`, customerdescription, address, total, ispaid, paymentaccountid, paymentamount,
    paymentdate, beneficiarydetail, createdon, createdby, lastupdatedon)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $id = pmi($query, array($code, $date, $customerdescription, $address, $total, $ispaid, $paymentaccountid, $paymentamount,
      $paymentdate, $beneficiarydetail, $createdon, $createdby, $lastupdatedon));

    pm("UPDATE salesinvoicegroup SET isreceipt = 1, salesreceiptid = ? WHERE `id` IN (" . implode(', ', $salesinvoicegroupids) . ")", array($id));

    for($i = 0 ; $i < count($items) ; $i++){
      $item = $items[$i];
      $salesinvoicegroupid = $item['id'];

      salesinvoicegroup_ispaid($salesinvoicegroupid, $item['paymentamount'] > 0 ? 1 : 0, $paymentdate, $paymentaccountid);
    }

    userlog('salesreceiptentry', $salesreceipt, '', $_SESSION['user']['id'], $id);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

  $result = array('id'=>$id);
  return $result;

}
function salesreceiptmodify($salesreceipt){

  $id = ov('id', $salesreceipt, 1);
  $current = salesreceiptdetail(null, array('id'=>$id));

  if(!$current) exc('Kwitansi tidak terdaftar.');

  $updatedrow = array();
  if(isset($salesreceipt['date']) && strtotime($salesreceipt['date']) != strtotime($current['date'])){
    if(!isdate($salesreceipt['date'])) exc('Format tanggal salah');
    $updatedrow['date'] = date('Ymd', strtotime($salesreceipt['date']));
  }
  if(isset($salesreceipt['customerdescription']) && $salesreceipt['customerdescription'] != $current['customerdescription'])
    $updatedrow['customerdescription'] = $salesreceipt['customerdescription'];
  if(isset($salesreceipt['address']) && $salesreceipt['address'] != $current['address'])
    $updatedrow['address'] = $salesreceipt['address'];
  if(isset($salesreceipt['note']) && $salesreceipt['note'] != $current['note'])
    $updatedrow['note'] = $salesreceipt['note'];
  if(isset($salesreceipt['ispaid']) && $salesreceipt['ispaid'] != $current['ispaid'])
    $updatedrow['ispaid'] = $salesreceipt['ispaid'];
  if(isset($salesreceipt['paymentdate']) && strtotime($salesreceipt['paymentdate']) != strtotime($current['paymentdate']))
    $updatedrow['paymentdate'] = date('Ymd', strtotime($salesreceipt['paymentdate']));
  if(isset($salesreceipt['paymentaccountid']))
    $updatedrow['paymentaccountid'] = $salesreceipt['paymentaccountid'] ? $salesreceipt['paymentaccountid'] : chartofaccountdetail(null, array('code'=>'000.00'))['id'];
  if(isset($salesreceipt['beneficiarydetail']) && $current['beneficiarydetail'] != $salesreceipt['beneficiarydetail'])
    $updatedrow['beneficiarydetail'] = $salesreceipt['beneficiarydetail'];

  try{

    pdo_begin_transaction();

    if(count($updatedrow) > 0)
      mysql_update_row('salesreceipt', $updatedrow, array('id'=>$id));

    if(isset($salesreceipt['items']) && is_array($salesreceipt['items']) && count($salesreceipt['items']) > 0){

      $items = $salesreceipt['items'];
      $current = salesreceiptdetail(null, array('id'=>$id));
      $paymentdate = $current['paymentdate'];
      $paymentaccountid = $current['paymentaccountid'];

      $salesinvoicegroupids = array();
      for($i = 0 ; $i < count($items) ; $i++){
        $item = $items[$i];
        $salesinvoicegroupid = $item['id'];
        $salesinvoicegroupids[] = $salesinvoicegroupid;
        salesinvoicegroup_ispaid($salesinvoicegroupid, $item['paymentamount'] > 0 ? 1 : 0, $paymentdate, $paymentaccountid);
      }
      pm("UPDATE salesinvoicegroup SET isreceipt = 0, salesreceiptid = 0 WHERE salesreceiptid = ?", array($id));
      pm("UPDATE salesinvoicegroup SET isreceipt = 1, salesreceiptid = ? WHERE `id` IN (" . implode(', ', $salesinvoicegroupids) . ")", array($id));

      $updatedrow['items'] = $salesreceipt['items'];

    }

    userlog('salesreceiptmodify', $current, $updatedrow, $_SESSION['user']['id'], $id);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();

    throw $ex;

  }

  $result = array('id'=>$id);
  return $result;

}
function salesreceiptremove($filters){

  $salesreceipt = salesreceiptdetail(null, $filters);

  if(!$salesreceipt) exc('Kwitansi tidak terdaftar.');

  $id = $salesreceipt['id'];
  $items = $salesreceipt['items'];
  $salesinvoicegroupids = array();
  for($i = 0 ; $i < count($items) ; $i++){
    $item = $items[$i];
    $salesinvoicegroupids[] = $item['id'];
  }

  try{

    pdo_begin_transaction();

    pm("UPDATE salesinvoicegroup SET isreceipt = 0, salesreceiptid = null WHERE `id` IN (" . implode(', ', $salesinvoicegroupids) . ")");
    pm("DELETE FROM salesreceipt WHERE `id` = ?", array($id));
    userlog('salesreceiptremove', $salesreceipt, '', $_SESSION['user']['id'], $id);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

}

function salesreceiptrecalculate($id, $items){

  $salesreceipt = salesreceiptdetail(null, array('id'=>$id));

  $total = 0;
  for($i = 0 ; $i < count($items) ; $i++){
    $item = $items[$i];
    $itemtotal = $item['total'];
    $total += $itemtotal;

    $refid = salesinvoicegroupdetail(null, array('code'=>$item['code']))['id'];
    $salesinvoicegroupmodify = array(
        'id'=>$refid,
        'isreceipt'=>1,
        'salesreceiptid'=>$id,
        'ispaid'=>$salesreceipt['ispaid'],
        'paymentaccountname'=>$salesreceipt['paymentaccountname'],
        'paymentdate'=>$salesreceipt['paymentdate']
    );
    salesinvoicegroupmodify($salesinvoicegroupmodify);
  }
  pm("UPDATE salesreceipt SET total = ? WHERE `id` = ?", array($total, $id));

}

?>