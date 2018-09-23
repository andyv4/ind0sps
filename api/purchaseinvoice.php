<?php
require_once dirname(__FILE__) . '/currency.php';
require_once dirname(__FILE__) . '/supplier.php';
require_once dirname(__FILE__) . '/purchaseorder.php';
require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/inventory.php';
require_once dirname(__FILE__) . '/system.php';
require_once dirname(__FILE__) . '/log.php';

$purchaseinvoice_payment_tolerance = 1000;
$purchaseinvoice_inventoryaccountid = 10; // Persediaan barang dagang
$purchaseinvoice_downpaymentaccountid = 18; // Uang muka pembelian
$purchaseinvoice_debtaccountid = 5; // Hutang
$purchaseinvoice_payment_tolerance_accountid = 1003;
$purchaseinvoice_columns = [
  'type'=>[ 'active'=>0, 'text'=>'Tipe', 'width'=>30, 'validation'=>'' ],
  'id'=>[ 'active'=>0, 'text'=>'Id', 'width'=>30 ],
  'option'=>[ 'active'=>1, 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'purchaseinvoicelist_options' ],
  'ispaid'=>[ 'active'=>1, 'text'=>'Lunas', 'width'=>30, 'type'=>'html', 'html'=>'purchaseinvoicelist_ispaid' ],
  'journal'=>[ 'active'=>0, 'text'=>'Jurnal', 'width'=>40, 'align'=>'center', 'type'=>'html', 'html'=>'grid_journaloption' ],
  'inventorybalance'=>[ 'active'=>0, 'text'=>'Mutasi', 'width'=>40, 'align'=>'center', 'type'=>'html', 'html'=>'grid_inventorybalanceoption' ],
  'code'=>[ 'active'=>1, 'text'=>'Kode', 'width'=>90 ],
  'date'=>[ 'active'=>1, 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date' ],
  'supplierid'=>[ 'active'=>0, 'text'=>'ID Supplier', 'width'=>30 ],
  'supplierdescription'=>[ 'active'=>1, 'text'=>'Nama Supplier', 'width'=>200, 'type'=>'html', 'html'=>'purchaseinvoicelist_supplierdescription' ],
  'address'=>[ 'active'=>0, 'text'=>'Alamat', 'width'=>100 ],
  'currencyid'=>[ 'active'=>0, 'text'=>'ID Mata Uang', 'width'=>30],
  'currencycode'=>[ 'active'=>1, 'text'=>'Kode Mata Uang', 'width'=>40],
  'currencyname'=>[ 'active'=>0, 'text'=>'Nama Mata Uang', 'width'=>60],
  'currencyrate'=>[ 'active'=>0, 'text'=>'Kurs', 'width'=>60, 'datatype'=>'money'],
  'subtotal'=>[ 'active'=>0, 'text'=>'Subtotal', 'width'=>100, 'datatype'=>'money'],
  'discount'=>[ 'active'=>0, 'text'=>'Diskon%', 'width'=>60, 'datatype'=>'number'],
  'discountamount'=>[ 'active'=>0, 'text'=>'Diskon', 'width'=>100, 'datatype'=>'money'],
  'taxable'=>[ 'active'=>0, 'text'=>'PPn', 'width'=>30, 'datatype'=>'number'],
  'freightcharge'=>[ 'active'=>0, 'text'=>'Freight Charge', 'width'=>100, 'datatype'=>'money'],
  'handlingfeeaccountid'=>[ 'active'=>0, 'text'=>'ID Akun Handling Fee', 'width'=>30, 'datatype'=>'number'],
  'handlingfeedate'=>[ 'active'=>0, 'text'=>'Tgl Handling Fee', 'width'=>100, 'datatype'=>'date'],
  'handlingfeeaccountname'=>[ 'active'=>0, 'text'=>'Akun Handling Fee', 'width'=>100],
  'handlingfeeamount'=>[ 'active'=>0, 'text'=>'Jumlah Handling Fee', 'width'=>100, 'datatype'=>'money'],
  'inventories'=>[ 'active'=>0, 'text'=>'Barang', 'width'=>100, 'datatype'=>'array_object', 'keys'=>'inventoryid,unitprice' ],
  'subtotal'=>[ 'active'=>0, 'text'=>'Subtotal', 'width'=>100, 'datatype'=>'money'],
  'total'=>[ 'active'=>1, 'text'=>'Total', 'width'=>100, 'datatype'=>'money'],
  'paymentaccountid'=>[ 'active'=>0, 'text'=>'ID Akun Pembayaran', 'width'=>30, 'datatype'=>'number'],
  'paymentaccountname'=>[ 'active'=>0, 'text'=>'Akun Pembayaran', 'width'=>100],
  'paymentdate'=>[ 'active'=>0, 'text'=>'Tgl Pembayaran', 'width'=>100, 'datatype'=>'date'],
  'paymentamount'=>[ 'active'=>0, 'text'=>'Jumlah Pembayaran', 'width'=>100, 'datatype'=>'money'],
  'note'=>[ 'active'=>0, 'text'=>'Catatan', 'width'=>100],
  'warehouseid'=>[ 'active'=>0, 'text'=>'ID Gudang', 'width'=>30, 'datatype'=>'int'],
  'purchaseorderid'=>[ 'active'=>0, 'text'=>'ID PO', 'width'=>30, 'datatype'=>'int'],
  'inventoryid'=>[ 'active'=>0, 'text'=>'ID Barang', 'width'=>30, 'datatype'=>'int'],
  'inventorycode'=>[ 'active'=>1, 'text'=>'Kode Barang', 'width'=>60],
  'inventorydescription'=>[ 'active'=>1, 'text'=>'Nama Barang', 'width'=>150, 'type'=>'html', 'html'=>'purchaseinvoicelist_inventorydescription'],
  'qty'=>[ 'active'=>1, 'text'=>'Kts', 'width'=>60, 'datatype'=>'number'],
  'unit'=>[ 'active'=>1, 'text'=>'Satuan', 'width'=>60],
  'unitprice'=>[ 'active'=>1, 'text'=>'Harga Satuan', 'width'=>60, 'datatype'=>'money'],
  'unitdiscount'=>[ 'active'=>1, 'text'=>'Diskon Barang%', 'width'=>60, 'datatype'=>'number'],
  'unitdiscountamount'=>[ 'active'=>1, 'text'=>'Diskon Barang', 'width'=>60, 'datatype'=>'money'],
  'unittotal'=>[ 'active'=>1, 'text'=>'Jumlah Barang', 'width'=>60, 'datatype'=>'money'],
  'unithandlingfee'=>[ 'active'=>1, 'text'=>'Jumlah Handling Fee', 'width'=>60, 'datatype'=>'money'],
  'createdon'=>[ 'active'=>0, 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'],

  'taxamount'=>[ 'active'=>0, 'text'=>'Jumlah PPn', 'width'=>100, 'datatype'=>'money'],
  'taxdate'=>[ 'active'=>0, 'text'=>'Tgl PPn', 'width'=>100, 'datatype'=>'date'],
  'taxaccountid'=>[ 'active'=>0, 'text'=>'ID Akun PPn', 'width'=>30, 'datatype'=>'number'],

  'import_cost'=>[ 'active'=>0, 'text'=>'Import Cost', 'width'=>100, 'datatype'=>'money'],
  'import_cost_date'=>[ 'active'=>0, 'text'=>'Tgl Import', 'width'=>100, 'datatype'=>'date'],
  'import_cost_accountid'=>[ 'active'=>0, 'text'=>'ID Import Cost', 'width'=>30, 'datatype'=>'number'],

];

function purchaseinvoice_uicolumns(){

  global $purchaseinvoice_columns;
  $columns = ui_columns($purchaseinvoice_columns);
  return $columns;

}
function purchaseinvoicecode($date, $taxable, $release = ''){

  $taxable_code = systemvarget('purchaseinvoice_tax_code');
  $non_taxable_code = systemvarget('purchaseinvoice_nontax_code');
  $type_code = $taxable && !empty(trim($taxable_code)) ? $taxable_code : $non_taxable_code;
  $type_code2 = $taxable && !empty(trim($taxable_code)) ? 'PIT' : 'PIN';

  if($release) code_release($release);

  $code = code_reserve(
    $type_code2,
    date('Y', strtotime($date)),
    $type_code
  );

  return $code;

}
function purchaseinvoiceexists($params){

  $row = mysql_get_row('purchaseinvoice', $params, null);
  return !$row ? 0 : $row['id'];

}
function purchaseinvoicedetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $purchaseinvoice = mysql_get_row('purchaseinvoice', $filters, $columns);

  if($purchaseinvoice){

    $inventories = pmrs("select t1.*, t2.taxable from purchaseinvoiceinventory t1, inventory t2 where t1.purchaseinvoiceid = ? and t1.inventoryid = t2.id", [ $purchaseinvoice['id'] ]);

    $purchaseinvoice['inventories'] = $inventories;
    $handlingfeepaymentaccount = chartofaccountdetail(null, array('id'=>$purchaseinvoice['handlingfeeaccountid']));

    $purchaseinvoice['currencyname'] = currencydetail(null, array('id'=>$purchaseinvoice['currencyid']))['name'];
    $purchaseinvoice['warehousename'] = warehousedetail(null, array('id'=>$purchaseinvoice['warehouseid']))['name'];
    $purchaseinvoice['paymentaccountname'] = chartofaccountdetail(null, array('id'=>$purchaseinvoice['paymentaccountid']))['name'];
    $purchaseinvoice['handlingfeeaccountname'] = isset($handlingfeepaymentaccount['name']) ? $handlingfeepaymentaccount['name'] : '';

    // Extract purchase order detail
    if($purchaseinvoice['purchaseorderid'] > 0){
      $purchaseorder = purchaseorderdetail(null, array('id'=>$purchaseinvoice['purchaseorderid']));
      if(is_array($purchaseorder)){
        $purchaseinvoice['pocode'] = $purchaseorder['code'];
        $purchaseinvoice['downpaymentamount'] = $purchaseorder['paymentamount'];
        $purchaseinvoice['downpaymentdate'] = $purchaseorder['paymentdate'];
        $purchaseinvoice['downpaymentaccountid'] = $purchaseorder['paymentaccountid'];
      }
    }
  }

  return $purchaseinvoice;
  
}
function purchaseinvoicelist($columns, $sorts, $filters, $limits, $groups = null){

  $purchaseinvoice_columnaliases = array(
    'id'=>'t1.id!',
    'ispaid'=>'t1.ispaid',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'supplierid'=>'t1.supplierid!',
    'currencyid'=>'t1.currencyid!',
    'paymentaccountid'=>'t1.paymentaccountid!',
    'supplierdescription'=>'t1.supplierdescription',
    'address'=>'t1.address',
    'currencycode'=>'(select code from currency where `id` = t1.currencyid)',
    'currencyname'=>'(select `name` from currency where `id` = t1.currencyid)',
    'currencyrate'=>'t1.currencyrate',
    'subtotal'=>'t1.subtotal',
    'discount'=>'t1.discount',
    'discountamount'=>'t1.discountamount',
    'taxable'=>'t1.taxable!',
    'taxamount'=>'t1.taxamount',
    'freightcharge'=>'t1.freightcharge',
    'handlingfeeaccountname'=>'(select `name` from chartofaccount where `id` = t1.handlingfeeaccountid)',
    'handlingfeeamount'=>'t1.handlingfeeamount',
    'handlingfeeaccountid'=>'t1.handlingfeeaccountid!',
    'total'=>'t1.total',
    'paymentaccountname'=>'(select `name` from chartofaccount where `id` = t1.paymentaccountid)',
    'paymentdate'=>'t1.paymentdate',
    'paymentamount'=>'t1.paymentamount',
    'note'=>'t1.note',
    'inventoryid'=>'t2.inventoryid!',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'unitdiscount'=>'t2.unitdiscount',
    'unitdiscountamount'=>'t2.unitdiscountamount',
    'unittotal'=>'t2.unittotal',
    'unithandlingfee'=>'t2.unithandlingfee',
    'createdon'=>'t1.createdon',
    'type'=>"'purchaseinvoice'!",
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $purchaseinvoice_columnaliases);
  $wherequery = 'WHERE t1.id = t2.purchaseinvoiceid' .
    str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $purchaseinvoice_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $purchaseinvoice_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  if(is_array($groups) && count($groups) > 0){

    if(count($groups) > 0){

      if($groups[count($groups) - 1]['name'] == 'code'){
        $pivot_group = $groups[count($groups) - 1];
        $pivot_group_query = groupquery_from_groups([ $pivot_group ], $purchaseinvoice_columnaliases);
      }

      $group = $groups[0];
      $columnquery = columnquery_from_columnaliases($columns, $purchaseinvoice_columnaliases);
      $group_query = groupquery_from_groups([ $group ], $purchaseinvoice_columnaliases);
      $group_column = groupcolumn_from_group($group, $purchaseinvoice_columnaliases);

      $query = "SELECT $group_column FROM (
        SELECT $columnquery FROM purchaseinvoice t1, purchaseinvoiceinventory t2 $wherequery $pivot_group_query
      ) as s1 $group_query order by $group[name]";
      $data = pmrs($query, $params);

    }

  }
  else{

    $query = "SELECT $columnquery FROM purchaseinvoice t1, purchaseinvoiceinventory t2 $wherequery $sortquery $limitquery";
    $data = pmrs($query, $params);

  }

  return $data;

}
function purchaseinvoicetotal($type){

  switch($type){
    case 'today':
      $total = floatval(pmc("SELECT SUM(`total` * currencyrate) FROM purchaseinvoice WHERE `date` = ?", array(date('Ymd'))));
      break;
    case 'thisweek':
      $total = floatval(pmc("SELECT SUM(`total` * currencyrate) FROM purchaseinvoice WHERE WEEK(`date`)= ?", array(array(date('W') - 1))));
      break;
    case 'thismonth':
      $total = floatval(pmc("SELECT SUM(`total` * currencyrate) FROM purchaseinvoice WHERE MONTH(`date`) = ?", array(date('m'))));
      break;
    case 'thisyear':
      $total = floatval(pmc("SELECT SUM(`total` * currencyrate) FROM purchaseinvoice WHERE YEAR(`date`) = ?", array(date('Y'))));
      break;
  }

  return $total;

}
function purchaseinvoiceremovable($params){

  $deletable = false;
  $purchaseinvoice = purchaseinvoicedetail(null, $params);
  if($purchaseinvoice){
    $deletable = true;
  }
  return $deletable;

}

/**
 * Validate purchase invoice object
 * - Replace inventory code, inventory description & unit with database
 * @param $updated
 * @param null $original
 * @throws Exception
 */
function purchaseinvoicevalidate(&$updated, $original = null){

  $date = ova('date', $updated, $original);
  $currencyid = ova('currencyid', $updated, $original);
  $currencyrate = ova('currencyrate', $updated, $original);
  $inventories = ova('inventories', $updated, $original);
  $discountamount = ova('discountamount', $updated, $original);
  $freightcharge = ova('freightcharge', $updated, $original);
  $taxable = ova('taxable', $updated, $original);
  $actual_subtotal = 0;
  foreach($inventories as $index=>$inventory){
    if(isset($inventory['__flag']) && $inventory['__flag'] == 'removed') continue; // Skip removed row
    $unittotal = $inventory['unittotal'];
    $actual_subtotal += $unittotal;
  }
  $actual_total = $actual_subtotal - $discountamount + $freightcharge;

  $downpaymentamount = ov('downpaymentamount', $original, 0);
  $total_in_currency = $actual_total * $currencyrate;
  $remaining_amount = $total_in_currency - $downpaymentamount;
  if($remaining_amount < 0) $remaining_amount = 0;

  if(isset($updated['code']) && $updated['code'] != ov('code', $original)){
    if(pmc("select count(*) from purchaseinvoice where code = ?", [ $updated['code'] ]) > 0) exc('Nomor faktur sudah dipakai');
  }

  if(isset($updated['supplierdescription']) && $updated['supplierdescription'] != ov('supplierdescription', $original)){
    if(!($supplier = supplierdetail(null, array('description'=>$updated['supplierdescription'])))) exc('Supplier tidak terdaftar');
    $updated['supplierid'] = $supplier['id'];
    $updated['supplierdescription'] = $supplier['description'];
  }

  if(isset($updated['currencyid']) && $updated['currencyid'] != ov('currencyid', $original)){
    if(!($currency = currencydetail(null, [ 'id'=>$updated['currencyid'] ]))) exc("Mata uang tidak terdaftar");
  }

  if(isset($updated['warehouseid']) && $updated['warehouseid'] != ov('warehouseid', $original)){
    if(!($warehouse = warehousedetail(null, [ 'id'=>$updated['warehouseid'] ]))) exc("Gudang tidak terdaftar");
  }

  if(isset($updated['currencyrate']) && $updated['currencyrate'] != ov('currencyrate', $original)){
    if($updated['currencyrate'] <= 0) exc("Nilai tukar harus diisi");
  }

  if(isset($updated['subtotal']) && $updated['subtotal'] != ov('subtotal', $original)){
    if(!money_is_equal($updated['subtotal'], $actual_subtotal, currency_epsilon($currencyid))) exc("Subtotal salah, silakan kalkulasi ulang total: [{$updated[subtotal]}:{$actual_subtotal}]");
  }

  if(isset($updated['total']) && $updated['total'] != ova('total', $updated, $original)){
    if(!money_is_equal($updated['total'], $actual_total, currency_epsilon($currencyid))) exc("Total salah, silakan kalkulasi ulang total: [{$updated[$actual_total]}:{$actual_total}]");
  }

  if(isset($updated['paymentamount']) && $updated['paymentamount'] != ov('paymentamount', $original)){

    $paymentdate = ova('paymentdate', $updated, $original);
    $paymentaccountid = ova('paymentaccountid', $updated, $original);
    $paymentamount = ova('paymentamount', $updated, $original);

    if($paymentamount > $remaining_amount) exc("Jumlah pembayaran melebihi total yang harus dibayar.");
    if(!isdate($paymentdate)) exc("Tanggal pelunasan belum diisi.");
    if(!chartofaccount_id_exists($paymentaccountid)) exc("Akun pelunasan belum diisi.");
    if($updated['paymentamount'] > 0 && $paymentdate < $date) exc("Tanggal pelunasan salah.");

  }

  if(isset($updated['taxamount']) && $updated['taxamount'] != ov('taxamount', $original)){

    $taxdate = ova('taxdate', $updated, $original);
    $taxaccountid = ova('taxaccountid', $updated, $original);
    $taxamount = ova('taxamount', $updated, $original);

    if(!isdate($taxdate)) exc("Tanggal PPn belum diisi.");
    if(!chartofaccount_id_exists($taxaccountid)) exc("Akun PPn belum diisi.");
    if($taxamount < 0) exc("Jumlah PPn salah.");

  }

  if(isset($updated['pph']) && $updated['pph'] != ov('pph', $original)){

    $pphdate = ova('pphdate', $updated, $original);
    $pphaccountid = ova('pphaccountid', $updated, $original);
    $pph = ova('pph', $updated, $original);

    if(!isdate($pphdate)) exc("Tanggal PPH belum diisi.");
    if(!chartofaccount_id_exists($pphaccountid)) exc("Akun PPH belum diisi.");
    if($pph < 0) exc("Jumlah PPH salah.");

  }

  if(isset($updated['kso']) && $updated['kso'] != ov('kso', $original)){

    $ksodate = ova('ksodate', $updated, $original);
    $ksoaccountid = ova('ksoaccountid', $updated, $original);
    $kso = ova('kso', $updated, $original);

    if(!isdate($ksodate)) exc("Tanggal KSO belum diisi.");
    if(!chartofaccount_id_exists($ksoaccountid)) exc("Akun KSO belum diisi.");
    if($kso < 0) exc("Jumlah KSO salah.");

  }

  if(isset($updated['ski']) && $updated['ski'] != ov('ski', $original)){

    $skidate = ova('skidate', $updated, $original);
    $skiaccountid = ova('skiaccountid', $updated, $original);
    $ski = ova('ski', $updated, $original);

    if(!isdate($skidate)) exc("Tanggal SKI belum diisi.");
    if(!chartofaccount_id_exists($skiaccountid)) exc("Akun SKI belum diisi.");
    if($ski < 0) exc("Jumlah SKI salah.");

  }

  if(isset($updated['clearance_fee']) && $updated['clearance_fee'] != ov('clearance_fee', $original)){

    $clearance_fee_date = ova('clearance_fee_date', $updated, $original);
    $clearance_fee_accountid = ova('clearance_fee_accountid', $updated, $original);
    $clearance_fee = ova('clearance_fee', $updated, $original);

    if(!isdate($clearance_fee_date)) exc("Tanggal clearance fee belum diisi.");
    if(!chartofaccount_id_exists($clearance_fee_accountid)) exc("Akun clearance fee belum diisi.");
    if($clearance_fee < 0) exc("Jumlah clearance fee salah.");

  }

  if(isset($updated['import_cost']) && $updated['import_cost'] != ov('import_cost', $original)){

    $import_cost_date = ova('import_cost_date', $updated, $original);
    $import_cost_accountid = ova('import_cost_accountid', $updated, $original);
    $import_cost = ova('import_cost', $updated, $original);

    if(!isdate($import_cost_date)) exc("Tanggal bea masuk belum diisi.");
    if(!chartofaccount_id_exists($import_cost_accountid)) exc("Akun bea masuk belum diisi.");
    if($import_cost < 0) exc("Jumlah bea masuk salah.");

  }

  if(isset($updated['handlingfeepaymentamount']) && floatval($updated['handlingfeepaymentamount']) != floatval(ov('handlingfeepaymentamount', $original))){

    $handlingfeedate = ova('handlingfeedate', $updated, $original);
    $handlingfeeaccountid = ova('handlingfeeaccountid', $updated, $original);
    $handlingfeepaymentamount = ova('handlingfeepaymentamount', $updated, $original);

    if(!isdate($handlingfeedate)) exc("Tanggal handling fee belum diisi.");
    if(!chartofaccount_id_exists($handlingfeeaccountid)) exc("Akun handling fee belum diisi.");
    if($handlingfeepaymentamount < 0) exc("Jumlah handling fee salah.");
    if($handlingfeedate < $date) exc("Tanggal handling fee salah.");

  }

  if(isset($updated['inventories'])){

    if(!is_array_object($updated['inventories']) || count($updated['inventories']) <= 0) exc("Barang harus diisi");
    $inventory_count = 0;

    foreach($updated['inventories'] as $index=>$inventory){

      if(isset($inventory['__flag']) && $inventory['__flag'] == 'removed') continue; // Skip removed row

      $inventorycode = ov('inventorycode', $inventory);
      if(!($inventory_data = inventorydetail(null, array('code'=>$inventorycode)))) exc("Barang $inventorycode tidak terdaftar");
      $inventoryid = $inventory_data['id'];
      $inventorycode = $inventory_data['code'];
      $inventorydescription = $inventory_data['description'];
      $qty = ov('qty', $inventory);
      if(!$qty) exc("Kuantitas di baris ke " . ($index + 1) . " salah");
      $unit = ov('unit', $inventory);
      $unitprice = ov('unitprice', $inventory);
      if(!$unitprice) exc("Harga di baris ke " . ($index + 1) . " salah");
      $unittotal = ov('unittotal', $inventory);
      $unitcostprice = ov('unitcostprice', $inventory, 0, 0);
      $unittax = ov('unittax', $inventory, 0, 0);

      $actual_unittotal = $qty * $unitprice;
      if(round($unittotal) != round($actual_unittotal)) exc("Total $inventorycode salah. $unittotal : $actual_unittotal ");
      if(!$unitcostprice) exc("Harga modal $inventorycode salah");
      if($taxable && !$inventory_data['taxable']) exc("Barang $inventorycode tidak kena pajak, tidak dapat dimasukkan kedalam faktur pajak");

      $updated['inventories'][$index]['inventoryid'] = $inventoryid;
      $updated['inventories'][$index]['inventorycode'] = $inventorycode;
      $updated['inventories'][$index]['inventorydescription'] = $inventorydescription;
      $updated['inventories'][$index]['unit'] = $unit;
      $inventory_count++;

    }
    if($inventory_count <= 0) exc("Barang harus diisi");

  }

}

function purchaseinvoiceentry($purchaseinvoice){

  $fp = acquire_lock(__FUNCTION__);
  purchaseinvoicevalidate($purchaseinvoice);

  // Check if purchase order id supplied is valid
  if($purchaseinvoice['purchaseorderid'] > 0){
    $purchaseinvoiceexists = intval(pmc("SELECT COUNT(*) FROM purchaseinvoice WHERE purchaseorderid = ?", array($purchaseinvoice['purchaseorderid'])));
    if($purchaseinvoiceexists) throw new Exception('Faktur pembelian untuk pesanan ini sudah ada.');
  }
  if(!$purchaseinvoice['purchaseorderid']) $purchaseinvoice['purchaseorderid'] = null; // Set purchaseorderid to null

  // Automatically set purchase invoice code to new one if current code already used
  if(pmc("select count(*) from purchaseinvoice where `code` = ?", [ $purchaseinvoice['code'] ]) > 0) $code = purchaseinvoicecode($purchaseinvoice['date'], $purchaseinvoice['taxable']);

  $purchaseinvoice['createdon'] = $purchaseinvoice['lastupdatedon'] = date('YmdHis');
  $purchaseinvoice['createdby'] = $_SESSION['user']['id'];

  $id = mysql_insert_row('purchaseinvoice', $purchaseinvoice);

  /**
   * Save inventories
   */
  $inventories = $purchaseinvoice['inventories'];

  // Group inventory by code and unitprice
  if(systemvarget('purchaseinvoice_item_grouping')){
    $inventories = array_index($inventories, ['inventorycode', 'unitprice']);
    $temp = [];
    foreach ($inventories as $inventorycode => $inventorycodes) {
      foreach ($inventorycodes as $unitprice => $inventoryunits) {
        $qty = 0;
        foreach ($inventoryunits as $inventoryunit)
          $qty += $inventoryunit['qty'];
        $inventoryunit = $inventoryunits[0];
        $inventoryunit['qty'] = $qty;
        $temp[] = $inventoryunit;
      }
    }
    $inventories = $temp;
  }

  $values = $params = [];
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventory = $inventories[$i];
    $inventoryid = $inventory['inventoryid'];
    $inventorycode = $inventory['inventorycode'];
    $inventorydescription = $inventory['inventorydescription'];
    $qty = $inventory['qty'];
    $unit = $inventory['unit'];
    $unitprice = $inventory['unitprice'];
    $unittotal = $qty * $unitprice;
    $unitdiscount = 0;
    $unitdiscountamount = 0;
    $unittotal = $unittotal - $unitdiscountamount;
    $unithandlingfee = 0;
    $unitcostprice = $inventory['unitcostprice'];
    $unitcostpriceflag = $inventory['unitcostpriceflag'];
    $unittax = $inventory['unittax'];

    $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ? , ?, ?, ?, ?, ?)";
    array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, $unitdiscount,
      $unitdiscountamount, $unittotal, $unithandlingfee, $unitcostprice, $unitcostpriceflag, $unittax);
  }
  try{
    pm("INSERT INTO purchaseinvoiceinventory(purchaseinvoiceid, inventoryid, inventorycode, inventorydescription, qty, 
      unit, unitprice, unitdiscount, unitdiscountamount, unittotal, unithandlingfee, unitcostprice, unitcostpriceflag, unittax) VALUES " . implode(', ', $values),
      $params);
  }
  catch(Exception $ex){
    purchaseinvoiceremove([ 'id'=>$id ]);
    throw $ex;
  }

  // Update purchase order
  if($purchaseinvoice['purchaseorderid'] > 0) pm("UPDATE purchaseorder SET isinvoiced = 1 WHERE `id` = ?", array($purchaseinvoice['purchaseorderid']));

  // Commit current code
  code_commit($purchaseinvoice['code']);

  // Process inventory balance & journal
  try{ purchaseinvoicecalculate($id); }catch(Exception $ex){ purchaseinvoiceremove([ 'id'=>$id ]); throw $ex; }

  // Save user log
  userlog('purchaseinvoiceentry', $purchaseinvoice, '', $_SESSION['user']['id'], $id);

  require_worker();

  release_lock($fp, __FUNCTION__);

  return array('id'=>$id);
  
}

function purchaseinvoicemodify($purchaseinvoice){

  global $purchaseinvoice_columns;

  $id = ov('id', $purchaseinvoice, 1);
  $current = purchaseinvoicedetail(null, array('id'=>$id));
  if(!$current) throw new Exception("Invoice tidak ada.");

  $updatedrows = [];

  if(isset($purchaseinvoice['supplierdescription'])){
    $updatedrows['supplierid'] = supplierdetail(null, array('description'=>$purchaseinvoice['supplierdescription']))['id'];
    $updatedrows['supplierdescription'] = $purchaseinvoice['supplierdescription'];
  }
  if(isset($purchaseinvoice['date']) && $purchaseinvoice['date'] != $current['date']){
    if(!isdate($purchaseinvoice['date'])) exc('Format tanggal salah');
    $updatedrows['date'] = ov('date', $purchaseinvoice, 1, array('type'=>'date'));
  }
  if(isset($purchaseinvoice['address']) && $purchaseinvoice['address'] != $current['address']){
    $updatedrows['address'] = $purchaseinvoice['address'];
  }
  if(isset($purchaseinvoice['currencyid']) && $purchaseinvoice['currencyid'] != $current['currencyid']){
    $updatedrows['currencyid'] = $purchaseinvoice['currencyid'];
  }
  if(isset($purchaseinvoice['currencyrate']) && $purchaseinvoice['currencyrate'] != $current['currencyrate']){
    $updatedrows['currencyrate'] = $purchaseinvoice['currencyrate'];
  }
  if(isset($purchaseinvoice['discount']) && $purchaseinvoice['discount'] != $current['discount']){
    $updatedrows['discount'] = $purchaseinvoice['discount'];
  }
  if(isset($purchaseinvoice['discountamount']) && $purchaseinvoice['discountamount'] != $current['discountamount']){
    $updatedrows['discountamount'] = $purchaseinvoice['discountamount'];
  }
  if(isset($purchaseinvoice['term']) && $purchaseinvoice['term'] != $current['term']){
    $updatedrows['term'] = $purchaseinvoice['term'];
  }
  if(isset($purchaseinvoice['note']) && $purchaseinvoice['note'] != $current['note']){
    $updatedrows['note'] = $purchaseinvoice['note'];
  }

  if(isset($purchaseinvoice['ispaid']) && $purchaseinvoice['ispaid'] != $current['ispaid'])
    $updatedrows['ispaid'] = $purchaseinvoice['ispaid'];

  if(isset($purchaseinvoice['paymentamount']) && $purchaseinvoice['paymentamount'] != $current['paymentamount'])
    $updatedrows['paymentamount'] = $purchaseinvoice['paymentamount'];

  if(isset($purchaseinvoice['paymentdate']) && $purchaseinvoice['paymentdate'] != $current['paymentdate'])
    $updatedrows['paymentdate'] = $purchaseinvoice['paymentdate'];

  if(isset($purchaseinvoice['paymentaccountid']) && $purchaseinvoice['paymentaccountid'] != $current['paymentaccountid'])
    $updatedrows['paymentaccountid'] = $purchaseinvoice['paymentaccountid'];

  if(isset($purchaseinvoice['inventories'])){

    $inventories = $purchaseinvoice['inventories'];
    $updatedrows['inventories'] = $inventories; // Add inventories to updated rows, always appear in log

    // Group inventory by code and unitprice
    if(systemvarget('purchaseinvoice_item_grouping')){
      $inventories = array_index($inventories, ['inventorycode', 'unitprice']);
      $temp = [];
      foreach ($inventories as $inventorycode => $inventorycodes) {
        foreach ($inventorycodes as $unitprice => $inventoryunits) {
          $qty = 0;
          foreach ($inventoryunits as $inventoryunit)
            $qty += $inventoryunit['qty'];
          $inventoryunit = $inventoryunits[0];
          $inventoryunit['qty'] = $qty;
          $temp[] = $inventoryunit;
        }
      }
      $inventories = $temp;
    }

    // Insert to database
    $queries = $params = [];
    $queries[] = "DELETE FROM purchaseinvoiceinventory WHERE purchaseinvoiceid = ?";
    $params[] = $id;
    foreach($inventories as $inventory){

      if(isset($inventory['__flag']) && $inventory['__flag'] == 'removed') continue; // Skip removed row

      $inventoryid = $inventory['inventoryid'];
      $inventorycode = $inventory['inventorycode'];
      $inventorydescription = $inventory['inventorydescription'];
      $qty = $inventory['qty'];
      $unit = $inventory['unit'];
      $unitprice = $inventory['unitprice'];
      $unittotal = $inventory['unittotal'];
      $unittax = $inventory['unittax'];
      $unitcostprice = $inventory['unitcostprice'];
      $unitcostpriceflag = $inventory['unitcostpriceflag'];

      $queries[] = "INSERT INTO purchaseinvoiceinventory(`id`, purchaseinvoiceid, inventoryid, inventorycode, inventorydescription, 
        qty, unit, unitprice, unittotal, unitcostprice, unitcostpriceflag, unittax) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, null, $id, $inventoryid, $inventorycode, $inventorydescription,
        $qty, $unit, $unitprice, $unittotal, $unitcostprice, $unitcostpriceflag, $unittax);

    }
    if(count($queries) > 0)
      pm(implode(';', $queries), $params);

  }
  
  if(count($updatedrows) > 0){
    $updatedrows['lastupdatedon'] = date('YmdHis');
    mysql_update_row('purchaseinvoice', $updatedrows, [ 'id'=>$id ]);
  }

  if(isset($updatedrows['code'])) code_commit($updatedrows['code'], $current['code']);

  purchaseinvoicecalculate($id);

  userlog('purchaseinvoicemodify', $current, $updatedrows, $_SESSION['user']['id'], $id);

  require_worker();

  return array('id'=>$id);

}
function purchaseinvoiceremove($filters){

	$purchaseinvoice = purchaseinvoicedetail(null, $filters);
 	if($purchaseinvoice){
 	  $id = $purchaseinvoice['id'];
    $code = $purchaseinvoice['code'];
    $taxable = $purchaseinvoice['taxable'];

    $lock_file = __DIR__ . "/../usr/system/purchaseinvoice_remove_$id.lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat hapus faktur, silakan ulangi beberapa saat lagi.');

    journalvoucherremove(array('ref'=>'PI', 'refid'=>$id));
    inventorybalanceremove(array('ref'=>'PI', 'refid'=>$id));

    $query = "DELETE FROM purchaseinvoice WHERE `id` = ?";
    pm($query, array($id));

    if($purchaseinvoice['purchaseorderid']){
      pm("UPDATE purchaseorder SET isinvoiced = 0 WHERE `id` = ?", array($purchaseinvoice['purchaseorderid']));
      purchaseordercalculate($purchaseinvoice['purchaseorderid']);
    }

    if(!$taxable) code_remove($code); // Remove reservation if sales invoice is non-taxable

    userlog('purchaseinvoiceremove', $purchaseinvoice, '', $_SESSION['user']['id'], $id);

    require_worker();

    fclose($fp);
    unlink($lock_file);

 	}
 	else
      throw new Exception('Faktur pembelian telah dihapus.');

}

function purchaseinvoicecalculate($id){

  global $purchaseinvoice_inventoryaccountid, $purchaseinvoice_downpaymentaccountid, $purchaseinvoice_debtaccountid,
         $purchaseinvoice_payment_tolerance_accountid, $purchaseinvoice_payment_tolerance;

  $purchaseinvoice = purchaseinvoicedetail(null, array('id'=>$id));
  if(!$purchaseinvoice) return;

  $date = $purchaseinvoice['date'];
  $supplierdescription = $purchaseinvoice['supplierdescription'];
  $inventories = $purchaseinvoice['inventories'];
  $warehouseid = $purchaseinvoice['warehouseid'];
  $code = $purchaseinvoice['code'];
  $currencyrate = $purchaseinvoice['currencyrate'];
  $paymentaccountid = ov('paymentaccountid', $purchaseinvoice);
  $paymentdate = ov('paymentdate', $purchaseinvoice);
  $paymentamount = ov('paymentamount', $purchaseinvoice);
  $total = ov('total', $purchaseinvoice);
  $ispaid = ov('ispaid', $purchaseinvoice);
  $totalpercurrency = round($total * $currencyrate);
  $handlingfeeaccountid = $purchaseinvoice['handlingfeeaccountid'];
  $handlingfeedate = $purchaseinvoice['handlingfeedate'];
  $handlingfeepaymentamount = $purchaseinvoice['handlingfeepaymentamount'];

  $purchaseorderid = ov('purchaseorderid', $purchaseinvoice, 0, null);
  $purchaseorder = purchaseorderdetail(null, array('id'=>$purchaseorderid));
  $downpaymentamount = ov('paymentamount', $purchaseorder, 0);

  $tax_paid = isset($purchaseorder['taxamount']) && $purchaseorder['taxamount'] > 0;
  $pph_paid = isset($purchaseorder['pph']) && $purchaseorder['pph'] > 0;
  $kso_paid = isset($purchaseorder['kso']) && $purchaseorder['kso'] > 0;
  $ski_paid = isset($purchaseorder['ski']) && $purchaseorder['ski'] > 0;
  $cf_paid = isset($purchaseorder['clearance_fee']) && $purchaseorder['clearance_fee'] > 0;
  $ic_paid = isset($purchaseorder['import_cost']) && $purchaseorder['import_cost'] > 0;
  $hf_paid = isset($purchaseorder['handlingfeepaymentamount']) && $purchaseorder['handlingfeepaymentamount'] > 0;

  /* Calculate cost price */
  /*$currencyrate = floatval($purchaseinvoice['currencyrate']);
  $subtotal = 0;
  foreach($inventories as $inventory){
    $unit_total = floatval($inventory['unittotal']);
    $subtotal += $unit_total;
  }
  $discountamount = floatval($purchaseinvoice['discountamount']);
  $taxamount = floatval($purchaseinvoice['taxamount']);
  $freightcharge = floatval($purchaseinvoice['freightcharge']);
  $pph = floatval($purchaseinvoice['pph']);
  $kso = floatval($purchaseinvoice['kso']);
  $ski = floatval($purchaseinvoice['ski']);
  $clearance_fee = floatval($purchaseinvoice['clearance_fee']);
  $handlingfeepaymentamount = floatval($purchaseinvoice['handlingfeepaymentamount']);*/

  /**
   * Inventory Balance
   */
  foreach($inventories as $inventory){

    $purchaseinvoiceinventoryid = $inventory['id'];
    $inventoryid = $inventory['inventoryid'];
    $qty = $inventory['qty'];
    $unitcostprice = $inventory['unitcostprice'];

    $inventorybalances[] = [
      'ref'=>'PI',
      'refid'=>$id,
      'refitemid'=>$purchaseinvoiceinventoryid,
      'date'=>$date,
      'description'=>$supplierdescription,
      'inventoryid'=>$inventoryid,
      'warehouseid'=>$warehouseid,
      'in'=>$qty,
      'amount'=>$qty * $unitcostprice,
      'createdon'=>date('YmdHis')
    ];

  }
  inventorybalanceremove(array('ref'=>'PI', 'refid'=>$id));
  inventorybalanceentries($inventorybalances);

  $debtamount = $totalpercurrency - $downpaymentamount - $paymentamount;
  if($debtamount < 1) $debtamount = 0; // If debtamount < 1, consider as paid
  if($ispaid) $debtamount = 0;

  /**
   * Journal
   */
  $journalvouchers = [];

  // Default journal
  $details = [];
  if($paymentamount > 0){

    $details[] = array('coaid' => $purchaseinvoice_inventoryaccountid, 'debitamount' => $paymentamount + $downpaymentamount + $debtamount, 'creditamount' => 0); // 10: Persediaan Barang Dagang
    if ($downpaymentamount > 0)
      $details[] = array('coaid' => $purchaseinvoice_downpaymentaccountid, 'debitamount' => 0, 'creditamount' => $downpaymentamount); // 18: Uang Muka Pembelian
    $details[] = array('coaid' => $paymentaccountid, 'debitamount' => 0, 'creditamount' => $paymentamount); // As of payment account id
    if ($debtamount > 0) $details[] = array('coaid'=>$purchaseinvoice_debtaccountid, 'debitamount'=>0, 'creditamount'=>$debtamount ); // 5: Hutang

    $journalvoucher = array(
      'date' => $paymentdate,
      'description' => $code,
      'ref' => 'PI',
      'refid' => $id,
      'type' => 'A',
      'details' => $details
    );
    $journalvouchers[] = $journalvoucher;

  }

  // Tax
  $taxamount = ov('taxamount', $purchaseinvoice);
  if($taxamount > 0 && !$tax_paid){
    $taxdate = ov('taxdate', $purchaseinvoice);
    $taxaccountid = ov('taxaccountid', $purchaseinvoice);
    $taxdebitaccountid = systemvarget('purchaseinvoice_taxaccountid');

    $details = [];
    $details[] =  array('coaid'=>$taxdebitaccountid, 'debitamount'=>$taxamount, 'creditamount'=>0);
    $details[] =  array('coaid'=>$taxaccountid, 'debitamount'=>0, 'creditamount'=>$taxamount);
    $journalvoucher = array(
      'date'=>$taxdate,
      'description'=>$code . " PPn",
      'ref'=>'PI',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;
  }

  // PPh
  $pph = ov('pph', $purchaseinvoice);
  if($pph > 0 && !$pph_paid){
    $pphdate = ov('pphdate', $purchaseinvoice);
    $pphaccountid = ov('pphaccountid', $purchaseinvoice);
    $pphdebitaccountid = systemvarget('purchaseinvoice_pphaccountid');

    $details = [];
    $details[] =  array('coaid'=>$pphdebitaccountid, 'debitamount'=>$pph, 'creditamount'=>0);
    $details[] =  array('coaid'=>$pphaccountid, 'debitamount'=>0, 'creditamount'=>$pph);
    $journalvoucher = array(
      'date'=>$pphdate,
      'description'=>$code . " PPH",
      'ref'=>'PI',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;
  }

  // KSO
  $kso = ov('kso', $purchaseinvoice);
  if($kso > 0 && !$kso_paid){
    $ksodate = ov('ksodate', $purchaseinvoice);
    $ksoaccountid = ov('ksoaccountid', $purchaseinvoice);
    $ksodebitaccountid = systemvarget('purchaseinvoice_ksoaccountid');

    $details = [];
    $details[] =  array('coaid'=>$ksodebitaccountid, 'debitamount'=>$kso, 'creditamount'=>0);
    $details[] =  array('coaid'=>$ksoaccountid, 'debitamount'=>0, 'creditamount'=>$kso);
    $journalvoucher = array(
      'date'=>$ksodate,
      'description'=>$code . " KSO",
      'ref'=>'PI',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;
  }

  // SKI
  $ski = ov('ski', $purchaseinvoice);
  if($ski > 0 && !$ski_paid){
    $skidate = ov('skidate', $purchaseinvoice);
    $skiaccountid = ov('skiaccountid', $purchaseinvoice);
    $skidebitaccountid = systemvarget('purchaseinvoice_skiaccountid');

    $details = [];
    $details[] =  array('coaid'=>$skidebitaccountid, 'debitamount'=>$ski, 'creditamount'=>0);
    $details[] =  array('coaid'=>$skiaccountid, 'debitamount'=>0, 'creditamount'=>$ski);
    $journalvoucher = array(
      'date'=>$skidate,
      'description'=>$code . " SKI",
      'ref'=>'PI',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;
  }

  // Clearance Fee
  $clearance_fee = ov('clearance_fee', $purchaseinvoice);
  if($clearance_fee > 0 && !$cf_paid){
    $clearance_fee_date = ov('clearance_fee_date', $purchaseinvoice);
    $clearance_fee_accountid = ov('clearance_fee_accountid', $purchaseinvoice);
    $clearance_fee_debitaccountid = systemvarget('purchaseinvoice_clearance_fee_accountid');

    $details = [];
    $details[] =  array('coaid'=>$clearance_fee_debitaccountid, 'debitamount'=>$clearance_fee, 'creditamount'=>0);
    $details[] =  array('coaid'=>$clearance_fee_accountid, 'debitamount'=>0, 'creditamount'=>$clearance_fee);
    $journalvoucher = array(
      'date'=>$clearance_fee_date,
      'description'=>$code . " CLEARANCE FEE",
      'ref'=>'PI',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;
  }

  // Import Cost
  $import_cost = ov('import_cost', $purchaseinvoice);
  if($import_cost > 0 && !$ic_paid){
    $import_cost_date = ov('import_cost_date', $purchaseinvoice);
    $import_cost_accountid = ov('import_cost_accountid', $purchaseinvoice);
    $import_cost_debitaccountid = systemvarget('purchaseinvoice_import_cost_accountid');

    $details = [];
    $details[] =  array('coaid'=>$import_cost_debitaccountid, 'debitamount'=>$import_cost, 'creditamount'=>0);
    $details[] =  array('coaid'=>$import_cost_accountid, 'debitamount'=>0, 'creditamount'=>$import_cost);
    $journalvoucher = array(
      'date'=>$import_cost_date,
      'description'=>$code . " BEA MASUK",
      'ref'=>'PI',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;
  }

  // Create journal for handling fee if any
  if($handlingfeepaymentamount > 0 && !$hf_paid){

    $details = [];
    $details[] =  array('coaid'=>40, 'debitamount'=>$handlingfeepaymentamount, 'creditamount'=>0);
    $details[] =  array('coaid'=>$handlingfeeaccountid, 'debitamount'=>0, 'creditamount'=>$handlingfeepaymentamount);
    $journalvoucher = array(
      'date'=>$handlingfeedate,
      'description'=>$code . " HANDLING FEE",
      'ref'=>'PI',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
    );
    $journalvouchers[] = $journalvoucher;

  }
  //exc($journalvouchers);
  if(count($journalvouchers) > 0){
    journalvoucherremove(array('ref'=>'PI', 'refid'=>$id));
    journalvoucherentries($journalvouchers);
  }

  // Update purchaseinvoice ispaid property
  //mysql_update_row("purchaseinvoice", [ 'ispaid'=>$debtamount > 0 ? 0 : 1 ], [ 'id'=>$id ]);

  // Update purchaseorder ispaid property if any
  if($purchaseorderid > 0)
    mysql_update_row("purchaseorder", [ 'ispaid'=>$debtamount > 0 ? 0 : 1 ], [ 'id'=>$purchaseorderid ]);

  return $journalvouchers;

}
function purchaseinvoicecalculateall(){

  $purchaseinvoiceids = pmrs("select id from purchaseinvoice");
  if(is_array($purchaseinvoiceids))
    foreach($purchaseinvoiceids as $purchaseinvoice)
      purchaseinvoicecalculate($purchaseinvoice['id']);

}
function purchaseinvoice_release_unused(){

  pm("update purchaseinvoice set code = '' where `id` not in (select purchaseinvoiceid from purchaseinvoiceinventory);");

}
function purchaseinvoice_get_costprice($id, $itemid){

  /**
   * Use purchase invoice inventory unit cost price
   */
  $purchaseinvoiceitem = pmr("select t1.code, t2.inventoryid, t2.unitcostprice from purchaseinvoice t1, purchaseinvoiceinventory t2 
    where t1.id = t2.purchaseinvoiceid and t2.id = ?", [ $itemid ]);
  $result = [
    'code'=>$purchaseinvoiceitem['code'],
    'unitamount'=>$purchaseinvoiceitem['unitcostprice'],
    'purchaseprice'=>-1,
    'm3'=>-1,
    'm3amount'=>-1,
    'handlingfeeamount'=>-1,
    'handlingfeevolume'=>-1,
    'freightcharge'=>-1,
    'totalhandlingfeeamount'=>-1
  ];
  return $result;

  /**
   * Calculate cost price by M3 (DEPRECATED)
   */
  // ---------------------------------------------------------------------------------
  // Purchase invoice inventory
  // unitamount = (unitamount - discount_per_unit + tax_per_unit) * currency_rate
  // ---------------------------------------------------------------------------------
  $purchaseinvoice = pmr("select code, currencyrate, freightcharge, discountamount, taxamount, handlingfeepaymentamount, handlingfeevolume from purchaseinvoice where `id` = ?", [ $id ]);
  $unitamount = pmc("select SUM(unittotal) / SUM(qty) as purchaseprice from purchaseinvoiceinventory where purchaseinvoiceid = ? and inventoryid = ? GROUP BY inventoryid", [ $id, $inventoryid ]);
  $currencyrate = $purchaseinvoice['currencyrate'];
  $freightcharge = $purchaseinvoice['freightcharge'];
  $discountamount = $purchaseinvoice['discountamount'];
  $taxamount = $purchaseinvoice['taxamount'];
  $discountamount_perunit = 0;
  $taxamount_perunit = 0;
  if($freightcharge > 0 || $discountamount > 0 || $taxamount > 0){
    $totalqty = pmc("select sum(qty) from purchaseinvoiceinventory where purchaseinvoiceid = ?", [ $id ]);
    $discountamount_perunit = $discountamount / $totalqty;
    $taxamount_perunit = $taxamount / $totalqty;
  }
  $purchaseprice = ($unitamount - $discountamount_perunit + $taxamount_perunit) * $currencyrate;

  // ---------------------------------------------------------------------------------
  // M3 calculation
  // ---------------------------------------------------------------------------------
  $handlingfeeamount = $purchaseinvoice['handlingfeepaymentamount'];
  $handlingfeevolume = $purchaseinvoice['handlingfeevolume'];
  $freightcharge = $purchaseinvoice['freightcharge'];
  $totalhandlingfeeamount = $handlingfeeamount + $freightcharge;
  $m3 = inventory_freightcharge_get($inventoryid, date('Ymd'))['m3'];
  $m3amount = $handlingfeevolume * $totalhandlingfeeamount > 0 ? $m3 / $handlingfeevolume * $totalhandlingfeeamount : 0;

  $unitamount = $purchaseprice + $m3amount;

  $result = [
    'code'=>$purchaseinvoice['code'],
    'unitamount'=>$unitamount,
    'purchaseprice'=>$purchaseprice,
    'm3'=>$m3,
    'm3amount'=>$m3amount,
    'handlingfeeamount'=>$handlingfeeamount,
    'handlingfeevolume'=>$handlingfeevolume,
    'freightcharge'=>$freightcharge,
    'totalhandlingfeeamount'=>$totalhandlingfeeamount
  ];
  return $result;

}

function purchaseinvoice_calc_zero_costprice($id){

  $purchaseinvoice = pmr("select * from purchaseinvoice where `id` = ?", [ $id ]);
  $inventories = pmrs("select * from purchaseinvoiceinventory where purchaseinvoiceid = ?", [ $id ]);

  $subtotal = 0;
  foreach($inventories as $inventory){
    $unittotal = $inventory['unittotal'];
    $subtotal += $unittotal;
  }
  $discountamount = $purchaseinvoice['discountamount'];
  $subtotal_after_discount = $subtotal - $discountamount;
  $taxamount = $purchaseinvoice['taxamount'];;
  $freightcharge = round($purchaseinvoice['freightcharge'], 2);
  $pph = round($purchaseinvoice['pph'], 2);
  $kso = round($purchaseinvoice['kso'], 2);
  $ski = round($purchaseinvoice['ski'], 2);
  $clearance_fee = round($purchaseinvoice['clearance_fee'], 2);

  $discount_percentage = $discountamount / $subtotal;
  $tax_percentage = ($taxamount + $freightcharge + $pph + $kso + $ski + $clearance_fee) / $subtotal_after_discount;

  $currencyrate = $purchaseinvoice['currencyrate'];

  $queries = $params = [];
  foreach($inventories as $inventory){

    // Only apply for unset inventory cost price
    if($inventory['unitcostprice'] <= 0){
      $purchaseinvoiceinventoryid = $inventory['id'];
      $qty = $inventory['qty'];
      $unittotal = $inventory['unittotal'];
      $unittax = $inventory['unittax'];
      $unitprice = $unittotal / $qty;
      $unitcostprice = $unitprice - ($discount_percentage * $unitprice);
      $unitcostprice = $unitcostprice + ($tax_percentage * $unitcostprice);
      $unitcostprice = round($unitcostprice * $currencyrate) + $unittax;
      $queries[] = "update purchaseinvoiceinventory set unitcostprice = ? where `id` = ?";
      array_push($params, $unitcostprice, $purchaseinvoiceinventoryid);
    }

  }

  if(count($queries) > 0){
    pm(implode(';', $queries), $params);
    return true;
  }
  return false;

}

?>