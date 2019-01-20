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

    $payments = pmrs("select 
        `id`, `date` as paymentdate, amount as paymentamount, currencyrate as paymentcurrencyrate,
        totalamount as paymenttotalamount, chartofaccountid as paymentaccountid
      from purchaseorderpayment where purchaseorderid = ?", [ $purchaseorder['id'] ]);
    $purchaseorder['payments'] = $payments;

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
  $currencyrate = 1;
  $note = ov('note', $purchaseorder);
  $discount = ov('discount', $purchaseorder, 0, 0);
  $discountamount = ov('discountamount', $purchaseorder, 0, 0);
  $taxable = ov('taxable', $purchaseorder, 0, 0);
  $inventories = ov('inventories', $purchaseorder, 1);
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];
  $isinvoiced = 0;
  $ispaid = ov('ispaid', $purchaseorder);
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

  // Validate payment
  $paymentdate = null;
  $paymentamount = 0;
  $paymentaccountid = 0;
  $payments = [];
  $payment_amount_in_currency = 0;
  for($i = 0 ; $i < 5 ; $i++){

    $n_paymentamount = ov("paymentamount-$i", $purchaseorder);
    $n_paymentcurrencyrate = ov("paymentcurrencyrate-$i", $purchaseorder);
    $n_paymentdate = ov("paymentdate-$i", $purchaseorder);
    $n_paymentaccountid = ov("paymentaccountid-$i", $purchaseorder);
    $n_totalamount = $n_paymentcurrencyrate * $n_paymentamount;

    if(!$n_paymentamount) continue;

    if(!$n_paymentdate) exc("Tanggal pembayaran belum diisi");
    if(!$n_paymentaccountid) exc("Akun pembayaran belum diisi");
    if(!$n_totalamount) exc("Total pembayaran belum diisi ");

    $payments[] = [
      'amount'=>$n_paymentamount,
      'currencyrate'=>$n_paymentcurrencyrate,
      'date'=>$n_paymentdate,
      'chartofaccountid'=>$n_paymentaccountid,
      'totalamount'=>$n_totalamount,
    ];

    $paymentamount += $n_totalamount;
    $payment_amount_in_currency += $n_paymentamount;
    if(!$paymentdate) $paymentdate = $n_paymentdate;
    if(!$paymentaccountid) $paymentaccountid = $n_paymentaccountid;

  }

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

    pm("delete from purchaseorderpayment where purchaseorderid = ?", [ $id ]);
    foreach($payments as $payment){

      pm("insert into purchaseorderpayment (purchaseorderid, `type`, `date`, amount, chartofaccountid, currencyrate, totalamount)
        values (?, ?, ?, ?, ? ,?, ?)", [
          $id,
          1,
          $payment['date'],
          $payment['amount'],
          $payment['chartofaccountid'],
          $payment['currencyrate'],
          $payment['totalamount']
      ]);

    }

    userlog('purchaseorderentry', $purchaseorder, '', $_SESSION['user']['id'], $id);

    job_create_and_run('purchaseorder_ext', [ $id ]);

    inventory_purchaseorderqty();

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

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

  $updatedrows = [];

  if(isset($purchaseorder['supplierdescription'])){
    $supplier = supplierdetail(null, array('description'=>$purchaseorder['supplierdescription']));
    if(!$supplier) exc("Supplier tidak terdaftar.");
    $updatedrows['supplierid'] = $supplier['id'];
    $updatedrows['supplierdescription'] = $supplier['description'];
  }

  if(isset($purchaseorder['date']) && isdate($purchaseorder['date']) && date('Ymd', strtotime($purchaseorder['date'])) != date('Ymd', strtotime($current['date'])))
    $updatedrows['date'] = ov('date', $purchaseorder, 1, array('type'=>'date'));

  if(isset($purchaseorder['address']) && $purchaseorder['address'] != $current['address'])
    $updatedrows['address'] = $purchaseorder['address'];

  if(isset($purchaseorder['currencyid']) && $purchaseorder['currencyid'] != $current['currencyid'])
    $updatedrows['currencyid'] = $purchaseorder['currencyid'];

  if(isset($purchaseorder['discount']) && $purchaseorder['discount'] != $current['discount'])
    $updatedrows['discount'] = $purchaseorder['discount'];

  if(isset($purchaseorder['discountamount']) && $purchaseorder['discountamount'] != $current['discountamount'])
    $updatedrows['discountamount'] = $purchaseorder['discountamount'];

  if(isset($purchaseorder['eta']) && isdate($purchaseorder['eta']) && $purchaseorder['eta'] != $current['eta'])
    $updatedrows['eta'] = $purchaseorder['eta'];

  if(isset($purchaseorder['refno']) && $purchaseorder['refno'] != $current['refno'])
    $updatedrows['refno'] = $purchaseorder['refno'];

  if(isset($purchaseorder['term']) && $purchaseorder['term'] != $current['term'])
    $updatedrows['term'] = $purchaseorder['term'];

  if(isset($purchaseorder['note']) && $purchaseorder['note'] != $current['note'])
    $updatedrows['note'] = $purchaseorder['note'];

  if(isset($purchaseorder['isbaddebt']) && $purchaseorder['isbaddebt'] != $current['isbaddebt'])
    $updatedrows['isbaddebt'] = $purchaseorder['isbaddebt'] ? 1 : 0;
  if(isset($purchaseorder['baddebtamount']) && $purchaseorder['baddebtamount'] != $current['baddebtamount'])
    $updatedrows['baddebtamount'] = $purchaseorder['baddebtamount'];
  if(isset($purchaseorder['baddebtdate']) && isdate($purchaseorder['baddebtdate']) &&
    date('Ymd', strtotime($purchaseorder['baddebtdate'])) >= date('Ymd', strtotime($current['baddebtdate'])))
    $updatedrows['baddebtdate'] = $purchaseorder['baddebtdate'];
  if(isset($purchaseorder['baddebtaccountid']))
    $updatedrows['baddebtaccountid'] = $purchaseorder['baddebtaccountid'];

  if(isset($purchaseorder['freightcharge']) && $purchaseorder['freightcharge'] != $current['freightcharge'])
    $updatedrows['freightcharge'] = $purchaseorder['freightcharge'];

  if(isset($purchaseorder['taxamount']) && $purchaseorder['taxamount'] != $current['taxamount'])
    $updatedrows['taxamount'] = $purchaseorder['taxamount'];
  if(isset($purchaseorder['taxaccountid']) && $purchaseorder['taxaccountid'] != $current['taxaccountid'])
    $updatedrows['taxaccountid'] = $purchaseorder['taxaccountid'];
  if(isset($purchaseorder['taxdate']) && isdate($purchaseorder['taxdate']) && $purchaseorder['taxdate'] != $current['taxdate'])
    $updatedrows['taxdate'] = $purchaseorder['taxdate'];

  if(isset($purchaseorder['pph']) && $purchaseorder['pph'] != $current['pph'])
    $updatedrows['pph'] = $purchaseorder['pph'];
  if(isset($purchaseorder['pphaccountid']) && $purchaseorder['pphaccountid'] != $current['pphaccountid'])
    $updatedrows['pphaccountid'] = $purchaseorder['pphaccountid'];
  if(isset($purchaseorder['pphdate']) && isdate($purchaseorder['pphdate']) && $purchaseorder['pphdate'] != $current['pphdate'])
    $updatedrows['pphdate'] = $purchaseorder['pphdate'];

  if(isset($purchaseorder['kso']) && $purchaseorder['kso'] != $current['kso'])
    $updatedrows['kso'] = $purchaseorder['kso'];
  if(isset($purchaseorder['ksoaccountid']) && $purchaseorder['ksoaccountid'] != $current['ksoaccountid'])
    $updatedrows['ksoaccountid'] = $purchaseorder['ksoaccountid'];
  if(isset($purchaseorder['ksodate']) && isdate($purchaseorder['ksodate']) && $purchaseorder['ksodate'] != $current['ksodate'])
    $updatedrows['ksodate'] = $purchaseorder['ksodate'];

  if(isset($purchaseorder['ski']) && $purchaseorder['ski'] != $current['ski'])
    $updatedrows['ski'] = $purchaseorder['ski'];
  if(isset($purchaseorder['skiaccountid']) && $purchaseorder['skiaccountid'] != $current['skiaccountid'])
    $updatedrows['skiaccountid'] = $purchaseorder['skiaccountid'];
  if(isset($purchaseorder['skidate']) && isdate($purchaseorder['skidate']) && $purchaseorder['skidate'] != $current['skidate'])
    $updatedrows['skidate'] = $purchaseorder['skidate'];

  if(isset($purchaseorder['clearance_fee']) && $purchaseorder['clearance_fee'] != $current['clearance_fee'])
    $updatedrows['clearance_fee'] = $purchaseorder['clearance_fee'];
  if(isset($purchaseorder['clearance_fee_accountid']) && $purchaseorder['clearance_fee_accountid'] != $current['clearance_fee_accountid'])
    $updatedrows['clearance_fee_accountid'] = $purchaseorder['clearance_fee_accountid'];
  if(isset($purchaseorder['clearance_fee_date']) && isdate($purchaseorder['clearance_fee_date']) && $purchaseorder['clearance_fee_date'] != $current['clearance_fee_date'])
    $updatedrows['clearance_fee_date'] = $purchaseorder['clearance_fee_date'];

  if(isset($purchaseorder['import_cost']) && $purchaseorder['import_cost'] != $current['import_cost'])
    $updatedrows['import_cost'] = $purchaseorder['import_cost'];
  if(isset($purchaseorder['import_cost_accountid']) && $purchaseorder['import_cost_accountid'] != $current['import_cost_accountid'])
    $updatedrows['import_cost_accountid'] = $purchaseorder['import_cost_accountid'];
  if(isset($purchaseorder['import_cost_date']) && isdate($purchaseorder['import_cost_date']) && $purchaseorder['import_cost_date'] != $current['import_cost_date'])
    $updatedrows['import_cost_date'] = $purchaseorder['import_cost_date'];

  if(isset($purchaseorder['handlingfeepaymentamount']) && $purchaseorder['handlingfeepaymentamount'] != $current['handlingfeepaymentamount'])
    $updatedrows['handlingfeepaymentamount'] = $purchaseorder['handlingfeepaymentamount'];
  if(isset($purchaseorder['handlingfeeaccountid']) && $purchaseorder['handlingfeeaccountid'] != $current['handlingfeeaccountid'])
    $updatedrows['handlingfeeaccountid'] = $purchaseorder['handlingfeeaccountid'];
  if(isset($purchaseorder['handlingfeedate']) && isdate($purchaseorder['handlingfeedate']) && $purchaseorder['handlingfeedate'] != $current['handlingfeedate'])
    $updatedrows['handlingfeedate'] = $purchaseorder['handlingfeedate'];

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

  // Validate payment
  $paymentdate = null;
  $paymentamount = 0;
  $payment_amount_in_currency = 0;
  $paymentaccountid = 0;
  $payments = [];
  for($i = 0 ; $i < 5 ; $i++){

    $n_paymentamount = ov("paymentamount-$i", $purchaseorder);
    $n_paymentcurrencyrate = ov("paymentcurrencyrate-$i", $purchaseorder);
    $n_paymentdate = ov("paymentdate-$i", $purchaseorder);
    $n_paymentaccountid = ov("paymentaccountid-$i", $purchaseorder);
    $n_totalamount = $n_paymentcurrencyrate * $n_paymentamount;

    if(!$n_paymentamount && !isdate($n_paymentdate) && !$n_paymentaccountid && !$n_totalamount) continue;

    $payments[] = [
      'amount'=>$n_paymentamount,
      'currencyrate'=>$n_paymentcurrencyrate,
      'date'=>$n_paymentdate,
      'chartofaccountid'=>$n_paymentaccountid,
      'totalamount'=>$n_totalamount,
    ];

    $paymentamount += $n_totalamount;
    $payment_amount_in_currency += $n_paymentamount;
    if(!$paymentdate) $paymentdate = $n_paymentdate;
    if(!$paymentaccountid) $paymentaccountid = $n_paymentaccountid;

  }
  $updatedrows['paymentamount'] = $paymentamount;
  if($paymentdate) $updatedrows['paymentdate'] = $paymentdate;
  if($paymentaccountid > 0) $updatedrows['paymentaccountid'] = $paymentaccountid;

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

    }
    $updatedrows['inventories'] = $purchaseorder['inventories'];

    pm("delete from purchaseorderpayment where purchaseorderid = ?", [ $id ]);
    foreach($payments as $payment){

      pm("insert into purchaseorderpayment (purchaseorderid, `type`, `date`, amount, chartofaccountid, currencyrate, totalamount)
        values (?, ?, ?, ?, ? ,?, ?)", [
        $id,
        1,
        $payment['date'],
        $payment['amount'],
        $payment['chartofaccountid'],
        $payment['currencyrate'],
        $payment['totalamount']
      ]);

    }
    $updatedrows['payments'] = $payments;

    userlog('purchaseordermodify', $current, $updatedrows, $_SESSION['user']['id'], $id);

    job_create_and_run('purchaseorder_ext', [ $id ]);

    inventory_purchaseorderqty();

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

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
      pm("DELETE FROM purchaseorder WHERE `id` = ?", array($id));
      userlog('purchaseorderremove', $purchaseorder, '', $_SESSION['user']['id'], $id);

      pdo_commit();

    }
    catch(Exception $ex){

      pdo_rollback();
      throw $ex;

    }

  }

}

function purchaseorder_ext($id){

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

  /**
   * Create journals
   */
  $journalvouchers = [];

  // Payment
  $total_payment = 0;
  $payments = $current['payments'];
  if(count($payments) > 0){

    foreach($payments as $index=>$payment){

      $paymentaccountid = $payment['paymentaccountid'];
      $paymentamount = $payment['paymentamount'];
      $paymenttotalamount = $payment['paymenttotalamount'];
      $paymentdate = $payment['paymentdate'];

      if($paymentaccountid > 0 && $paymenttotalamount > 0 && isdate($paymentdate)){
        $details = array();
        $details[] = array('coaid'=>$purchaseinvoice_downpaymentaccountid, 'debitamount'=>$paymenttotalamount, 'creditamount'=>0);
        $details[] = array('coaid'=>$paymentaccountid, 'debitamount'=>0, 'creditamount'=>$paymenttotalamount);
        $journalvoucher = array(
          'date'=>$paymentdate,
          'description'=>'Payment ' . ($index + 1) . ' for ' . $code,
          'ref'=>'PO',
          'refid'=>$id,
          'type'=>'A',
          'details'=>$details
        );
        $journalvouchers[] = $journalvoucher;
        $total_payment += $paymentamount;
      }

    }

  }

  // Tax
  $taxdate = ov('taxdate', $current);
  $taxaccountid = ov('taxaccountid', $current);
  $taxamount = ov('taxamount', $current);
  if($taxamount > 0 && isdate($taxdate) && $taxaccountid > 0){

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
  $pphdate = ov('pphdate', $current);
  $pphaccountid = ov('pphaccountid', $current);
  $pph = ov('pph', $current);
  if($pph > 0 && isdate($pphdate) && $pphaccountid > 0){

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
  $kso = ov('kso', $current);
  $ksodate = ov('ksodate', $current);
  $ksoaccountid = ov('ksoaccountid', $current);
  if($kso > 0 && isdate($ksodate) && $ksoaccountid > 0){

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
  $skidate = ov('skidate', $current);
  $skiaccountid = ov('skiaccountid', $current);
  if($ski > 0 && isdate($skidate) && $skiaccountid > 0){

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
  $clearance_fee = ov('clearance_fee', $current);
  $clearance_fee_date = ov('clearance_fee_date', $current);
  $clearance_fee_accountid = ov('clearance_fee_accountid', $current);
  if($clearance_fee > 0 && isdate($clearance_fee_date) && $clearance_fee_accountid > 0){

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
  $import_cost = ov('import_cost', $current);
  $import_cost_date = ov('import_cost_date', $current);
  $import_cost_accountid = ov('import_cost_accountid', $current);
  if($import_cost > 0 && isdate($import_cost_date) && $import_cost_accountid > 0){

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
  $handlingfeepaymentamount = ov('handlingfeepaymentamount', $current);
  $handlingfeedate = ov('handlingfeedate', $current);
  $handlingfeeaccountid = ov('handlingfeeaccountid', $current);
  if($handlingfeepaymentamount > 0 && isdate($handlingfeedate) && $handlingfeeaccountid > 0){

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

  $isbaddebt = ov('isbaddebt', $current);
  $baddebtdate = ov('baddebtdate', $current);
  $baddebtaccountid = ov('baddebtaccountid', $current);
  $baddebtamount = ov('baddebtamount', $current);
  if($isbaddebt && isdate($baddebtdate) && $baddebtaccountid > 0 && $baddebtamount > 0){

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

  if(count($journalvouchers) > 0)
    journalvoucherentries($journalvouchers);

  $total = $current['total'];
  if(abs($total_payment - $total) < 0.5)
    $updates['ispaid'] = 1;
  else
    $updates['ispaid'] = 0;
  mysql_update_row('purchaseorder', $updates, [ 'id'=>$current['id'] ]);

  if(function_exists('supplierpayablecalculate')) supplierpayablecalculate(array($supplierid));
  if(function_exists('inventory_purchaseorderqty')) inventory_purchaseorderqty();

}

function purchaseorder_check_journal($fix = false, $callback = null){

  // Check purchase order already paid and already invoiced
  $chartofaccountid = 18;
  $offset = 0;
  $limit = 1000;
  $total_amount_restored = 0;
  do{

    $rows = pmrs("select `id` from purchaseorder where ispaid = 1 and isinvoiced = 1 limit $limit offset $offset");

    foreach($rows as $row){

      // Check if down payment already credited
      $piid = pmc("select `id` from purchaseinvoice where purchaseorderid = ?", [ $row['id'] ]);
      $amount = pmc("select sum(debit - credit) from journalvoucher t1, journalvoucherdetail t2
        where t1.id = t2.jvid and ((t1.ref = 'PO' and t1.refid = ?) or (t1.ref = 'PI' and t1.refid = ?))
        and t2.coaid = ?", [ $row['id'], $piid, $chartofaccountid ]);
      if($amount > 0){
        if($fix) purchaseinvoice_ext($piid);
        if(is_callable($callback))
          call_user_func_array($callback, [ $row['id'] . ' ' . number_format($amount) ]);
        $total_amount_restored += $amount;
      }

    }

    $offset += $limit;

  }
  while($rows != null);
  chartofaccountrecalculate($chartofaccountid);
  if(is_callable($callback))
    call_user_func_array($callback, [ "Total amount restored " . number_format($total_amount_restored) ]);


}

?>