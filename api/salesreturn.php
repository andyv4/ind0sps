<?php
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/customer.php';
require_once dirname(__FILE__) . '/warehouse.php';
require_once dirname(__FILE__) . '/user.php';
require_once dirname(__FILE__) . '/salesorder.php';
require_once dirname(__FILE__) . '/salesinvoicegroup.php';
require_once dirname(__FILE__) . '/salesreceipt.php';
require_once dirname(__FILE__) . '/system.php';
require_once dirname(__FILE__) . '/log.php';

$salesreturn_datadef = array(
  'date'=>array('type'=>'date', 'required'=>1, 'error_message'=>'Silakan isi tanggal.'),
  'code'=>array('type'=>'string', 'required'=>1),
  'customerid'=>array('type'=>'int', 'required'=>1, 'dbcheckquery'=>"SELECT COUNT(*) FROM customer WHERE `id` = ?"),
  'items'=>array('type'=>'array', 'required'=>1, 'items'=>array(
    array(
      'code'=>array('type'=>'string', 'required'=>1),
      'qty'=>array('type'=>'int', 'required'=>1),
      'warehouseid'=>array('type'=>'int', 'required'=>1)
    )
  )),
  'note'=>array('type'=>'string', 'required'=>0)
);
salesreturndetail_accounts();

function salesreturn_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>0, 'name'=>'customerid', 'text'=>'Id Pelanggan', 'width'=>30),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'Id Barang', 'width'=>30),
    array('active'=>0, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>60),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Barang', 'width'=>200),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>60, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'unit', 'text'=>'Satuan', 'width'=>60),
    array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>80, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'unittotal', 'text'=>'Total Barang', 'width'=>80, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'datetime'),
  );
  return $columns;

}
function salesreturncode(){

  if(!($prefix = systemvarget('salesreturnprefix'))) $prefix = 'SN';
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM salesreturn WHERE code LIKE ?";
  $rows = pmrs($query, array("%$prefix_plus_year%"));
  $blankcounter = -1;
  $maxcounter = 0;
  if(is_array($rows)){
    $numbers = array();
    for($i = 0 ; $i < count($rows) ; $i++){
      $code = $rows[$i]['code'];
      $counter = intval(str_replace($prefix_plus_year, '', $code));
      $numbers[] = $counter;
    }
    sort($numbers);
    for($i = 0 ; $i < count($numbers) ; $i++){
      if($i + 1 < count($numbers) && $numbers[$i] + 1 != $numbers[$i + 1]){
        $blankcounter = $numbers[$i] + 1;
        break;
      }
      if($maxcounter < $numbers[$i]) $maxcounter = $numbers[$i];
    }
    if($blankcounter == -1) $blankcounter = $maxcounter + 1;
  }
  if($blankcounter == -1) $blankcounter = 1;
  $code = "$prefix/" . date('y') . "/" . str_pad($blankcounter, 5, '0', STR_PAD_LEFT);
  return $code;

}
function salesreturndetail($columns, $filters){

  $id = ov('id', $filters);

  $obj = pmr("SELECT * FROM salesreturn WHERE `id` = ?", array($id));
  $items = pmrs("SELECT * FROM salesreturninventory WHERE salesreturnid = ?", array($id));

  for($i = 0 ; $i < count($items) ; $i++){
    $salesinvoice = pmr("SELECT code, ispaid FROM salesinvoice t1, salesinvoiceinventory t2 WHERE
      t2.id = ? AND t1.id = t2.salesinvoiceid GROUP BY t1.id", array($items[$i]['salesinvoiceinventoryid']));

    $items[$i]['code'] = $salesinvoice['code'];
    $items[$i]['ispaid'] = $salesinvoice['ispaid'];
  }

  $obj['items'] = $items;

  return $obj;

}
function salesreturnlist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerid'=>'t1.customerid',
    'customerdescription'=>'t1.customerdescription',
    'total'=>'t1.total',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'unittotal'=>'t2.unittotal',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.code', 't1.date', 't1.createdby'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesreturnid' . str_replace('WHERE', 'AND', $wherequery);
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM salesreturn t1, salesreturninventory t2 $wherequery $sortquery $limitquery";
  $arr = pmrs($query, $params);

  return $arr;

}
function salesreturn_returntypes(){

  return array(
    array('text'=>'Cash', 'value'=>'cash')
  );

}
function salesreturn_salesinvoiceitems($codes){

  if(!is_array($codes)) return;

  // Add quote to codes
  for($i = 0 ; $i < count($codes) ; $i++)
    $codes[$i] = "'" . $codes[$i] . "'";

  $items = pmrs("SELECT t2.* FROM salesinvoice t1, salesinvoiceinventory t2 WHERE t1.id = t2.salesinvoiceid AND t1.code IN (" . implode(', ', $codes) . ")");

  // Set max qty
  for($i = 0 ; $i < count($items) ; $i++)
    $items[$i]['maxqty'] = $items[$i]['qty'];

  return $items;

}
function salesreturn_salesinvoicelist($customerid){

  $salesinvoices = pmrs("SELECT `id`, `date`, code FROM salesinvoice WHERE customerid = ? ORDER BY `date` DESC, createdon DESC", array($customerid));
  return $salesinvoices;

}
function salesreturndetail_accounts(){

  $rows = pmrs("SELECT * FROM chartofaccount WHERE accounttype = 'Asset'");
  if(!is_array($rows)) $rows = array();
  $rows[] = pmr("SELECT * FROM chartofaccount WHERE code = '00.000'");

  return array_cast($rows, array('text'=>'name', 'value'=>'id'));

}

function salesreturnentry($obj){

  $lock_file = __DIR__ . "/../usr/system/salesreturn_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  global $salesreturn_datadef;
  $obj = datadef_validation($obj, $salesreturn_datadef);

  //throw new Exception(print_r($obj, 1));

  $customerid = ov('customerid', $obj);
  $customer = customerdetail(null, array('id'=>$customerid));
  $customerdescription = $customer['description'];
  $total = ov('total', $obj);
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $lastupdatedby = $_SESSION['user']['id'];

  // Insert to salesreturn table
  $id = pmi("INSERT INTO salesreturn(`date`, code, customerid, customerdescription, total, note, returnaccountid, createdon, createdby,
   lastupdatedon, lastupdatedby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($obj['date'], $obj['code'], $customerid, $customerdescription,
    $total, $obj['note'], $obj['returnaccountid'], $createdon, $createdby, $lastupdatedon, $lastupdatedby));

  try{

    $params = $queries = $salesinvoiceinventoryids = array();
    for($i = 0 ; $i < count($obj['items']) ; $i++){
      $inventory = $obj['items'][$i];

      $inventorydescription = ov('inventorydescription', $inventory);
      $inventorydata = inventorydetail(null, array('description'=>$inventorydescription));
      $inventoryid = ov('id', $inventorydata);
      $inventorycode = ov('code', $inventorydata);
      $qty = ov('qty', $inventory);
      $unit = ov('unit', $inventory);
      $unitprice = ov('unitprice', $inventory);
      $unittotal = ov('unittotal', $inventory);
      $salesinvoiceinventoryid = ov('salesinvoiceinventoryid', $inventory);
      $warehouseid = $inventory['warehouseid'];

      $queries[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, $unittotal,
        $salesinvoiceinventoryid, $warehouseid);
      $salesinvoiceinventoryids[] = $salesinvoiceinventoryid;

    }
    pm("INSERT INTO salesreturninventory (salesreturnid, inventoryid, inventorycode, inventorydescription, qty, unit, unitprice, unittotal,
      salesinvoiceinventoryid, warehouseid) VALUES " . implode(', ', $queries), $params);

    salesreturn_salesinvoiceinventory_returnqty($salesinvoiceinventoryids);
    salesreturn_journal($id);
    salesreturn_inventorybalance($id);

    userlog('salesreturnentry', $obj, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

    return array('id'=>$id);

  }
  catch(Exception $ex){

    salesreturnremove(array('id'=>$id));

    fclose($fp);
    unlink($lock_file);

    throw $ex;

  }

}
function salesreturnmodify($obj){

  $id = ov('id', $obj);
  $salesreturn = salesreturndetail(null, array('id'=>$id));

  if(!$salesreturn) exc('Retur penjualan tidak terdaftar.');

  $lock_file = __DIR__ . "/../usr/system/salesreturn_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $ispaid = ov('ispaid', $obj);
  $updatedrow = array();

  if(isset($obj['note']) && $obj['note'] != $salesreturn['note'])
    $updatedrow['note'] = $obj['note'];

  if(isset($obj['returnaccountid'])){
    $returnaccountid = $obj['returnaccountid'];
    $exists = intval(pmc("SELECT COUNT(*) FROM chartofaccount WHERE `id` = ? AND code != '00.000'", array($returnaccountid))) > 0 ? 1 : 0;
    if(!$exists && $ispaid) throw new Exception('Akun pembayaran retur belum diisi.');
    $updatedrow['returnaccountid'] = $returnaccountid;
  }

  if(isset($obj['ispaid'])){
    $ispaid = intval($obj['ispaid']) > 0 ? 1 : 0;
    $updatedrow['ispaid'] = $ispaid;
  }

  if(isset($obj['returnamount']) && $obj['returnamount'] != $salesreturn['returnamount']){
    $updatedrow['returnamount'] = $obj['returnamount'];
  }

  //print_var($updatedrow);

  if(count($updatedrow) > 0)
    mysql_update_row('salesreturn', $updatedrow, array('id'=>$id));

  if(isset($obj['items'])){

    if(count($obj['items']) <= 0) throw new Exception('Faktur belum diisi.');

    pm("DELETE FROM salesreturninventory WHERE salesreturnid = ?", array($id));

    $params = $queries = $salesinvoiceinventoryids = array();
    for($i = 0 ; $i < count($obj['items']) ; $i++){
      $inventory = $obj['items'][$i];

      $inventorydescription = ov('inventorydescription', $inventory);
      $inventorydata = inventorydetail(null, array('description'=>$inventorydescription));
      $inventoryid = ov('id', $inventorydata);
      $inventorycode = ov('code', $inventorydata);
      $qty = ov('qty', $inventory);
      $unit = ov('unit', $inventory);
      $unitprice = ov('unitprice', $inventory);
      $unittotal = ov('unittotal', $inventory);
      $salesinvoiceinventoryid = ov('salesinvoiceinventoryid', $inventory);
      $warehouseid = $inventory['warehouseid'];

      $queries[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, $unittotal,
        $salesinvoiceinventoryid, $warehouseid);
      $salesinvoiceinventoryids[] = $salesinvoiceinventoryid;

    }
    pm("INSERT INTO salesreturninventory (salesreturnid, inventoryid, inventorycode, inventorydescription, qty, unit, unitprice, unittotal,
      salesinvoiceinventoryid, warehouseid) VALUES " . implode(', ', $queries), $params);

    salesreturn_salesinvoiceinventory_returnqty($salesinvoiceinventoryids);
    salesreturn_journal($id);
    salesreturn_inventorybalance($id);

    $updatedrow['items'] = $obj['items'];
  }

  salesreturn_journal($id);

  userlog('salesreturnmodify', $salesreturn, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}
function salesreturnremove($filters){

  $salesreturn = salesreturndetail(null, $filters);

  if(!$salesreturn) exc('Retur penjualan tidak terdaftar.');

  $lock_file = __DIR__ . "/../usr/system/salesreturn_remove_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

  $id = $salesreturn['id'];
  $items = $salesreturn['items'];
  $salesinvoiceinventoryids = array();
  for($i = 0 ; $i < count($items) ; $i++)
    $salesinvoiceinventoryids[] = $items[$i]['salesinvoiceinventoryid'];

  journalvoucherremove(array('ref'=>'SN', 'refid'=>$id));
  pm("DELETE FROM salesreturn WHERE `id` = ?", array($id));
  salesreturn_salesinvoiceinventory_returnqty($salesinvoiceinventoryids);
  inventorybalanceremove(array('ref'=>'SN', 'refid'=>$id));

  userlog('salesreturnremove', $salesreturn, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}

function salesreturn_salesinvoiceinventory_returnqty($salesinvoiceinventoryids){

  for($i = 0 ; $i < count($salesinvoiceinventoryids) ; $i++){
    $salesinvoiceinventoryid = $salesinvoiceinventoryids[$i];

    $returnqty = pmc("SELECT SUM(qty) FROM salesreturninventory WHERE salesinvoiceinventoryid = ?", array($salesinvoiceinventoryid));
    pm("UPDATE salesinvoiceinventory SET returnqty = ? WHERE `id` = ?", array($returnqty, $salesinvoiceinventoryid));
  }

}
function salesreturn_inventorybalance($id){

  $obj = salesreturndetail(null, array('id'=>$id));
  $items = $obj['items'];

  inventorybalanceremove(array('ref'=>'SN', 'refid'=>$id));
  foreach($items as $inventory){
    $inventoryid = $inventory['inventoryid'];
    $qty = $inventory['qty'];

    inventorybalanceentry(array(
      'ref'=>'SN',
      'refid'=>$id,
      'date'=>$obj['date'],
      'warehouseid'=>$inventory['warehouseid'],
      'description'=>$obj['customerdescription'],
      'inventoryid'=>$inventoryid,
      'in'=>$qty,
      'createdon'=>$obj['createdon']
    ));
  }

}
function salesreturn_journal($id){

  $salesreturn_accountid = pmc("SELECT `id` FROM chartofaccount WHERE code = '500.04'");
  if(!$salesreturn_accountid) throw new Exception('Akun retur dengan kode 500.04 tidak ada');

  $sales_accountid = pmc("SELECT `id` FROM chartofaccount WHERE code = '500.01'");
  if(!$salesreturn_accountid) throw new Exception('Akun penjualan dengan kode 500.04 tidak ada');

  $salesreceivable_accountid = pmc("SELECT `id` FROM chartofaccount WHERE code = '200.01'");
  if(!$salesreceivable_accountid) throw new Exception('Akun piutang dengan kode 200.01 tidak ada');

  $salesreturn = salesreturndetail(null, array('id'=>$id));
  $items = $salesreturn['items'];
  $total = $salesreturn['total'];
  $ispaid = $salesreturn['ispaid'];
  $returnaccountid = $salesreturn['returnaccountid'];

  $returnamount = 0;
  $receivableamount = 0;
  $paymentamount = 0;
  foreach($items as $item){
    $salesinvoiceinventoryid = $item['salesinvoiceinventoryid'];
    $unittotal = $item['unitprice'] * $item['qty'];

    $salesinvoice_ispaid = pmc("SELECT t1.ispaid FROM salesinvoice t1, salesinvoiceinventory t2 WHERE
      t1.id = t2.salesinvoiceid AND t2.id = ? GROUP BY t2.salesinvoiceid", array($salesinvoiceinventoryid));

    $returnamount += $unittotal;
    if(!$salesinvoice_ispaid){
      $receivableamount += $unittotal;
    }
    else{
      $paymentamount += $unittotal;
    }
  }

  // Generate journal
  // - Penjualan (C)
  // - Retur Penjualan (D)
  // - Retur Penjualan (C)
  // - Piutang (C)
  $details = array();
  $details[] = array('coaid'=>$sales_accountid, 'debitamount'=>$total, 'creditamount'=>0);
  if($total - $receivableamount > 0)
    $details[] = array('coaid'=>$salesreturn_accountid, 'debitamount'=>0, 'creditamount'=>$total);
  if($receivableamount > 0){
    $details[] = array('coaid'=>$salesreturn_accountid, 'debitamount'=>$receivableamount, 'creditamount'=>0);
    $details[] = array('coaid'=>$salesreceivable_accountid, 'debitamount'=>0, 'creditamount'=>$receivableamount);
  }
  if($ispaid && $paymentamount > 0){
    $details[] = array('coaid'=>$salesreturn_accountid, 'debitamount'=>$paymentamount, 'creditamount'=>0);
    $details[] = array('coaid'=>$returnaccountid, 'debitamount'=>0, 'creditamount'=>$paymentamount);
  }

  $journal = array(
      'date'=>$salesreturn['date'],
      'description'=>'Retur dari ' . $salesreturn['customerdescription'] . ' #' . $salesreturn['code'],
      'ref'=>'SN',
      'refid'=>$id,
      'type'=>'A',
      'details'=>$details
  );

  journalvoucherentryormodify($journal);

}

?>