<?php

require_once dirname(__FILE__) . '/inventory.php';
require_once dirname(__FILE__) . '/system.php';

function sampleinvoice_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>40),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options', 'align'=>'center'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>90),
    array('active'=>0, 'name'=>'warehouseid', 'text'=>'ID Gudang', 'width'=>30, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'warehousename', 'text'=>'Nama Gudang', 'width'=>80),
    array('active'=>0, 'name'=>'customerid', 'text'=>'Id Pelanggan', 'width'=>30),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'Id Barang', 'width'=>30),
    array('active'=>1, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>80),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Barang', 'width'=>200),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>60, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat', 'width'=>120, 'datatype'=>'datetime'),
    array('active'=>0, 'name'=>'salesinvoicegroupid', 'text'=>'ID Grup Faktur', 'width'=>40),
    array('active'=>0, 'name'=>'salesreceiptid', 'text'=>'ID Kwitansi', 'width'=>40),
  );
  return $columns;

}
function sampleinvoicecode(){

  $prefix = systemvarget('sampleinvoiceprefix', 'SJS');
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM sampleinvoice WHERE code LIKE ?";
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
function sampleinvoicedetail($columns, $filters){

  $sampleinvoice = mysql_get_row('sampleinvoice', $filters, $columns);

  if($sampleinvoice){

    $id = $sampleinvoice['id'];

    $inventories = pmrs("SELECT * FROM sampleinvoiceinventory WHERE sampleinvoiceid = ?", array($id));
    $sampleinvoice['inventories'] = $inventories;

  }

  return $sampleinvoice;

}

function sampleinvoiceentry($sampleinvoice){

  $code = ov('code', $sampleinvoice);
  $date = ov('date', $sampleinvoice);
  $customerdescription = ov('customerdescription', $sampleinvoice);
  $address = ov('address', $sampleinvoice);
  $note = ov('note', $sampleinvoice);
  $warehouseid = ov('warehouseid', $sampleinvoice);
  $inventories = ov('inventories', $sampleinvoice);
  $userid = $_SESSION['user']['id'];

  if(pmc("SELECT COUNT(*) FROM sampleinvoice WHERE code = ?", array($code)) > 0) exc('Kode sudah ada.');
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(!is_array($inventories)) exc('Invalid inventories parameter, array required.');
  if(count($inventories) == 0) exc('Barang harus diisi.');
  $inventory_exists = false;
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventory = $inventories[$i];
    $inventoryid = $inventory['inventoryid'];
    $qty = $inventory['qty'];
    $obj = inventorydetail(null, array('id'=>$inventoryid));

    if(!$obj) exc('Barang tidak terdaftar.');
    if($qty <= 0) exc('Kts harus lebih besar dari 0.');
    $inventory_exists = true;
  }
  if(!$inventory_exists) exc("Barang harus diisi dengan benar.");

  try{

    pdo_begin_transaction();

    $query = "INSERT INTO sampleinvoice(`date`, code, customerdescription, address, note, warehouseid, createdon, createdby,
    lastupdatedon, lastupdatedby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $id = pmi($query, array($date, $code, $customerdescription, $address, $note, $warehouseid, date('YmdHis'), $userid, date('YmdHis'), $userid));

    $queries = $params = array();
    foreach($inventories as $inventory){
      if(count($inventory) == 0) continue;
      if(isset($inventory['qty']) && !$inventory['qty']) continue;
      $inventoryid = $inventory['inventoryid'];
      $qty = $inventory['qty'];
      $unit = $inventory['unit'];

      $obj = inventorydetail(null, array('id'=>$inventoryid));
      $inventoryid = $obj['id'];
      $inventorycode = $obj['code'];
      $inventorydescription = $obj['description'];

      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit);
      $queries[] = "(?, ?, ?, ?, ?, ?)";
    }
    pm("INSERT INTO sampleinvoiceinventory(sampleinvoiceid, inventoryid, inventorycode, inventorydescription, qty, unit) VALUES " . implode(', ', $queries), $params);

    userlog('sampleinvoiceentry', $sampleinvoice, '', $_SESSION['user']['id'], $id);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

  sampleinvoicecalculate($id);

  return [ 'id'=>$id ];

}
function sampleinvoicemodify($sampleinvoice){

  $id = ov('id', $sampleinvoice);
  $current = sampleinvoicedetail(null, array('id'=>$id));

  if(!$current) exc('Sample invoice tidak ada.');

  $updatedcols = array();

  if(isset($sampleinvoice['date']) && $sampleinvoice['date'] != $current['date']){
    if(!isdate($sampleinvoice['date'])) exc('Format tanggal salah');
    $updatedcols['date'] = $sampleinvoice['date'];
  }

  if(isset($sampleinvoice['code']) && $sampleinvoice['code'] != $current['code'])
    $updatedcols['code'] = $sampleinvoice['code'];

  if(isset($sampleinvoice['customerdescription']) && $sampleinvoice['customerdescription'] != $current['customerdescription'])
    $updatedcols['customerdescription'] = $sampleinvoice['customerdescription'];

  if(isset($sampleinvoice['address']) && $sampleinvoice['address'] != $current['address'])
    $updatedcols['address'] = $sampleinvoice['address'];

  if(isset($sampleinvoice['note']) && $sampleinvoice['note'] != $current['note'])
    $updatedcols['note'] = $sampleinvoice['note'];

  if(isset($sampleinvoice['warehouseid']) && $sampleinvoice['warehouseid'] != $current['warehouseid'])
    $updatedcols['warehouseid'] = $sampleinvoice['warehouseid'];

  if(isset($sampleinvoice['inventories'])){

    $inventories = ov('inventories', $sampleinvoice);

    if (!is_array($inventories)) exc('Invalid inventories parameter, array required.');
    if (count($inventories) == 0) exc('Barang harus diisi.');

    $temp = [];
    $inventory_exists = false;
    for ($i = 0; $i < count($inventories); $i++) {
      $inventory = $inventories[$i];
      $inventoryid = $inventory['inventoryid'];
      $qty = $inventory['qty'];
      $obj = inventorydetail(null, array('id' => $inventoryid));

      if (!$obj) exc('Barang tidak terdaftar.');
      if ($qty <= 0) exc('Kts harus lebih besar dari 0.');

      $inventories[$i]['inventory_code'] = $obj['code'];
      $inventories[$i]['inventory_description'] = $obj['description'];

      $temp[] = $inventories[$i];
      $inventory_exists = true;
    }
    $inventories = $temp;
    if(!$inventory_exists) exc("Barang harus diisi dengan benar.");

  }

  try{

    pdo_begin_transaction();

    if(count($updatedcols) > 0){
      $updatedcols['lastupdatedon'] = date("YmdHis");
      $updatedcols['lastupdatedby'] = $_SESSION['user']['id'];
      mysql_update_row('sampleinvoice', $updatedcols, array('id'=>$id));
    }

    if(isset($sampleinvoice['inventories'])){

      $queries = $params = array();
      foreach($inventories as $inventory){
        if(count($inventory) == 0) continue;
        if(isset($inventory['qty']) && !$inventory['qty']) continue;
        $inventoryid = $inventory['inventoryid'];
        $qty = $inventory['qty'];
        $unit = $inventory['unit'];
        $inventorycode = $inventory['inventory_code'];
        $inventorydescription = $inventory['inventory_description'];

        array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit);
        $queries[] = "(?, ?, ?, ?, ?, ?)";
      }
      if(count($queries) > 0){
        pm("DELETE FROM sampleinvoiceinventory WHERE sampleinvoiceid = ?", array($id));
        pm("INSERT INTO sampleinvoiceinventory(sampleinvoiceid, inventoryid, inventorycode, inventorydescription, qty, unit) VALUES " . implode(', ', $queries), $params);
      }

      $updatedcols['inventories'] = $sampleinvoice['inventories'];

    }

    userlog('sampleinvoicemodify', $current, $updatedcols, $_SESSION['user']['id'], $id);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();

    throw $ex;

  }

  sampleinvoicecalculate($id);

  return [ 'id'=>$id ];

}
function sampleinvoiceremove($filters){

  $sampleinvoice = sampleinvoicedetail(null, $filters);

  if(!$sampleinvoice) exc('Sampel faktur penjualan tidak terdaftar.');

  $id = $sampleinvoice['id'];

  try{

    pdo_begin_transaction();

    journalvoucherremove(array('ref'=>'SJS', 'refid'=>$id));
    inventorybalanceremove(array('ref'=>'SJS', 'refid'=>$id));
    pm("DELETE FROM sampleinvoice WHERE `id` = ?", array($id));
    userlog('sampleinvoiceremove', $sampleinvoice, '', $_SESSION['user']['id'], $id);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();

    throw $ex;

  }

}

function sampleinvoicecalculate($id){

  $sampleinvoice = sampleinvoicedetail(null, array('id'=>$id));

  // Inventory balance
  $inventoryouts = array();
  $inventories = $sampleinvoice['inventories'];
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventory = $inventories[$i];
    $inventoryid = $inventory['inventoryid'];
    $qty = ov('qty', $inventory);

    if(!isset($inventoryouts[$inventoryid])) $inventoryouts[$inventoryid] = array('qty'=>0);
    $inventoryouts[$inventoryid]['qty'] += $qty;
  }
  inventorybalanceremove(array('ref'=>'SJS', 'refid'=>$id));
  $inventorybalances = array();
  foreach($inventoryouts as $inventoryid=>$out){
    $inventorybalance = array(
      'ref'=>'SJS',
      'refid'=>$id,
      'date'=>$sampleinvoice['date'],
      'warehouseid'=>$sampleinvoice['warehouseid'],
      'description'=>$sampleinvoice['customerdescription'],
      'inventoryid'=>$inventoryid,
      'out'=>$out['qty'],
      'createdon'=>$sampleinvoice['createdon']
    );
    $inventorybalances[] = $inventorybalance;
  }
  inventorybalanceentries($inventorybalances);

  // Journal voucher
  $details[] = array('coaid'=>10, 'debitamount'=>0, 'creditamount'=>0);
  $details[] = array('coaid'=>11, 'debitamount'=>0, 'creditamount'=>0);
  $journal = array(
      'ref'=>'SJS',
      'refid'=>$id,
      'type'=>'A',
      'date'=>$sampleinvoice['date'],
      'description'=>$sampleinvoice['customerdescription'],
      'details'=>$details
  );
  journalvoucherentryormodify($journal);

  global $_REQUIRE_WORKER;
  $_REQUIRE_WORKER = true;

}

?>