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
    array('active'=>0, 'name'=>'isbaddebt', 'text'=>'Bad Debt', 'width'=>40, 'type'=>'html', 'html'=>'purchaseorderlist_isbaddebt'),
    array('active'=>0, 'name'=>'journal', 'text'=>'Jurnal', 'width'=>40, 'align'=>'center', 'type'=>'html', 'html'=>'grid_journaloption'),
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
    'isbaddebt'=>'t1.isbaddebt',
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

  // ------------------------------------------------------------
  // Parameter extraction & validation
  // ------------------------------------------------------------
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
  $handlingfeeaccountid = ov('handlingfeeaccountid', $purchaseorder);
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
  $pph = ov('pph', $purchaseorder, 0, 0);
  $kso = ov('kso', $purchaseorder, 0, 0);
  $ski = ov('ski', $purchaseorder, 0, 0);
  $clearance_fee = ov('clearance_fee', $purchaseorder, 0, 0);
  $taxamount = ov('taxamount', $purchaseorder, 0, 0);
  $taxdate = ov('taxdate', $purchaseorder, 0, 0);
  $taxaccountid = ov('taxaccountid', $purchaseorder, 0, 0);
  $pphdate = ov('pphdate', $purchaseorder, 0, 0);
  $pphaccountid = ov('pphaccountid', $purchaseorder, 0, 0);
  $ksodate = ov('ksodate', $purchaseorder, 0, 0);
  $ksoaccountid = ov('ksoaccountid', $purchaseorder, 0, 0);
  $skidate = ov('skidate', $purchaseorder, 0, 0);
  $skiaccountid = ov('skiaccountid', $purchaseorder, 0, 0);
  $clearance_fee_date = ov('clearance_fee_date', $purchaseorder, 0, 0);
  $clearance_fee_accountid = ov('clearance_fee_accountid', $purchaseorder, 0, 0);
  $import_cost = ov('import_cost', $purchaseorder, 0, 0);
  $import_cost_date = ov('import_cost_date', $purchaseorder, 0, 0);
  $import_cost_accountid = ov('import_cost_accountid', $purchaseorder, 0, 0);

  // ------------------------------------------------------------
  // Validation
  // ------------------------------------------------------------
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(pmc("select count(*) from purchaseorder where code = ?", [ $code ]) > 0) exc("Kode pesanan sudah ada.");
  if(!is_array($inventories) || count($inventories) == 0) throw new Exception('Barang harus diisi.');
  if($paymentamount > 0){
    if(!isdate($paymentdate)) exc('Tanggal pelunasan harus diisi.');
    if(!chartofaccount_id_exists($paymentaccountid)) exc("Akun pelunasan harus diisi.");
  }
  if($taxamount > 0){
    if(!isdate($taxdate)) exc("Tanggal ppn harus diisi.");
    if(!chartofaccount_id_exists($taxaccountid)) exc("Akun ppn harus diisi.");
  }
  if($pph > 0){
    if(!isdate($pphdate)) exc("Tanggal pph harus diisi.");
    if(!chartofaccount_id_exists($pphaccountid)) exc("Akun pph harus diisi.");
  }
  if($kso > 0){
    if(!isdate($ksodate)) exc("Tanggal kso harus diisi.");
    if(!chartofaccount_id_exists($ksoaccountid)) exc("Akun kso harus diisi.");
  }
  if($ski > 0){
    if(!isdate($skidate)) exc("Tanggal ski harus diisi.");
    if(!chartofaccount_id_exists($skiaccountid)) exc("Akun ski harus diisi.");
  }
  if($clearance_fee > 0){
    if(!isdate($clearance_fee_date)) exc("Tanggal clearance fee harus diisi.");
    if(!chartofaccount_id_exists($clearance_fee_accountid)) exc("Akun clearance fee harus diisi.");
  }
  if($import_cost > 0){
    if(!isdate($import_cost_date)) exc("Tanggal bea masuk harus diisi.");
    if(!chartofaccount_id_exists($import_cost_accountid)) exc("Akun bea masuk harus diisi.");
  }
  if($handlingfeepaymentamount > 0){
    if(!isdate($handlingfeedate)) exc("Tanggal handling fee masuk harus diisi.");
    if(!chartofaccount_id_exists($handlingfeeaccountid)) exc("Akun handling fee masuk harus diisi.");
  }
  if($ispaid && $paymentamount <= 0) exc("Nilai pelunasan belum diisi.");

  $subtotal = 0;
  foreach($inventories as $index=>$row){

    $inventorycode = ov('inventorycode', $row);
    if(empty($inventorycode)) continue;

    $inventory = inventorydetail(null, array('code'=>$inventorycode));
    if(!$inventory) exc("Barang tidak terdaftar.");

    $qty = ov('qty', $row);
    if($qty <= 0) exc("Kuantitas barang harus diisi.");

    $unitprice = ov('unitprice', $row);
    if($unitprice <= 0) exc("Harga barang harus diisi.");

    $unittotal = $qty * $unitprice;
    $unitdiscount = ov('unitdiscount', $row);
    $unitdiscountamount = intval($unitdiscount) ? $unitdiscount / 100 * $unittotal : 0;
    $unittotal = $unittotal - $unitdiscountamount;

    $subtotal += $unittotal;
  }
  $total = $subtotal - $discountamount + $freightcharge;

  // Perform ACID
  try{

    pdo_begin_transaction();

    // Save to purchase order
    $query = "
    INSERT INTO purchaseorder
    (
      isinvoiced, code, `date`, supplierid, supplierdescription, currencyid, currencyrate, address, note, ispaid, 
      paymentaccountid, subtotal, paymentamount, paymentdate, discount, discountamount, total, taxable, createdon, createdby, lastupdatedon, freightcharge, 
      handlingfeevolume, handlingfeeamount, handlingfeeaccountid, handlingfeedate, handlingfeepaymentamount, refno, eta, term, pph, kso,
      ski, clearance_fee, taxamount, taxdate, taxaccountid, pphdate, pphaccountid, ksodate, ksoaccountid, skidate, skiaccountid,
      clearance_fee_date, clearance_fee_accountid, import_cost, import_cost_date, import_cost_accountid
    )
    VALUES
    ( 
      ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
      ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?
    )
  ";
    $id = pmi($query,
      array(
        $isinvoiced, $code, $date, $supplierid, $supplierdescription, $currencyid, $currencyrate, $address, $note, $ispaid,
        $paymentaccountid, $subtotal, $paymentamount, $paymentdate, $discount, $discountamount, $total, $taxable, $createdon, $createdby, $lastupdatedon, $freightcharge,
        $handlingfeevolume, $handlingfeeamount, $handlingfeeaccountid, $handlingfeedate, $handlingfeepaymentamount, $refno, $eta, $term, $pph, $kso,
        $ski, $clearance_fee, $taxamount, $taxdate, $taxaccountid, $pphdate, $pphaccountid, $ksodate, $ksoaccountid, $skidate, $skiaccountid,
        $clearance_fee_date, $clearance_fee_accountid, $import_cost, $import_cost_date, $import_cost_accountid
      )
    );

    // Save to purchase order inventory
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
      $unittax = ov('unittax', $row);

      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, $unitdiscount,
        $unitdiscountamount, $unittotal, $unithandlingfee, $unittax);
      $queries[] = "(?, ?, ?, ?, ?, ?, ?, ?, ? ,?, ?, ?)";
    }
    if(count($queries) > 0){
      $query = "INSERT INTO purchaseorderinventory(purchaseorderid, inventoryid, inventorycode, inventorydescription, qty, unit,
    unitprice, unitdiscount, unitdiscountamount, unittotal, unithandlingfee, unittax) VALUES " . implode(', ', $queries);
      pm($query, $params);
    }

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

  userlog('purchaseorderentry', $purchaseorder, '', $_SESSION['user']['id'], $id);

  purchaseordercalculate($id);
  inventory_purchaseorderqty();

  return [ 'id'=>$id ];

}
function purchaseordermodify($purchaseorder){

  // Retrieve existing data
  $id = ov('id', $purchaseorder, 1);
  $current = purchaseorderdetail(null, array('id'=>$id));
  if(!$current) throw new Exception("Pesanan tidak ada.");

  // Apply default value
  $current['taxdate'] = $current['taxdate'] == '0000-00-00' ? '' : $current['taxdate'];
  $current['pphdate'] = $current['pphdate'] == '0000-00-00' ? '' : $current['pphdate'];
  $current['ksodate'] = $current['ksodate'] == '0000-00-00' ? '' : $current['ksodate'];
  $current['skidate'] = $current['skidate'] == '0000-00-00' ? '' : $current['skidate'];
  $current['clearance_fee_date'] = $current['clearance_fee_date'] == '0000-00-00' ? '' : $current['clearance_fee_date'];
  $current['import_cost_date'] = $current['import_cost_date'] == '0000-00-00' ? '' : $current['import_cost_date'];
  $current['handlingfeedate'] = $current['handlingfeedate'] == '0000-00-00' ? '' : $current['handlingfeedate'];

  $updatedrows = array();

  if(isset($purchaseorder['supplierdescription'])){
    $supplier = supplierdetail(null, array('description'=>$purchaseorder['supplierdescription']));
    if(!$supplier) exc("Supplier tidak terdaftar.");
    $updatedrows['supplierid'] = $supplier['id'];
    $updatedrows['supplierdescription'] = $supplier['description'];
  }

  if(isset($purchaseorder['date']) && date('Ymd', strtotime($purchaseorder['date'])) != date('Ymd', strtotime($current['date']))){
    if(!isdate($purchaseorder['date'])) exc('Format tanggal salah');
    $updatedrows['date'] = ov('date', $purchaseorder, 1, array('type'=>'date'));
  }

  if(isset($purchaseorder['address']) && $purchaseorder['address'] != $current['address']){
    $updatedrows['address'] = $purchaseorder['address'];
  }

  if(isset($purchaseorder['currencyid']) && $purchaseorder['currencyid'] != $current['currencyid']){
    $updatedrows['currencyid'] = $purchaseorder['currencyid'];
  }

  if(isset($purchaseorder['currencyrate']) && $purchaseorder['currencyrate'] != $current['currencyrate']){
    if(!$purchaseorder['currencyrate']) exc("Nilai tukar salah.");
    $updatedrows['currencyrate'] = $purchaseorder['currencyrate'];
  }

  if(isset($purchaseorder['discount']) && $purchaseorder['discount'] != $current['discount']){
    $updatedrows['discount'] = $purchaseorder['discount'];
  }

  if(isset($purchaseorder['discountamount']) && $purchaseorder['discountamount'] != $current['discountamount']){
    $updatedrows['discountamount'] = $purchaseorder['discountamount'];
  }

  if(isset($purchaseorder['eta']) && $purchaseorder['eta'] != $current['eta']){
    $updatedrows['eta'] = $purchaseorder['eta'];
  }

  if(isset($purchaseorder['refno']) && $purchaseorder['refno'] != $current['refno']){
    $updatedrows['refno'] = $purchaseorder['refno'];
  }

  if(isset($purchaseorder['term']) && $purchaseorder['term'] != $current['term']){
    $updatedrows['term'] = $purchaseorder['term'];
  }

  if(isset($purchaseorder['note']) && $purchaseorder['note'] != $current['note'])
    $updatedrows['note'] = $purchaseorder['note'];

  if(isset($purchaseorder['isbaddebt']) && $purchaseorder['isbaddebt'] != $current['isbaddebt']){
    $isbaddebt = $purchaseorder['isbaddebt'] ? 1 : 0;

    if($isbaddebt){
      $baddebtamount = ov('baddebtamount', $purchaseorder, 1);
      $baddebtdate = ov('baddebtdate', $purchaseorder, 1);
      $baddebtaccountid = ov('baddebtaccountid', $purchaseorder, 1);
      if($baddebtamount <= 0) exc("Nilai bad debt harus diisi.");
      if(!isdate($baddebtdate)) exc("Tanggal bad debt harus diisi");
      if(date('Ymd', strtotime($baddebtdate)) < date('Ymd', ova('baddebtdate', $purchaseorder, $current))) exc("Tanggal bad debt harus lebih dari tanggal pelunasan.");
      if(!chartofaccount_id_exists($baddebtaccountid)) exc("Akun bad debt belum diisi.");
    }
    $updatedrows['isbaddebt'] = $isbaddebt;

  }

  if(isset($purchaseorder['baddebtamount']) && $purchaseorder['baddebtamount'] != $current['baddebtamount']){
    $updatedrows['baddebtamount'] = $purchaseorder['baddebtamount'];
  }

  if(isset($purchaseorder['baddebtdate']) &&
    date('Ymd', strtotime($purchaseorder['baddebtdate'])) >= date('Ymd', strtotime($current['baddebtdate']))){
    $updatedrows['baddebtdate'] = $purchaseorder['baddebtdate'];
  }

  if(isset($purchaseorder['baddebtaccountid'])){
    $updatedrows['baddebtaccountid'] = $purchaseorder['baddebtaccountid'];
  }

  if(isset($purchaseorder['ispaid']) && $purchaseorder['ispaid'] != $current['ispaid']){
    $paymentamount = ov('paymentamount', $purchaseorder);
    if($paymentamount <= 0) exc("Nilai pelunasan belum diisi.");
    $updatedrows['ispaid'] = $purchaseorder['ispaid'];
  }

  if(isset($purchaseorder['paymentamount']) && $purchaseorder['paymentamount'] != $current['paymentamount']){
    if($purchaseorder['paymentamount'] > 0){  // If has payment amount, require 2 more fields
      $paymentdate = ov('paymentdate', $purchaseorder, 1);
      $paymentaccountid = ov('paymentaccountid', $purchaseorder, 1);
      if(!isdate($paymentdate)) exc("Tanggal pelunasan salah.");
      if(!chartofaccount_id_exists($paymentaccountid)) exc("Akun pelunasan salah.");
    }
    $updatedrows['paymentamount'] = $purchaseorder['paymentamount'];
  }

  if(isset($purchaseorder['paymentdate']) &&
    date('Ymd', strtotime($purchaseorder['paymentdate'])) >= date('Ymd', strtotime($current['date'])) &&
    date('Ymd', strtotime($purchaseorder['paymentdate'])) != date('Ymd', strtotime($current['paymentdate']))){
    $updatedrows['paymentdate'] = date('Ymd', strtotime($purchaseorder['paymentdate']));
  }

  if(isset($purchaseorder['paymentaccountid']) &&
    $purchaseorder['paymentaccountid'] != $current['paymentaccountid']
  ){
    if(!chartofaccount_id_exists($purchaseorder['paymentaccountid'])) exc("Akun pelunasan belum diisi.");
    $updatedrows['paymentaccountid'] = $purchaseorder['paymentaccountid'];
  }

  if(isset($purchaseorder['freightcharge']) && $purchaseorder['freightcharge'] != $current['freightcharge'])
    $updatedrows['freightcharge'] = $purchaseorder['freightcharge'];

  if($purchaseorder['taxamount'] != $current['taxamount'] ||
    date('Ymd', strtotime($purchaseorder['taxdate'])) != date('Ymd', strtotime($current['taxdate'])) ||
    intval($purchaseorder['taxaccountid']) != intval($current['taxaccountid'])){
    if(!isdate($purchaseorder['taxdate'])) exc("Tanggal ppn harus diisi.");
    if(!chartofaccount_id_exists($purchaseorder['taxaccountid'])) exc("Akun ppn harus diisi.");
    $updatedrows['taxamount'] = $purchaseorder['taxamount'];
    $updatedrows['taxdate'] = $purchaseorder['taxdate'];
    $updatedrows['taxaccountid'] = $purchaseorder['taxaccountid'];
  }

  if($purchaseorder['pph'] != $current['pph'] ||
    date('Ymd', strtotime($purchaseorder['pphdate'])) != date('Ymd', strtotime($current['pphdate'])) ||
    intval($purchaseorder['pphaccountid']) != intval($current['pphaccountid'])){
    if(!isdate($purchaseorder['pphdate'])) exc("Tanggal pph harus diisi.");
    if(!chartofaccount_id_exists($purchaseorder['pphaccountid'])) exc("Akun pph harus diisi.");
    $updatedrows['pph'] = $purchaseorder['pph'];
    $updatedrows['pphdate'] = $purchaseorder['pphdate'];
    $updatedrows['pphaccountid'] = $purchaseorder['pphaccountid'];
  }

  if($purchaseorder['kso'] != $current['kso'] ||
    date('Ymd', strtotime($purchaseorder['ksodate'])) != date('Ymd', strtotime($current['ksodate'])) ||
    intval($purchaseorder['ksoaccountid']) != intval($current['ksoaccountid'])){
    if(!isdate($purchaseorder['ksodate'])) exc("Tanggal kso harus diisi.");
    if(!chartofaccount_id_exists($purchaseorder['ksoaccountid'])) exc("Akun kso harus diisi.");
    $updatedrows['kso'] = $purchaseorder['kso'];
    $updatedrows['ksodate'] = $purchaseorder['ksodate'];
    $updatedrows['ksoaccountid'] = $purchaseorder['ksoaccountid'];
  }

  if($purchaseorder['ski'] != $current['ski'] ||
    date('Ymd', strtotime($purchaseorder['skidate'])) != date('Ymd', strtotime($current['skidate'])) ||
    intval($purchaseorder['skiaccountid']) != intval($current['skiaccountid'])){
    if(!isdate($purchaseorder['skidate'])) exc("Tanggal ski harus diisi.");
    if(!chartofaccount_id_exists($purchaseorder['skiaccountid'])) exc("Akun ski harus diisi.");
    $updatedrows['ski'] = $purchaseorder['ski'];
    $updatedrows['skidate'] = $purchaseorder['skidate'];
    $updatedrows['skiaccountid'] = $purchaseorder['skiaccountid'];
  }

  if($purchaseorder['clearance_fee'] != $current['clearance_fee'] ||
    date('Ymd', strtotime($purchaseorder['clearance_fee_date'])) != date('Ymd', strtotime($current['clearance_fee_date'])) ||
    intval($purchaseorder['clearance_fee_accountid']) != intval($current['clearance_fee_accountid'])){
    if(!isdate($purchaseorder['clearance_fee_date'])) exc("Tanggal clearance fee harus diisi.");
    if(!chartofaccount_id_exists($purchaseorder['clearance_fee_accountid'])) exc("Akun clearance fee harus diisi.");
    $updatedrows['clearance_fee'] = $purchaseorder['clearance_fee'];
    $updatedrows['clearance_fee_date'] = $purchaseorder['clearance_fee_date'];
    $updatedrows['clearance_fee_accountid'] = $purchaseorder['clearance_fee_accountid'];
  }

  if($purchaseorder['import_cost'] != $current['import_cost'] ||
    date('Ymd', strtotime($purchaseorder['import_cost_date'])) != date('Ymd', strtotime($current['import_cost_date'])) ||
    intval($purchaseorder['import_cost_accountid']) != intval($current['import_cost_accountid'])){
    if(!isdate($purchaseorder['import_cost_date'])) exc("Tanggal bea masuk harus diisi.");
    if(!chartofaccount_id_exists($purchaseorder['import_cost_accountid'])) exc("Akun bea masuk harus diisi.");
    $updatedrows['import_cost'] = $purchaseorder['import_cost'];
    $updatedrows['import_cost_date'] = $purchaseorder['import_cost_date'];
    $updatedrows['import_cost_accountid'] = $purchaseorder['import_cost_accountid'];
  }

  if(doubleval($purchaseorder['handlingfeepaymentamount']) != doubleval($current['handlingfeepaymentamount']) ||
    date('Ymd', strtotime($purchaseorder['handlingfeedate'])) != date('Ymd', strtotime($current['handlingfeedate'])) ||
    intval($purchaseorder['handlingfeeaccountid']) != intval($current['handlingfeeaccountid'])){
    if(!isdate($purchaseorder['handlingfeedate'])) exc("Tanggal handling fee masuk harus diisi.");
    if(!chartofaccount_id_exists($purchaseorder['handlingfeeaccountid'])) exc("Akun handling fee masuk harus diisi.");
    $updatedrows['handlingfeepaymentamount'] = $purchaseorder['handlingfeepaymentamount'];
    $updatedrows['handlingfeedate'] = $purchaseorder['handlingfeedate'];
    $updatedrows['handlingfeeaccountid'] = $purchaseorder['handlingfeeaccountid'];
  }

  if(isset($purchaseorder['inventories'])){
    $inventories = $purchaseorder['inventories'];

    $subtotal = 0;
    foreach($inventories as $index=>$row){

      $inventorycode = ov('inventorycode', $row);
      if(empty($inventorycode)) continue;

      $inventory = inventorydetail(null, array('code'=>$inventorycode));
      if(!$inventory) exc("Barang tidak terdaftar.");

      $qty = ov('qty', $row);
      if($qty <= 0) exc("Kuantitas barang harus diisi.");

      $unitprice = ov('unitprice', $row);
      if($unitprice <= 0) exc("Harga barang harus diisi.");

      $unittotal = $qty * $unitprice;
      $unitdiscount = ov('unitdiscount', $row);
      $unitdiscountamount = intval($unitdiscount) ? $unitdiscount / 100 * $unittotal : 0;
      $unittotal = $unittotal - $unitdiscountamount;

      $subtotal += $unittotal;
    }
    $discountamount = ova('discountamount', $purchaseorder, $current);
    $freightcharge = ova('freightcharge', $purchaseorder, $current);
    $total = $subtotal - $discountamount + $freightcharge;

    $updatedrows['subtotal'] = $subtotal;
    $updatedrows['total'] = $total;
  }

  try{

    pdo_begin_transaction();

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
        $unittax = ov('unittax', $row, 0, 0);

        array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, $unitdiscount,
          $unitdiscountamount, $unittotal, $unithandlingfee, $unittax);
        $queries[] = "(?, ?, ?, ?, ?, ?, ?, ?, ? ,?, ?, ?)";
      }
      if(count($queries) > 0){
        $query = "INSERT INTO purchaseorderinventory(purchaseorderid, inventoryid, inventorycode, inventorydescription, qty, unit, unitprice, unitdiscount, unitdiscountamount,
        unittotal, unithandlingfee, unittax) VALUES " . implode(', ', $queries);
        pm($query, $params);
      }

      $updatedrows['inventories'] = $purchaseorder['inventories'];
    }

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

  purchaseordercalculate($id);

  userlog('purchaseordermodify', $current, $updatedrows, $_SESSION['user']['id'], $id);

  return array('id'=>$id);

}
function purchaseorderremove($filters){

  $purchaseorder = purchaseorderdetail(null, $filters);
  if($purchaseorder){

    $id = $purchaseorder['id'];

    if($purchaseorder['invoicerefid']) throw new Exception('Tidak dapat menghapus pesanan pembelian ini. Sudah ada faktur. Silakan menghapus faktur terlebih dahulu.');

    // Check if there's purchaseinvoice
    $exists = intval(pmc("SELECT COUNT(*) FROM purchaseinvoice WHERE purchaseorderid = ?", array($id)));
    if($exists) throw new Exception("Tidak dapat menghapus pesanan ini, sudah ada faktur pembelian.");

    try{

      pdo_begin_transaction();

      journalvoucherremove(array('ref'=>'PO', 'refid'=>$id));
      $query = "DELETE FROM purchaseorder WHERE `id` = ?";
      pm($query, array($id));
      userlog('purchaseorderremove', $purchaseorder, '', $_SESSION['user']['id'], $id);

      pdo_commit();

    }
    catch(Exception $ex){

      pdo_rollback();
      throw $ex;

    }

  }

}

function purchaseordercalculate($id){

  // Retrieve system object
  $purchaseinvoice_downpaymentaccountid = systemvarget('purchaseinvoice_downpaymentaccountid');
  $taxdebitaccountid = systemvarget('purchaseinvoice_taxaccountid');
  $pphdebitaccountid = systemvarget('purchaseinvoice_pphaccountid');
  $ksodebitaccountid = systemvarget('purchaseinvoice_ksoaccountid');
  $skidebitaccountid = systemvarget('purchaseinvoice_skiaccountid');
  $clearance_fee_debitaccountid = systemvarget('purchaseinvoice_clearance_fee_accountid');
  $import_cost_debitaccountid = systemvarget('purchaseinvoice_import_cost_accountid');
  $handlingfee_debitaccountid = systemvarget('purchaseinvoice_handlingfeeaccountid');

  // Retrieve purchase order object
  $current = purchaseorderdetail(null, array('id'=>$id));
  $code = $current['code'];
  $supplierid = $current['supplierid'];
  $paymentamount = ov('paymentamount', $current);
  $taxamount = ov('taxamount', $current);
  $pph = ov('pph', $current);
  $kso = ov('kso', $current);
  $clearance_fee = ov('clearance_fee', $current);
  $import_cost = ov('import_cost', $current);
  $handlingfeepaymentamount = ov('handlingfeepaymentamount', $current);
  $isbaddebt = ov('isbaddebt', $current);

  /**
   * Create journals
   */
  $journalvouchers = [];

  // Payment
  if($paymentamount > 0){

    $paymentaccountid = ov('paymentaccountid', $current);
    $paymentdate = ov('paymentdate', $current);

    $details = array();
    $details[] = array('coaid'=>$purchaseinvoice_downpaymentaccountid, 'debitamount'=>$paymentamount, 'creditamount'=>0);
    $details[] = array('coaid'=>$paymentaccountid, 'debitamount'=>0, 'creditamount'=>$paymentamount);
    $journalvoucher = array(
      'date'=>$paymentdate,
      'description'=>'Payment for ' . $code,
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // Tax
  if($taxamount > 0){

    $taxdate = ov('taxdate', $current);
    $taxaccountid = ov('taxaccountid', $current);

    $details = [];
    $details[] =  array('coaid'=>$taxdebitaccountid, 'debitamount'=>$taxamount, 'creditamount'=>0);
    $details[] =  array('coaid'=>$taxaccountid, 'debitamount'=>0, 'creditamount'=>$taxamount);
    $journalvoucher = array(
      'date'=>$taxdate,
      'description'=>$code . " PPn",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // PPh
  if($pph > 0){

    $pphdate = ov('pphdate', $current);
    $pphaccountid = ov('pphaccountid', $current);

    $details = [];
    $details[] =  array('coaid'=>$pphdebitaccountid, 'debitamount'=>$pph, 'creditamount'=>0);
    $details[] =  array('coaid'=>$pphaccountid, 'debitamount'=>0, 'creditamount'=>$pph);
    $journalvoucher = array(
      'date'=>$pphdate,
      'description'=>$code . " PPH",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // KSO
  if($kso > 0){

    $ksodate = ov('ksodate', $current);
    $ksoaccountid = ov('ksoaccountid', $current);

    $details = [];
    $details[] =  array('coaid'=>$ksodebitaccountid, 'debitamount'=>$kso, 'creditamount'=>0);
    $details[] =  array('coaid'=>$ksoaccountid, 'debitamount'=>0, 'creditamount'=>$kso);
    $journalvoucher = array(
      'date'=>$ksodate,
      'description'=>$code . " KSO",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // SKI
  $ski = ov('ski', $current);
  if($ski > 0){

    $skidate = ov('skidate', $current);
    $skiaccountid = ov('skiaccountid', $current);

    $details = [];
    $details[] =  array('coaid'=>$skidebitaccountid, 'debitamount'=>$ski, 'creditamount'=>0);
    $details[] =  array('coaid'=>$skiaccountid, 'debitamount'=>0, 'creditamount'=>$ski);
    $journalvoucher = array(
      'date'=>$skidate,
      'description'=>$code . " SKI",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // Clearance Fee
  if($clearance_fee > 0){

    $clearance_fee_date = ov('clearance_fee_date', $current);
    $clearance_fee_accountid = ov('clearance_fee_accountid', $current);

    $details = [];
    $details[] =  array('coaid'=>$clearance_fee_debitaccountid, 'debitamount'=>$clearance_fee, 'creditamount'=>0);
    $details[] =  array('coaid'=>$clearance_fee_accountid, 'debitamount'=>0, 'creditamount'=>$clearance_fee);
    $journalvoucher = array(
      'date'=>$clearance_fee_date,
      'description'=>$code . " CLEARANCE FEE",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // Import Cost
  if($import_cost > 0){

    $import_cost_date = ov('import_cost_date', $current);
    $import_cost_accountid = ov('import_cost_accountid', $current);

    $details = [];
    $details[] =  array('coaid'=>$import_cost_debitaccountid, 'debitamount'=>$import_cost, 'creditamount'=>0);
    $details[] =  array('coaid'=>$import_cost_accountid, 'debitamount'=>0, 'creditamount'=>$import_cost);
    $journalvoucher = array(
      'date'=>$import_cost_date,
      'description'=>$code . " BEA MASUK",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // Create journal for handling fee if any
  if($handlingfeepaymentamount > 0){

    $handlingfeedate = ov('handlingfeedate', $current);
    $handlingfeeaccountid = ov('handlingfeeaccountid', $current);

    $details = [];
    $details[] =  array('coaid'=>$handlingfee_debitaccountid, 'debitamount'=>$handlingfeepaymentamount, 'creditamount'=>0);
    $details[] =  array('coaid'=>$handlingfeeaccountid, 'debitamount'=>0, 'creditamount'=>$handlingfeepaymentamount);
    $journalvoucher = array(
      'date'=>$handlingfeedate,
      'description'=>$code . " HANDLING FEE",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  if($isbaddebt){

    $baddebtdate = ov('baddebtdate', $current);
    $baddebtaccountid = ov('baddebtaccountid', $current);
    $baddebtamount = ov('baddebtamount', $current);

    $details = [];
    $details[] =  array('coaid'=>$baddebtaccountid, 'debitamount'=>$baddebtamount, 'creditamount'=>0);
    $details[] =  array('coaid'=>$purchaseinvoice_downpaymentaccountid, 'debitamount'=>0, 'creditamount'=>$baddebtamount);
    $journalvoucher = array(
      'date'=>$baddebtdate,
      'description'=>$code . " BAD DEBT",
      'ref'=>'PO',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }

  if(count($journalvouchers) > 0){
    journalvoucherremove(array('ref'=>'PO', 'refid'=>$id));
    journalvoucherentries($journalvouchers);
  }

  if(function_exists('supplierpayablecalculate')) supplierpayablecalculate(array($supplierid));
  if(function_exists('inventory_purchaseorderqty')) inventory_purchaseorderqty();

}

?>