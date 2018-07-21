<?php
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/currency.php';
require_once dirname(__FILE__) . '/supplier.php';
require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/purchaseinvoice.php';
require_once dirname(__FILE__) . '/inventory.php';
require_once dirname(__FILE__) . '/log.php';

function purchaseorder_uicolumns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'purchaseorderlist_options'),
    array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>30, 'type'=>'html', 'html'=>'purchaseorderlist_ispaid'),
    array('active'=>1, 'name'=>'isinvoiced', 'text'=>'Faktur', 'width'=>40, 'type'=>'html', 'html'=>'purchaseorderlist_isinvoiced'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>90),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>0, 'name'=>'supplierid', 'text'=>'ID Supplier', 'width'=>30),
    array('active'=>1, 'name'=>'supplierdescription', 'text'=>'Nama Supplier', 'width'=>200, 'type'=>'html', 'html'=>'purchaseorderlist_supplierdescription'),
    array('active'=>0, 'name'=>'address', 'text'=>'Alamat', 'width'=>100),
    array('active'=>0, 'name'=>'currencyid', 'text'=>'ID Mata Uang', 'width'=>30),
    array('active'=>1, 'name'=>'currencycode', 'text'=>'Kode Mata Uang', 'width'=>40),
    array('active'=>0, 'name'=>'currencyname', 'text'=>'Nama Mata Uang', 'width'=>60),
    array('active'=>0, 'name'=>'currencyrate', 'text'=>'Kurs', 'width'=>60, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'subtotal', 'text'=>'Subtotal', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'discount', 'text'=>'Diskon%', 'width'=>60, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'discountamount', 'text'=>'Diskon', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'taxable', 'text'=>'PPn', 'width'=>30, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'taxamount', 'text'=>'Jumlah PPn', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'freightcharge', 'text'=>'Freight Charge', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'handlingfeeaccountid', 'text'=>'ID Akun Handling Fee', 'width'=>30, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'handlingfeeaccountname', 'text'=>'Akun Handling Fee', 'width'=>100),
    array('active'=>0, 'name'=>'handlingfeeamount', 'text'=>'Jumlah Handling Fee', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'paymentaccountid', 'text'=>'ID Akun Pembayaran', 'width'=>30, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'paymentaccountname', 'text'=>'Akun Pembayaran', 'width'=>100),
    array('active'=>0, 'name'=>'paymentdate', 'text'=>'Tgl Pembayaran', 'width'=>100, 'datatype'=>'date'),
    array('active'=>0, 'name'=>'paymentamount', 'text'=>'Jumlah Pembayaran', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'note', 'text'=>'Catatan', 'width'=>100),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'ID Barang', 'width'=>30),
    array('active'=>1, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>60),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Nama Barang', 'width'=>150, 'type'=>'html', 'html'=>'purchaseorderlist_inventorydescription'),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unit', 'text'=>'Satuan', 'width'=>60),
    array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>60, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'unitdiscount', 'text'=>'Diskon Barang%', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitdiscountamount', 'text'=>'Diskon Barang', 'width'=>60, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'unittotal', 'text'=>'Jumlah Barang', 'width'=>60, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'unithandlingfee', 'text'=>'Jumlah Handling Fee', 'width'=>60, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function purchaseordercode(){

  $prefix = systemvarget('purchaseorderprefix', 'PO');
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM purchaseorder WHERE code LIKE ?";
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
function purchaseorderdetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $purchaseorder = mysql_get_row('purchaseorder', $filters, $columns);

  if($purchaseorder){
    $inventories = mysql_get_rows('purchaseorderinventory', array('*'), array('purchaseorderid'=>$purchaseorder['id']));
    $handlingfeepaymentaccount = chartofaccountdetail(null, array('id'=>$purchaseorder['handlingfeeaccountid']));

    $purchaseorder['inventories'] = $inventories;
    $purchaseorder['currencyname'] = currencydetail(null, array('id'=>$purchaseorder['currencyid']))['name'];
    $purchaseorder['paymentaccountname'] = chartofaccountdetail(null, array('id'=>$purchaseorder['paymentaccountid']))['name'];
    $purchaseorder['handlingfeeaccountname'] = isset($handlingfeepaymentaccount['name']) ? $handlingfeepaymentaccount['name'] : '';
  }

  return $purchaseorder;
}
function purchaseorderlist($columns, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'date'=>'t1.date',
    'code'=>'t1.code',
    'ispaid'=>'t1.ispaid',
    'isinvoiced'=>'t1.isinvoiced',
    'supplierdescription'=>'t1.supplierdescription',
    'total'=>'t1.total',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'total'=>'t1.total',
    'createdon'=>'t1.createdon',
    'createdby'=>'t1.createdby'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.paymentaccountid', 't1.currencyid', 't1.handlingfeeaccountid', 't1.createdby'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $columnaliases);
  $wherequery = "WHERE t1.id = t2.purchaseorderid" . str_replace('WHERE', 'AND', $wherequery);
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM purchaseorder t1, purchaseorderinventory t2 $wherequery $sortquery $limitquery";
  $purchaseorders = pmrs($query, $params);

  if(is_array($purchaseorders)){
    $currencies = currencylist(null, null);
    $currencies = array_index($currencies, array('id'), 1);
    $users = userlist(null, null);
    $users = array_index($users, array('id'), 1);
    $chartofaccounts = chartofaccountlist(null, null);
    $chartofaccounts = array_index($chartofaccounts, array('id'), 1);

    for($i = 0 ; $i < count($purchaseorders) ; $i++){
      $purchaseorders[$i]['paymentaccountname'] = $purchaseorders[$i]['paymentamount'] > 0 ? $chartofaccounts[$purchaseorders[$i]['paymentaccountid']]['name'] : '';
      $purchaseorders[$i]['handlingfeepaymentaccountname'] = $purchaseorders[$i]['handlingfeepaymentamount'] > 0 ? $chartofaccounts[$purchaseorders[$i]['handlingfeeaccountid']]['name'] : '';
      $purchaseorders[$i]['currencyname'] = $currencies[$purchaseorders[$i]['currencyid']]['code'];
      $purchaseorders[$i]['createdby'] = $users[$purchaseorders[$i]['createdby']]['name'];
    }
  }

  return $purchaseorders;

}
function purchaseorderremovable($params){

  $removable = false;
  $purchaseorder = purchaseorderdetail(null, $params);
  if($purchaseorder){

    $removable = true;

    // Not removable if purchase invoice already exists
    $purchaseinvoice = purchaseinvoicedetail(null, array('id'=>ov('purchaseorderid', $purchaseorder['id'])));
    if($purchaseinvoice) $removable = false;


  }
  return $removable;

}
function purchaseorderlistforinvoice($hint, $supplierdescription){

  $query = "SELECT * FROM purchaseorder WHERE supplierdescription LIKE ? AND invoicerefid = null";
  $rows = pmrs($query, array("%$hint%"));
  return $rows;

}

function purchaseorderentry($purchaseorder){

  $lock_file = __DIR__ . '/../usr/system/purchaseorder_entry.lock';
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $code = ov('code', $purchaseorder, 1);
  $supplierdescription = ov('supplierdescription', $purchaseorder, 1);
  $supplier = supplierdetail(null, array('description'=>$supplierdescription));
  $supplierid = $supplier['id'];
  $date = ov('date', $purchaseorder, 1, array('type'=>'date'));
  $address = ov('address', $purchaseorder);
  $currencyid = ov('currencyid', $purchaseorder, 0, 1);
  $currencyrate = floatval(ov('currencyrate', $purchaseorder, 1));
  $note = ov('note', $purchaseorder);
  $discount = ov('discount', $purchaseorder, 0, 0);
  $discountamount = ov('discountamount', $purchaseorder, 0, 0);
  $taxable = ov('taxable', $purchaseorder, 0, 0);
  $inventories = ov('inventories', $purchaseorder, 1);
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];
  $isinvoiced = 0;
  $ispaid = ov('ispaid', $purchaseorder);
  $paymentdate = ov('paymentdate', $purchaseorder);
  $paymentamount = ov('paymentamount', $purchaseorder, 0, 0);
  $paymentaccountid = ov('paymentaccountid', $purchaseorder, 0, 2);
  $freightcharge = ov('freightcharge', $purchaseorder, 0, 0);
  $handlingfeeamount = ov('handlingfeeamount', $purchaseorder, 0, 0);
  $handlingfeeaccountname = ov('handlingfeeaccountname', $purchaseorder);
  $handlingfeeaccountid = 40;
  if(!empty($handlingfeeaccountname)){
    $handlingfeeaccount = chartofaccountdetail(null, array('name'=>$handlingfeeaccountname));
    $handlingfeeaccountid = isset($handlingfeeaccount['id']) ? $handlingfeeaccount['id'] : $handlingfeeaccountid;
  }
  $handlingfeedate = ov('handlingfeedate', $purchaseorder);
  $handlingfeevolume = ov('handlingfeevolume', $purchaseorder);
  $handlingfeepaymentamount = ov('handlingfeepaymentamount', $purchaseorder, 0, 0);
  $refno = ov('refno', $purchaseorder);
  $eta = ov('eta', $purchaseorder);
  $term = ov('term', $purchaseorder);

  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(!is_array($inventories) || count($inventories) == 0) throw new Exception('Barang harus diisi.');
  if($ispaid){
    if(!isdate($paymentdate)) exc('Tanggal pelunasan harus diisi.');
    if(!chartofaccount_id_exists($paymentaccountid)) exc("Akun pelunasan harus diisi.");
  }

  $query = "INSERT INTO purchaseorder(isinvoiced, code, `date`, supplierid, supplierdescription, currencyid, currencyrate, address, note, ispaid, paymentaccountid, paymentamount, paymentdate,
    discount, discountamount, taxable, createdon, createdby, lastupdatedon, freightcharge, handlingfeevolume, handlingfeeamount, handlingfeeaccountid, handlingfeedate, handlingfeepaymentamount,
    refno, eta, term) VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($isinvoiced, $code, $date, $supplierid, $supplierdescription, $currencyid, $currencyrate, $address, $note, $ispaid, $paymentaccountid, $paymentamount, $paymentdate,
      $discount, $discountamount, $taxable, $createdon, $createdby, $lastupdatedon, $freightcharge, $handlingfeevolume, $handlingfeeamount, $handlingfeeaccountid, $handlingfeedate, $handlingfeepaymentamount,
      $refno, $eta, $term));

  try{
    $params = array();
    $queries = array();
    for($i = 0 ; $i < count($inventories) ; $i++){
      $row = $inventories[$i];
      $inventorycode = ov('inventorycode', $row);
      if(empty($inventorycode)) continue;
      $inventory = inventorydetail(null, array('code'=>$inventorycode));
      if(!$inventory) throw new Exception('Barang tidak terdaftar. (' . $inventorycode . ')');
      $inventoryid = $inventory['id'];
      $inventorycode = $inventory['code'];
      $inventorydescription = $inventory['description'];
      $qty = ov('qty', $row);
      $unit = ov('unit', $row);
      $unitprice = ov('unitprice', $row);
      $unittotal = $qty * $unitprice;
      $unitdiscount = ov('unitdiscount', $row);
      $unitdiscountamount = intval($unitdiscount) ? $unitdiscount / 100 * $unittotal : 0;
      $unittotal = $unittotal - $unitdiscountamount;
      $unithandlingfee = ov('unithandlingfee', $row);

      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, $unitdiscount,
          $unitdiscountamount, $unittotal, $unithandlingfee);
      $queries[] = "(?, ?, ?, ?, ?, ?, ?, ?, ? ,?, ?)";
    }
    if(count($queries) > 0){
      $query = "INSERT INTO purchaseorderinventory(purchaseorderid, inventoryid, inventorycode, inventorydescription, qty, unit,
      unitprice, unitdiscount, unitdiscountamount, unittotal, unithandlingfee) VALUES " . implode(', ', $queries);
      pm($query, $params);
    }

    purchaseordercalculate($id);
    inventory_purchaseorderqty();

    userlog('purchaseorderentry', $purchaseorder, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

    return array('id'=>$id);
  }
  catch(Exception $ex){

    purchaseorderremove(array('id'=>$id));

    fclose($fp);
    unlink($lock_file);

    throw $ex;

  }
  
}
function purchaseordermodify($purchaseorder){

  $id = ov('id', $purchaseorder, 1);
  $current_purchaseorder = purchaseorderdetail(null, array('id'=>$id));
  if(!$current_purchaseorder) throw new Exception("Invoice tidak ada.");

  $lock_file = __DIR__ . "/../usr/system/purchaseorder_modify_$id.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrows = array();

  if(isset($purchaseorder['supplierdescription'])){
    $updatedrows['supplierid'] = supplierdetail(null, array('description'=>$purchaseorder['supplierdescription']))['id'];
    $updatedrows['supplierdescription'] = $purchaseorder['supplierdescription'];
  }
  if(isset($purchaseorder['date']) && $purchaseorder['date'] != $current_purchaseorder['date']){
    if(!isdate($purchaseorder['date'])) exc('Format tanggal salah');
    $updatedrows['date'] = ov('date', $purchaseorder, 1, array('type'=>'date'));
  }
  if(isset($purchaseorder['address']) && $purchaseorder['address'] != $current_purchaseorder['address']){
    $updatedrows['address'] = $purchaseorder['address'];
  }
  if(isset($purchaseorder['currencyid']) && $purchaseorder['currencyid'] != $current_purchaseorder['currencyid']){
    $updatedrows['currencyid'] = $purchaseorder['currencyid'];
  }
  if(isset($purchaseorder['currencyrate']) && $purchaseorder['currencyrate'] != $current_purchaseorder['currencyrate']){
    $updatedrows['currencyrate'] = $purchaseorder['currencyrate'];
  }
  if(isset($purchaseorder['discount']) && $purchaseorder['discount'] != $current_purchaseorder['discount']){
    $updatedrows['discount'] = $purchaseorder['discount'];
  }
  if(isset($purchaseorder['discountamount']) && $purchaseorder['discountamount'] != $current_purchaseorder['discountamount']){
    $updatedrows['discountamount'] = $purchaseorder['discountamount'];
  }
  if(isset($purchaseorder['eta']) && $purchaseorder['eta'] != $current_purchaseorder['eta']){
    $updatedrows['eta'] = $purchaseorder['eta'];
  }
  if(isset($purchaseorder['refno']) && $purchaseorder['refno'] != $current_purchaseorder['refno']){
    $updatedrows['refno'] = $purchaseorder['refno'];
  }
  if(isset($purchaseorder['term']) && $purchaseorder['term'] != $current_purchaseorder['term']){
    $updatedrows['term'] = $purchaseorder['term'];
  }
  if(isset($purchaseorder['taxable']) && $purchaseorder['taxable'] != $current_purchaseorder['taxable']){
    $updatedrows['taxable'] = $purchaseorder['taxable'];
  }
  if(isset($purchaseorder['note']) && $purchaseorder['note'] != $current_purchaseorder['note'])
    $updatedrows['note'] = $purchaseorder['note'];

  if(isset($purchaseorder['ispaid']) && $purchaseorder['ispaid'] != $current_purchaseorder['ispaid']){

    if($purchaseorder['ispaid']){

      $paymentdate = ov('paymentdate', $purchaseorder);
      $paymentamount = ov('paymentamount', $purchaseorder, 0, 0);
      $paymentaccountid = ov('paymentaccountid', $purchaseorder, 0, 2);
      $total_per_currency = ova('total', $purchaseorder, $current_purchaseorder);
      $currency_rate = ova('currencyrate', $purchaseorder, $current_purchaseorder);

      if(!isdate($paymentdate)) exc('Tanggal pelunasan harus diisi.');
      if(!chartofaccount_id_exists($paymentaccountid)) exc("Akun pelunasan harus diisi.");
      if(!money_is_equal($paymentamount, $total_per_currency * $currency_rate)) exc("Jumlah pelunasan salah.");

    }

    $updatedrows['paymentdate'] = isset($paymentdate) ? $paymentdate : '';
    $updatedrows['paymentaccountid'] = isset($paymentaccountid) ? $paymentaccountid : null;
    $updatedrows['paymentamount'] = isset($paymentamount) ? $paymentamount : 0;
    $updatedrows['ispaid'] = $purchaseorder['ispaid'];

  }

  if(isset($purchaseorder['freightcharge']) && $purchaseorder['freightcharge'] != $current_purchaseorder['freightcharge'])
    $updatedrows['freightcharge'] = $purchaseorder['freightcharge'];

  if(count($updatedrows) > 0){
    $updatedrows['lastupdatedon'] = date('YmdHis');
    mysql_update_row('purchaseorder', $updatedrows, array('id'=>$id));
  }

  if(isset($purchaseorder['inventories'])){
    $inventories = $purchaseorder['inventories'];

    $query = "DELETE FROM purchaseorderinventory WHERE purchaseorderid = ?";
    pm($query, array($id));

    $params = array();
    $queries = array();
    for($i = 0 ; $i < count($inventories) ; $i++){
      $row = $inventories[$i];
      $inventorycode = ov('inventorycode', $row);
      if(empty($inventorycode)) continue;
      $inventory = inventorydetail(null, array('code'=>$inventorycode));
      if(!$inventory) throw new Exception('Barang tidak terdaftar. (' . $inventorycode . ')');
      $inventoryid = $inventory['id'];
      $inventorycode = $inventory['code'];
      $inventorydescription = $inventory['description'];
      $qty = ov('qty', $row);
      $unit = ov('unit', $row);
      $unitprice = ov('unitprice', $row);
      $unittotal = $qty * $unitprice;
      $unitdiscount = ov('unitdiscount', $row);
      $unitdiscountamount = intval($unitdiscount) ? $unitdiscount / 100 * $unittotal : 0;
      $unittotal = $unittotal - $unitdiscountamount;
      $unithandlingfee = ov('unithandlingfee', $row, 0, 0);

      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, $unitdiscount,
        $unitdiscountamount, $unittotal, $unithandlingfee);
      $queries[] = "(?, ?, ?, ?, ?, ?, ?, ?, ? ,?, ?)";
    }
    if(count($queries) > 0){
      $query = "INSERT INTO purchaseorderinventory(purchaseorderid, inventoryid, inventorycode, inventorydescription, qty, unit, unitprice, unitdiscount, unitdiscountamount,
        unittotal, unithandlingfee) VALUES " . implode(', ', $queries);
      pm($query, $params);
    }

    $updatedrows['inventories'] = $purchaseorder['inventories'];
  }

  purchaseordercalculate($id);
  if(function_exists('inventory_purchaseorderqty')) inventory_purchaseorderqty();

  userlog('purchaseordermodify', $current_purchaseorder, $updatedrows, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);

}
function purchaseorderremove($filters){

  $purchaseorder = purchaseorderdetail(null, $filters);
  if($purchaseorder){

    $id = $purchaseorder['id'];

    $lock_file = __DIR__ . "/../usr/system/purchaseorder_remove_$id.lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    if($purchaseorder['invoicerefid']) throw new Exception('Tidak dapat menghapus pesanan pembelian ini. Sudah ada faktur. Silakan menghapus faktur terlebih dahulu.');

    // Check if there's purchaseinvoice
    $exists = intval(pmc("SELECT COUNT(*) FROM purchaseinvoice WHERE purchaseorderid = ?", array($id)));
    if($exists) throw new Exception("Tidak dapat menghapus pesanan ini, sudah ada faktur pembelian.");

    journalvoucherremove(array('ref'=>'PO', 'refid'=>$id));
    $query = "DELETE FROM purchaseorder WHERE `id` = ?";
    pm($query, array($id));

    userlog('purchaseorderremove', $purchaseorder, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }

}

function purchaseordercalculate($id){

  $purchaseorder = purchaseorderdetail(null, array('id'=>$id));
  $inventories = $purchaseorder['inventories'];
  $currencyrate = $purchaseorder['currencyrate'];
  $date = $purchaseorder['date'];
  $code = $purchaseorder['code'];
  $supplierid = $purchaseorder['supplierid'];
  $discount = $purchaseorder['discount'];
  $discountamount = $purchaseorder['discountamount'];
  $taxable = $purchaseorder['taxable'];
  $freightcharge = ov('freightcharge', $purchaseorder, 0, 0);

  $subtotal = 0;
  for($i = 0 ; $i < count($inventories) ; $i++){
    $row = $inventories[$i];
    $inventorydescription = ov('inventorydescription', $row);
    $inventory = inventorydetail(null, array('description'=>$inventorydescription));
    $unittotal = ov('unittotal', $row);
    $subtotal += $unittotal;
  }

  if(intval($discount))
    $discountamount = $discount / 100 * $subtotal;
  $subtotal_afterdiscount = $subtotal - $discountamount;
  $taxamount = $taxable ? $subtotal_afterdiscount * 0.1 : 0;
  $total = $subtotal_afterdiscount + $taxamount + $freightcharge;
  $query = "UPDATE purchaseorder SET subtotal = ?, discount = ?, discountamount = ?, taxable = ?,
    taxamount = ?, total = ? WHERE `id` = ?";
  pm($query, array($subtotal, $discount, $discountamount, $taxable, $taxamount, $total, $id));

  journalvoucherremove(array('ref'=>'PO', 'refid'=>$id));

  $paymentamount = ov('paymentamount', $purchaseorder);
  if($paymentamount > 0){

    $paymentaccountid = ov('paymentaccountid', $purchaseorder);
    $paymentdate = ov('paymentdate', $purchaseorder);
    $handlingfeepaymentamount = ov('handlingfeepaymentamount', $purchaseorder, 0, 0);
    $handlingfeeaccountid = 40;

    $details = array();
    if($handlingfeepaymentamount > 0) $details[] = array('coaid'=>$handlingfeeaccountid, 'debitamount'=>$handlingfeepaymentamount, 'creditamount'=>0);
    $details[] = array('coaid'=>18, 'debitamount'=>$paymentamount - $handlingfeepaymentamount, 'creditamount'=>0);
    $details[] = array('coaid'=>$paymentaccountid, 'debitamount'=>0, 'creditamount'=>$paymentamount);

    journalvoucherentry(array(
      'date'=>$paymentdate,
      'description'=>'Payment for ' . $code,
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    ));
  }
  $ispaid = money_is_equal($paymentamount, $total * $currencyrate) ? 1 : 0;

  mysql_update_row('purchaseorder', [ 'ispaid'=>$ispaid ], [ 'id'=>$id ]);

  supplierpayablecalculate(array($supplierid));

}

?>