<?php

require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/inventory.php';
require_once dirname(__FILE__) . '/warehouse.php';
require_once dirname(__FILE__) . '/user.php';
require_once dirname(__FILE__) . '/system.php';

function inventoryadjustment_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>0, 'name'=>'warehouseid', 'text'=>'ID Gudang', 'width'=>30),
    array('active'=>1, 'name'=>'warehousename', 'text'=>'Nama Gudang', 'width'=>90),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>90, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>90),
    array('active'=>1, 'name'=>'description', 'text'=>'Nama', 'width'=>150),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'ID Barang', 'width'=>30),
    array('active'=>1, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>60),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Nama Barang', 'width'=>150),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>70, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unit', 'text'=>'Satuan', 'width'=>60),
    array('active'=>1, 'name'=>'unitprice', 'text'=>'Nilai Barang', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'amount', 'text'=>'Total Nilai Barang', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'remark', 'text'=>'Catatan', 'width'=>100),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function inventoryadjustmentcode(){

  $prefix = systemvarget('inventoryadjustmentprefix', 'AJ');
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM inventoryadjustment WHERE code LIKE ?";
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
function inventoryadjustmentdetail($columns, $filters){
  $inventoryadjustment = mysql_get_row('inventoryadjustment', $filters, array('*'));

  if($inventoryadjustment){
    $warehouse = warehousedetail(null, array('id'=>$inventoryadjustment['warehouseid']));
    $inventoryadjustment['warehousename'] = $warehouse['name'];

    $details = pmrs("SELECT * FROM inventoryadjustmentdetail WHERE inventoryadjustmentid = ?", array($inventoryadjustment['id']));
    for($i = 0 ; $i < count($details) ; $i++){

    }
    $inventoryadjustment['details'] = $details;
  }

  return $inventoryadjustment;
}
function inventoryadjustmentlist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'date'=>'t1.date',
    'code'=>'t1.code',
    'description'=>'t1.description',
    'createdon'=>'t1.createdon',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unitprice'=>'t2.unitprice',
    'unit'=>'t2.unit',
    'remark'=>'t2.remark'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.createdby', 't1.warehouseid', 't2.inventoryid'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = 'WHERE t1.id = t2.inventoryadjustmentid' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $limitquery = limitquery_from_limitoffset($limitoffset);

  $query = "SELECT $columnquery FROM inventoryadjustment t1, inventoryadjustmentdetail t2 $wherequery $sortquery $limitquery";
  $arr = pmrs($query, $params);

  if(is_array($arr)){
    $users = userlist(null, null);
    $users = array_index($users, array('id'), 1);
    $warehouses = warehouselist();
    $warehouses = array_index($warehouses, array('id'), 1);
    for($i = 0 ; $i < count($arr) ; $i++){
      $arr[$i]['createdby'] = isset($users[$arr[$i]['createdby']]) ? $users[$arr[$i]['createdby']]['name'] : '-';
      $arr[$i]['warehousename'] = isset($warehouses[$arr[$i]['warehouseid']]) ? $warehouses[$arr[$i]['warehouseid']]['name'] : '-';
    }
  }

  return $arr;

}

function inventoryadjustmententry($obj){

  // Required parameters and validation
  $date = ov('date', $obj, 1, array('type'=>'date')); // Date
  $code = ov('code', $obj, 1, array('notempty'=>1)); // Code
  $warehouseid = ov('warehouseid', $obj);
  $description = ov('description', $obj, 0, ''); // Description
  $details = ov('details', $obj, 1);
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  if(!$code) $code = inventoryadjustmentcode();
  
  if(intval(pmc("SELECT COUNT(*) FROM inventoryadjustment WHERE code = ?", array($code))) > 0) throw new Exception('Kode sudah ada.');
  if(!is_array($details) || count($details) == 0) throw new Exception('Barang harus diisi.');
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(pmc("select count(*) from warehouse where `id` = ?", [ $warehouseid ]) <= 0) exc("Gudang harus diisi.");

  for($i = 0 ; $i < count($details) ; $i++){
    $detail = $details[$i];
    $inventoryid = ov('inventoryid', $detail);
    $inventory = inventorydetail(null, array('id'=>$inventoryid));
    
    if(!$inventory) throw new Exception("Barang yang dimasukkan salah.($inventoryid)");
    
    $inventoryid = $inventory['id'];
    $inventorycode = $inventory['code'];
    $qty = ov('qty', $detail, 1, array('type'=>'decimal'));
    $qty_current = inventoryqty($inventoryid, $warehouseid, $date);
    $remark = ov('remark', $detail, 0, '');
    $unitprice = ov('unitprice', $detail, 0, 0);

		//if($qty > $qty_current) throw new Exception('Stok ' . $inventorydescription . ' tidak cukup untuk gudang ' . $warehousename);

    $details[$i]['inventoryid'] = $inventoryid;
    $details[$i]['inventorycode'] = $inventorycode;
    $details[$i]['inventorydescription'] = $inventory['description'];
    $details[$i]['unit'] = $inventory['unit'];
    $details[$i]['unitprice'] = $unitprice;
    $details[$i]['remark'] = $remark;
  }

  try{

    pdo_begin_transaction();

    // Store to inventoryadjustment table
    $query = "INSERT INTO inventoryadjustment(`date`, code, warehouseid, description, createdon, lastupdatedon, createdby) VALUES
    (?, ?, ?, ?, ?, ?, ?)";
    $id = pmi($query, array($date, $code, $warehouseid, $description, $createdon, $lastupdatedon, $createdby));

    // Store to inventoryadjustmentdetail table
    $params = $paramstr = array();
    for($i = 0 ; $i < count($details) ; $i++){
      $detail = $details[$i];
      $inventoryid = $detail['inventoryid'];
      $inventorycode = $detail['inventorycode'];
      $inventorydescription = $detail['inventorydescription'];
      $unit = $detail['unit'];
      $qty = $detail['qty'];
      $unitprice = $detail['unitprice'];
      $amount = $qty * $unitprice;
      $remark = $detail['remark'];
      $inventorybalances[$inventoryid] = $qty;

      $paramstr[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $unit, $unitprice, $qty, $amount, $remark);
    }
    $query = "INSERT INTO inventoryadjustmentdetail(inventoryadjustmentid, inventoryid, inventorycode, inventorydescription, unit, unitprice, qty, amount, remark)
    VALUES " . implode(',', $paramstr);
    pm($query, $params);

    userlog('inventoryadjustmententry', $obj, '', $_SESSION['user']['id'], $id);

    job_create_and_run('inventoryadjustment_ext', [ $id ]);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();

    throw $ex;

  }

  return [ 'id'=>$id ];

}
function inventoryadjustmentmodify($obj){

  $id = ov('id', $obj, 1);
  $current = inventoryadjustmentdetail(null, array('id'=>$id));
  if(!$current) throw new Exception('Tidak dapat mengubah penyesuaian ini, data tidak terdaftar.');

  $updatedrow = array();
  
  if(isset($obj['code']) && $obj['code'] != $current['code']){
  	$code = $obj['code'];
  	if(intval(pmc("SELECT COUNT(*) FROM inventoryadjustment WHERE code = ?", array($code))) > 0) throw new Exception('Kode sudah ada.');
  	$updatedrow['code'] = $code;
  }
  if(isset($obj['description']) && $obj['description'] != $current['description'])
    $updatedrow['description'] = $obj['description'];
  if(isset($obj['warehouseid']) && $obj['warehouseid'] != $current['warehouseid'])
    $updatedrow['warehouseid'] = $obj['warehouseid'];
  if(isset($obj['date']) && strtotime($obj['date']) != strtotime($current['date'])){
    if(!isdate($obj['date'])) exc('Format tanggal salah');
    $updatedrow['date'] = date('Ymd', strtotime($obj['date']));
  }

  if(isset($obj['details'])){
    $details = $obj['details'];
  	for($i = 0 ; $i < count($details) ; $i++){
	    $detail = $details[$i];
	    $inventoryid = ov('inventoryid', $detail);
	    $inventory = inventorydetail(null, array('id'=>$inventoryid));
	    
	    if(!$inventory) throw new Exception('Barang yang dimasukkan salah.');
	  	  
	    $inventoryid = $inventory['id'];
	    $inventorycode = $inventory['code'];
	    $remark = ov('remark', $detail, 0, '');
	    $unitprice = ov('unitprice', $detail, 0, 0);

	    $details[$i]['inventoryid'] = $inventoryid;
	    $details[$i]['inventorycode'] = $inventorycode;
	    $details[$i]['inventorydescription'] = $inventory['description'];
	    $details[$i]['unit'] = $inventory['unit'];
	    $details[$i]['unitprice'] = $unitprice;
	    $details[$i]['remark'] = $remark;
	  }  
  }

  try{

    pdo_begin_transaction();

    if(count($updatedrow) > 0)
      mysql_update_row('inventoryadjustment', $updatedrow, array('id'=>$id));

    if(isset($obj['details']) && count($obj['details']) != $current['details']){

      pm("DELETE FROM inventoryadjustmentdetail WHERE inventoryadjustmentid = ?", array($id));

      $params = $paramstr = array();
      for($i = 0 ; $i < count($details) ; $i++){
        $detail = $details[$i];
        $inventoryid = $detail['inventoryid'];
        $inventorycode = $detail['inventorycode'];
        $inventorydescription = $detail['inventorydescription'];
        $unit = $detail['unit'];
        $qty = $detail['qty'];
        $unitprice = $detail['unitprice'];
        $amount = $qty * $unitprice;
        $remark = $detail['remark'];
        $inventorybalances[$inventoryid] = $qty;

        $paramstr[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
        array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $unit, $unitprice, $qty, $amount, $remark);
      }
      $query = "INSERT INTO inventoryadjustmentdetail(inventoryadjustmentid, inventoryid, inventorycode, inventorydescription, unit, unitprice, qty, amount, remark)
	    VALUES " . implode(',', $paramstr);
      pm($query, $params);

      $updatedrow['details'] = $obj['details'];

      userlog('inventoryadjustmentmodify', $current, $updatedrow, $_SESSION['user']['id'], $id);

      job_create_and_run('inventoryadjustment_ext', [ $id ]);

    }

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();

    throw $ex;

  }

  return [ 'id'=>$id ];
  
}
function inventoryadjustmentremove($filters){

  if(isset($filters['id'])){
    $id = $filters['id'];
    $inventoryadjustment = inventoryadjustmentdetail(null, array('id'=>$id));
    if(!$inventoryadjustment) throw new Exception('Kode tidak terdaftar.');

    try{

      pdo_begin_transaction();

      pm("DELETE FROM inventoryadjustment WHERE `id` = ?", array($id));

      userlog('inventoryadjustmentremove', $inventoryadjustment, '', $_SESSION['user']['id'], $id);

      job_create_and_run('inventoryadjustment_remove_ext', [ $id ]);

      pdo_commit();

    }
    catch(Exception $ex){

      pdo_rollback();

      throw $ex;

    }

  }

}

function inventoryadjustment_ext($id){

  $current = inventoryadjustmentdetail(null, array('id'=>$id));
  $code = $current['code'];
  $warehouseid = $current['warehouseid'];
  $date = $current['date'];
  $details = $current['details'];

  $total = 0;
  $inventorybalances = array();
  for($i = 0 ; $i < count($details) ; $i++){
    $inventory = $details[$i];
    $inventoryid = $inventory['inventoryid'];
    $qty = $inventory['qty'];
    $amount = $inventory['amount'];

    $inventorybalance = array(
      'inventoryid'=>$inventoryid,
      'warehouseid'=>$warehouseid,
      'description'=>$current['description'],
      'date'=>$date,
      'in'=>$qty > 0 ? $qty : 0,
      'out'=>$qty < 0 ? abs($qty) : 0,
      'amount'=>abs($amount),
      'ref'=>'IA',
      'refid'=>$id,
      'createdon'=>$current['createdon']
    );
    $inventorybalances[] = $inventorybalance;
    $total += $amount;
  }
  inventorybalanceentries($inventorybalances);

  journalvoucherentries([
    [
      'date'=>$date,
      'description'=>$code,
      'ref'=>'AJ',
      'refid'=>$id,
      'type'=>'A',
      'details'=>array(
        array('coaid'=>10, 'debitamount'=>$total, 'creditamount'=>0),
        array('coaid'=>19, 'debitamount'=>0, 'creditamount'=>$total)
      )
    ]
  ]);

}
function inventoryadjustment_remove_ext($id){

  inventorybalanceremove(array('ref'=>'IA', 'refid'=>$id));
  journalvoucherremove(array('ref'=>'AJ', 'refid'=>$id));


}

?>