<?php
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/customer.php';
require_once dirname(__FILE__) . '/warehouse.php';
require_once dirname(__FILE__) . '/salesinvoice.php';
require_once dirname(__FILE__) . '/system.php';

function salesorder_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>40, 'type'=>'html', 'html'=>'grid_ispaid'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>0, 'name'=>'customerid', 'text'=>'Id Pelanggan', 'width'=>30),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'Id Barang', 'width'=>30),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Barang', 'width'=>200),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>80, 'datatype'=>'money'),
  );
  return $columns;

}
function salesordercode(){
  $index = 0;
  $value = systemvarget('salesorderindexofyear#' . date('Y'));
  if($value) $index = intval($value);
  else systemvarset('salesorderindexofyear#' . date('Y'), $index);

  if(!($prefix = systemvarget('salesorderprefix'))) $prefix = 'SO';

  $index++;
  $code = "$prefix/" . date('y') . "/" . str_pad($index, 5, '0', STR_PAD_LEFT);
  systemvarset('salesorderindexofyear#' . date('Y'), $index);

  return $code;
}
function salesorderentry($salesorder){

  $lock_file = __DIR__ . "/../usr/system/salesorder_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $system_salesminimummargin = systemvarget('salesminimummargin');

  $code = ov('code', $salesorder, 1);
  $customerdescription = ov('customerdescription', $salesorder, 1);
  $address = ov('address', $salesorder, 1, array('notempty'=>1));
  $date = ov('date', $salesorder, 1, array('type'=>'date'));
  $salesmanname = ov('salesmanname', $salesorder, 1);
  $inventories = ov('inventories', $salesorder, 1);
  $pocode = ov('pocode', $salesorder);
  $creditterm = ov('creditterm', $salesorder);
  $note = ov('note', $salesorder);
  $discount = ov('discount', $salesorder, 0, 0);
  $discountamount = ov('discountamount', $salesorder, 0, 0);
  $taxable = ov('taxable', $salesorder, 0, 0);
  $ispaid = ov('ispaid', $salesorder);
  $paymentaccountname = ov('paymentaccountname', $salesorder);
  $paymentamount = ov('paymentamount', $salesorder);

  if(!is_array($inventories) || count($inventories) == 0) throw new Exception('Barang harus diisi.');
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventory = $inventories[$i];
    $inventorydescription = ov('inventorydescription', $inventory, 1);
    $inventory_data = inventorydetail(null, array('description'=>$inventorydescription));
    if(!$inventory) throw new Exception('Tidak dapat membuat faktur ini. Ada barang yang tidak terdaftar.');
    $qty = ov('qty', $inventory, 1, array('type'=>'decimal'));
    $unitprice = ov('unitprice', $inventory, 1, array('type'=>'money'));
    $avgcostprice = $inventory_data['avgcostprice'];
    if($unitprice < ceil($avgcostprice + ($system_salesminimummargin / 100 * $avgcostprice))) throw new Exception('Tidak dapat membuat pesanan ini. Harga jual barang dibawah margin.');

    $inventories[$i]['inventoryid'] = $inventory_data['id'];
    $inventories[$i]['inventorycode'] = $inventory_data['code'];
    $inventories[$i]['qty'] = $qty;
    $inventories[$i]['avgcostprice'] = $avgcostprice;
    $inventories[$i]['unit'] = $inventory_data['unit'];
    $inventories[$i]['unitprice'] = $unitprice;
  }
  $customer = customerdetail(null, array('description'=>$customerdescription)); if(!$customer) throw new Exception('Pelanggan tidak ada.');
  $salesman = userdetail(null, array('name'=>$salesmanname));
  if(!empty($paymentaccountname)){
    $paymentaccount = chartofaccountdetail(null, array('name'=>$paymentaccountname));
    if(!$paymentaccount) throw new Exception('Nama akun pembayaran tidak terdaftar.');
    $paymentaccountid = $paymentaccount['id'];
  }

  $salesmanid = $salesman['id'];
  $customerid = ov('id', $customer);
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];
  $isinvoiced = 0;

  $query = "INSERT INTO salesorder(isinvoiced, code, `date`, customerid, salesmanid, customerdescription, address, note, pocode, creditterm, discount, discountamount,
    taxable, ispaid, paymentaccountid, paymentamount, createdon, createdby, lastupdatedon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($isinvoiced, $code, $date, $customerid, $salesmanid, $customerdescription, $address, $note, $pocode, $creditterm, $discount, $discountamount,
    $taxable, $ispaid, $paymentaccountid, $paymentamount, $createdon, $createdby, $lastupdatedon));

  try{
    $params = $paramstr = array();
    for($i = 0 ; $i < count($inventories) ; $i++){
      $row = $inventories[$i];
      $inventorydescription = ov('inventorydescription', $row);
      $inventory = inventorydetail(null, array('description'=>$inventorydescription));
      $inventoryid = $inventory['id'];
      $inventorycode = $inventory['code'];
      $qty = ov('qty', $row);
      $unit = ov('unit', $row);
      $unitprice = ov('unitprice', $row);
      $unittotal = $qty * $unitprice;

      $paramstr[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, '', 0, $unittotal);
    }
    $query = "INSERT INTO salesorderinventory(salesorderid, inventoryid, inventorycode, inventorydescription, qty, unit, unitprice, unitdiscount, unitdiscountamount, unittotal)
    VALUES " . implode(', ', $paramstr);
    pm($query, $params);

    salesorderrecalculate($id);

    userlog('salesorderentry', $salesorder, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

    $result = array('id'=>$id);
    return $result;
  }
  catch(Exception $ex){

    pm("DELETE FROM salesorder WHERE `id` = ?", array($id));

    fclose($fp);
    unlink($lock_file);

    throw $ex;
  }
}
function salesordermodify($salesorder){

  $id = ov('id', $salesorder, 1);
  $current = salesorderdetail(null, array('id'=>$id));
  if(!$current) throw new Exception("Pesanan tidak terdaftar.");

  $lock_file = __DIR__ . "/../usr/system/salesorder_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();
  if(isset($salesorder['date']) && strtotime($salesorder['date']) != strtotime($current['date']))
    $updatedrow['date'] = date('Ymd', strtotime($salesorder['date']));
  if(isset($salesorder['customerdescription']) && $salesorder['customerdescription'] != $current['customerdescription']){
    $customer = customerdetail(null, array('description'=>$salesorder['customerdescription']));
    if(!$customer) throw new Exception('Pelanggan tidak terdaftar.');
    $updatedrow['customerid'] = $customer['id'];
    $updatedrow['customerdescription'] = $customer['description'];
  }
  if(isset($salesorder['address']) && $salesorder['address'] != $current['address'])
    $updatedrow['address'] = $salesorder['address'];
  if(isset($salesorder['pocode']) && $salesorder['pocode'] != $current['pocode'])
    $updatedrow['pocode'] = $salesorder['pocode'];
  if(isset($salesorder['creditterm']) && $salesorder['creditterm'] != $current['creditterm'])
    $updatedrow['creditterm'] = $salesorder['creditterm'];
  if(isset($salesorder['salesmanname']) && $salesorder['salesmanname'] != $current['salesmanname']){
    $salesman = userdetail(null, array('name'=>$salesorder['salesmanname']));
    if(!$salesman) throw new Exception('Salesman tidak terdaftar.');
    $updatedrow['salesmanid'] = $salesman['id'];
  }
  if(isset($salesorder['note']) && $salesorder['note'] != $current['note'])
    $updatedrow['note'] = $salesorder['note'];
  if(isset($salesorder['discount']) && $salesorder['discount'] != $current['discount'])
    $updatedrow['discount'] = $salesorder['discount'];
  if(isset($salesorder['discountamount']) && $salesorder['discountamount'] != $current['discountamount'])
    $updatedrow['discountamount'] = $salesorder['discountamount'];
  if(isset($salesorder['taxable']) && $salesorder['taxable'] != $current['taxable'])
    $updatedrow['taxable'] = $salesorder['taxable'];
  if(isset($salesorder['ispaid']) && $salesorder['ispaid'] != $current['ispaid'])
    $updatedrow['ispaid'] = $salesorder['ispaid'];
  if(isset($salesorder['paymentaccountname']) && $salesorder['paymentaccountname'] != $current['paymentaccountname']){
    $paymentaccount = chartofaccountdetail(null, array('name'=>$salesorder['paymentaccountname']));
    if(!$paymentaccount) throw new Exception('Akun pembayaran tidak terdaftar.');
    $updatedrow['paymentaccountid'] = $paymentaccount['id'];
  }
  if(isset($salesorder['paymentamount']) && $salesorder['paymentamount'] != $current['paymentamount'])
    $updatedrow['paymentamount'] = $salesorder['paymentamount'];

  if(count($updatedrow) > 0)
    mysql_update_row('salesorder', $updatedrow, array('id'=>$id));

  if(isset($salesorder['inventories'])){
    $inventories = $salesorder['inventories'];

    pm("DELETE FROM salesorderinventory WHERE salesorderid = ?", array($id));

    $params = $paramstr = array();
    for($i = 0 ; $i < count($inventories) ; $i++){
      $row = $inventories[$i];
      $inventorydescription = ov('inventorydescription', $row);
      $inventory = inventorydetail(null, array('description'=>$inventorydescription));
      $inventoryid = $inventory['id'];
      $inventorycode = $inventory['code'];
      $qty = ov('qty', $row);
      $unit = ov('unit', $row);
      $unitprice = ov('unitprice', $row);
      $unittotal = $qty * $unitprice;

      $paramstr[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, '', 0, $unittotal);
    }
    $query = "INSERT INTO salesorderinventory(salesorderid, inventoryid, inventorycode, inventorydescription, qty, unit, unitprice, unitdiscount, unitdiscountamount, unittotal)
    VALUES " . implode(', ', $paramstr);
    pm($query, $params);

    $updatedrow['inventories'] = $salesorder['inventories'];
  }

  salesorderrecalculate($id);

  userlog('salesordermodify', $salesorder, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}
function salesorderremove($filters){

  if(isset($filters['id'])){
    $id = ov('id', $filters);
    $salesorder = salesorderdetail(null, array('id'=>$id));

    if(!$salesorder) exc('Pesanan tidak terdaftar.');

    $lock_file = __DIR__ . "/../usr/system/salesorder_remove_" . $id . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    journalvoucherremove(array('ref'=>'SO', 'refid'=>$id));

    $query = "DELETE FROM salesorder WHERE `id` = ?";
    pm($query, array($id));

    userlog('salesorderremove', $salesorder, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }

}

function salesorderdetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $salesorder = mysql_get_row('salesorder', $filters, $columns);

  if($salesorder){
    $inventories = mysql_get_rows('salesorderinventory', array('*'), array('salesorderid'=>$salesorder['id']));
    $salesorder['inventories'] = $inventories;
    $salesorder['salesmanname'] = userdetail(null, array('id'=>$salesorder['salesmanid']))['name'];
    $salesorder['warehousename'] = warehousedetail(null, array('id'=>$salesorder['warehouseid']))['name'];

    $paymentaccount = chartofaccountdetail(null, array('id'=>$salesorder['paymentaccountid']));
    if($paymentaccount) $salesorder['paymentaccountname'] = $paymentaccount['name'];
  }

  return $salesorder;
}
function salesorderlist($columns, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'status'=>'t1.status',
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerid'=>'t1.customerid',
    'customerdescription'=>'t1.customerdescription',
    'subtotal'=>'t1.subtotal',
    'total'=>'t1.total',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'costprice'=>'t2.costprice',
    'totalcostprice'=>'t2.totalcostprice',
    'margin'=>'t2.margin',
    'unitdiscount'=>'t2.unitdiscount',
    'unitdiscountamount'=>'t2.unitdiscountamount',
    'unittotal'=>'t2.unittotal',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.createdby', 't1.salesmanid'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesorderid' . str_replace('WHERE', 'AND', $wherequery);
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM salesorder t1, salesorderinventory t2 $wherequery $sortquery $limitquery";
  $salesorders = pmrs($query, $params);

  if(is_array($salesorders) && count($salesorders) > 0){
    $salesmans = userlist(null, null);
    $salesmans = array_index($salesmans, array('id'), 1);
    for($i = 0 ; $i < count($salesorders) ; $i++)
      $salesorders[$i]['salesmanname'] = $salesmans[$salesorders[$i]['salesmanid']]['name'];
  }

  return $salesorders;
}

function salesorderrecalculate($id){
  $salesorder = salesorderdetail(null, array('id'=>$id));
  $date = ov('date', $salesorder);
  $code = ov('code', $salesorder);

  // Subtotal, discountamount, taxamount, total
  $inventories = $salesorder['inventories'];
  $discountamount = ov('discountamount', $salesorder);
  $subtotal = 0;
  for($i = 0 ; $i < count($inventories) ; $i++){
    $row = $inventories[$i];
    $inventorydescription = ov('inventorydescription', $row);
    $inventory = inventorydetail(null, array('description'=>$inventorydescription));
    $inventoryid = $inventory['id'];
    $inventorycode = $inventory['code'];
    $qty = ov('qty', $row);
    $unitprice = ov('unitprice', $row);
    $unittotal = $qty * $unitprice;
    $subtotal += $unittotal;
  }
  $total = $subtotal;
  $discount = $salesorder['discount'];
  if(intval($discount) && $discount > 0)
    $discountamount = $discount / 100 * $total;
  if(!floatval($discountamount)) $discountamount = 0;
  $total -= $discountamount;
  $taxable = $salesorder['taxable'];
  $taxamount = $taxable ? $total * 0.1 : 0;
  $total += $taxamount;
  $query = "UPDATE salesorder SET subtotal = ?, discountamount = ?, taxamount = ?, total = ? WHERE `id` = ?";
  pm($query, array($subtotal, $discountamount, $taxamount, $total, $id));

  // Ispaid
  $ispaid = ov('ispaid', $salesorder);
  $paymentaccountid = ov('paymentaccountid', $salesorder);
  $paymentamount = ov('paymentamount', $salesorder);
  journalvoucherremove(array('ref'=>'SO', 'refid'=>$id));
  if($ispaid && $paymentaccountid){
    $creditaccountid = 20;
    $debitaccountid = $paymentaccountid;
    journalvoucherentry(array(
      'ref'=>'SO',
      'refid'=>$id,
      'type'=>'A',
      'date'=>$date,
      'description'=>$code,
      'details'=>array(
        array('coaid'=>$debitaccountid, 'debitamount'=>$paymentamount, 'creditamount'=>0),
        array('coaid'=>$creditaccountid, 'debitamount'=>0, 'creditamount'=>$paymentamount)
      )
    ));
  }

}
function salesorder_salesinvoicenew($id){
  $salesorder = salesorderdetail(null, array('id'=>$id));

  $salesinvoice = array(
    'date'=>date('Ymd'),
    'customerdescription'=>$salesorder['customerdescription'],
    'address'=>$salesorder['address'],
    'pocode'=>$salesorder['pocode'],
    'creditterm'=>$salesorder['creditterm'],
    'warehousename'=>$salesorder['warehousename'],
    'salesmanname'=>$salesorder['salesmanname'],
    'inventories'=>$salesorder['inventories'],
    'note'=>$salesorder['note'],
    'subtotal'=>$salesorder['subtotal'],
    'discount'=>$salesorder['discount'],
    'discountamount'=>$salesorder['discountamount'],
    'taxable'=>$salesorder['taxable'],
    'taxamount'=>$salesorder['taxamount'],
    'total'=>$salesorder['total'],
    'ispaid'=>$salesorder['ispaid'],
    'paymentaccountname'=>$salesorder['paymentaccountname'],
    'paymentamount'=>$salesorder['paymentamount'],
    'salesorderid'=>$id
  );
  return $salesinvoice;
}
function salesorderpaymentaccounts(){

  return chartofaccountlist(null, array('accounttype'=>'Asset'));

}

?>