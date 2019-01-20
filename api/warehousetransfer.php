<?php
require_once dirname(__FILE__) . '/inventory.php';
require_once dirname(__FILE__) . '/warehouse.php';

function warehousetransfer_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'options', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'description', 'text'=>'Deskripsi', 'width'=>150),
    array('active'=>1, 'name'=>'fromwarehousename', 'text'=>'Gudang Asal', 'width'=>100),
    array('active'=>1, 'name'=>'towarehousename', 'text'=>'Gudang Tujuan', 'width'=>100),
    array('active'=>0, 'name'=>'fromwarehouseid', 'text'=>'ID Gudang Asal', 'width'=>30),
    array('active'=>0, 'name'=>'towarehouseid', 'text'=>'ID Gudang Tujuan', 'width'=>30),
    array('active'=>1, 'name'=>'totalqty', 'text'=>'Total Kts', 'width'=>60, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'ID Barang', 'width'=>30, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>50),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Nama Barang', 'width'=>200),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>30, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unit', 'text'=>'Satuan', 'width'=>50),
    array('active'=>0, 'name'=>'remark', 'text'=>'Catatan', 'width'=>100),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function warehousetransfercode(){

  $prefix = systemvarget('warehousetransferprefix', 'WT');
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM warehousetransfer WHERE code LIKE ?";
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
function warehousetransferdetail($columns, $filters){

  $warehousetransfer = mysql_get_row('warehousetransfer', $filters, $columns);
  if($warehousetransfer){
    $warehousetransfer['fromwarehousename'] = warehousedetail(null, array('id'=>$warehousetransfer['fromwarehouseid']))['name'];
    $warehousetransfer['towarehousename'] = warehousedetail(null, array('id'=>$warehousetransfer['towarehouseid']))['name'];
    $inventories = pmrs("SELECT * FROM warehousetransferinventory WHERE warehousetransferid = ?", array($warehousetransfer['id']));
    for($i = 0 ; $i < count($inventories) ; $i++){
      $inventory = inventorydetail(null, array('id'=>$inventories[$i]['inventoryid']));
      $inventories[$i]['inventorycode'] = $inventory['code'];
      $inventories[$i]['inventorydescription'] = $inventory['description'];
    }
    $warehousetransfer['inventories'] = $inventories;
  }
  return $warehousetransfer;

}
function warehousetransferlist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
      'date'=>'t1.date',
      'code'=>'t1.code',
      'description'=>'t1.description',
      'totalqty'=>'t1.totalqty',
      'createdon'=>'t1.createdon',
      'inventorycode'=>'t3.code as inventorycode',
      'inventorydescription'=>'t3.description as inventorydescription',
      'qty'=>'t2.qty',
      'unit'=>'t3.unit',
      'remark'=>'t2.remark'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.createdby', 't1.fromwarehouseid', 't1.towarehouseid', 't2.inventoryid'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = 'WHERE t1.id = t2.warehousetransferid AND t2.inventoryid = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $limitquery = limitquery_from_limitoffset($limitoffset);

  $query = "SELECT $columnquery FROM warehousetransfer t1, warehousetransferinventory t2, inventory t3 $wherequery $sortquery $limitquery";
  $warehousetransfers = pmrs($query, $params);

  if(is_array($warehousetransfers)){
    $users = userlist(null, null);
    $users = array_index($users, array('id'), 1);
    $warehouses = warehouselist();
    $warehouses = array_index($warehouses, array('id'), 1);
    for($i = 0 ; $i < count($warehousetransfers) ; $i++){
      $warehousetransfers[$i]['createdby'] = isset($users[$warehousetransfers[$i]['createdby']]) ? $users[$warehousetransfers[$i]['createdby']]['name'] : '-';
      $warehousetransfers[$i]['fromwarehousename'] = isset($warehouses[$warehousetransfers[$i]['fromwarehouseid']]) ? $warehouses[$warehousetransfers[$i]['fromwarehouseid']]['name'] : '-';
      $warehousetransfers[$i]['towarehousename'] = isset($warehouses[$warehousetransfers[$i]['towarehouseid']]) ? $warehouses[$warehousetransfers[$i]['towarehouseid']]['name'] : '-';
    }
  }

  return $warehousetransfers;

}

function warehousetransferentry($warehousetransfer){

  $date = ov('date', $warehousetransfer, 1, array('type'=>'date'));
  $code = ov('code', $warehousetransfer, 1, array('notempty'=>1));
  $description = ov('description', $warehousetransfer, 0, '');
  $fromwarehouseid = ov('fromwarehouseid', $warehousetransfer, 1, array('notempty'=>1));
  $towarehouseid = ov('towarehouseid', $warehousetransfer, 1, array('notempty'=>1));
  $inventories = ov('inventories', $warehousetransfer, 1);

  if($fromwarehouseid == $towarehouseid) throw new Exception('Gudang tidak boleh sama.');
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(!is_array($inventories) && count($inventories) == 0) throw new Exception('Barang belum dimasukkan');

  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];
  $fromwarehousename = pmc("select `name` from warehouse where `id` = ?", [ $fromwarehouseid ]);

  $query = "INSERT INTO warehousetransfer (`date`, code, description, fromwarehouseid, towarehouseid, createdon, createdby)
    VALUES (?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($date, $code, $description, $fromwarehouseid, $towarehouseid, $createdon, $createdby));

  try{

    pdo_begin_transaction();

    $totalqty = 0;
    $params = $paramstr = array();
    for($i = 0 ; $i < count($inventories) ; $i++){
      $inventory = $inventories[$i];
      $inventorycode = ov('inventorycode', $inventory);
      $qty = ov('qty', $inventory);
      $remark = ov('remark', $inventory);

      if(empty($inventorycode) && empty($qty) && empty($remark)) continue;
      $inventoryobj = inventorydetail(null, array('code'=>$inventorycode));
      if(!$inventoryobj) throw new Exception("Barang tidak terdaftar. ($inventorycode)");
      if(!intval($qty) || $qty <= 0) throw new Exception('Kuantitas harus diisi.');

      $inventoryid = $inventoryobj['id'];
      $inventorydescription = $inventoryobj['description'];
      $current_qty = pmc("SELECT SUM(`in` - `out`) FROM inventorybalance WHERE `date` <= ? AND warehouseid = ? AND inventoryid = ?", array($date, $fromwarehouseid, $inventoryid));
      if($qty > $current_qty) throw new Exception("Kuantitas $inventorycode - $inventorydescription tidak cukup di gudang $fromwarehousename");

      $paramstr[] = "(?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $qty, $remark);
      $totalqty += $qty;
    }

    if(count($paramstr) > 0){
      $query = "INSERT INTO warehousetransferinventory(warehousetransferid, inventoryid, qty, remark)
        VALUES " . implode(', ', $paramstr);
      pm($query, $params);
    }

    userlog('warehousetransferentry', $warehousetransfer, '', $_SESSION['user']['id'], $id);

    job_create_and_run('warehousetransfer_ext', [ $id ] );

    pdo_commit();

    return array('id'=>$id);
  
  }
  catch(Exception $ex){

    pdo_rollback();

    throw $ex;
  
  }

}
function warehousetransfermodify($warehousetransfer){

  $id = ov('id', $warehousetransfer, 1);
  $current = warehousetransferdetail(null, array('id'=>$id));

  if(!$current) exc('Pindah gudang tidak terdaftar.');

  $updatedrow = array();

  if(isset($warehousetransfer['date']) && strtotime($warehousetransfer['date']) != strtotime($current['date'])){
    if(!isdate($warehousetransfer['date'])) exc('Format tanggal salah');
    $updatedrow['date'] = date('Ymd', strtotime($warehousetransfer['date']));

  }

  if(isset($warehousetransfer['description']) && $warehousetransfer['description'] != $current['description'])
    $updatedrow['description'] = $warehousetransfer['description'];

  if(isset($warehousetransfer['fromwarehousename']) && $warehousetransfer['fromwarehousename'] != $current['fromwarehousename']){
    $fromwarehousename = $warehousetransfer['fromwarehousename'];
    $fromwarehouse = warehousedetail(null, array('name'=>$fromwarehousename));
    if($fromwarehouse)
      $updatedrow['fromwarehouseid'] = $fromwarehouse['id'];
  }

  if(isset($warehousetransfer['towarehousename']) && $warehousetransfer['towarehousename'] != $current['towarehousename']){
    $towarehousename = $warehousetransfer['towarehousename'];
    $towarehouse = warehousedetail(null, array('name'=>$towarehousename));
    if($towarehouse)
      $updatedrow['towarehouseid'] = $towarehouse['id'];
  }

  if(isset($warehousetransfer['fromwarehouseid']) && $warehousetransfer['fromwarehouseid'] != $current['fromwarehouseid']){
    $updatedrow['fromwarehouseid'] = $warehousetransfer['fromwarehouseid'];
  }

  if(isset($warehousetransfer['towarehouseid']) && $warehousetransfer['towarehouseid'] != $current['towarehouseid']){
    $updatedrow['towarehouseid'] = $warehousetransfer['towarehouseid'];
  }

  try{

    pdo_begin_transaction();

    if(count($updatedrow) > 0)
      mysql_update_row('warehousetransfer', $updatedrow, array('id'=>$id));

    if(isset($warehousetransfer['inventories']) && is_array($warehousetransfer['inventories'])){
      $inventories = $warehousetransfer['inventories'];

      $params = $paramstr = array();
      for($i = 0 ; $i < count($inventories) ; $i++){
        $inventory = $inventories[$i];
        $inventorycode = ov('inventorycode', $inventory);
        $qty = ov('qty', $inventory);
        $remark = ov('remark', $inventory);

        if(empty($inventorycode) && empty($qty) && empty($remark)) continue;
        $inventoryobj = inventorydetail(null, array('code'=>$inventorycode));
        if(!$inventoryobj) throw new Exception("Barang tidak terdaftar. ($inventorycode)");
        if(!intval($qty) || $qty <= 0) throw new Exception('Kuantitas harus diisi.');

        $inventoryid = $inventoryobj['id'];

        $paramstr[] = "(?, ?, ?, ?)";
        array_push($params, $id, $inventoryid, $qty, $remark);
      }

      pm("DELETE FROM warehousetransferinventory WHERE warehousetransferid = ?", array($id));
      if(count($paramstr) > 0){
        $query = "INSERT INTO warehousetransferinventory(warehousetransferid, inventoryid, qty, remark)
      VALUES " . implode(', ', $paramstr);
        pm($query, $params);
      }

      $updatedrow['inventories'] = $warehousetransfer['inventories'];
    }

    userlog('warehousetransfermodify', $current, $updatedrow, $_SESSION['user']['id'], $id);

    job_create_and_run('warehousetransfer_ext', [ $id ]);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

  return array('id'=>$id);

}
function warehousetransferremove($filters){

	$warehousetransfer = warehousetransferdetail(null, $filters);

	if(!$warehousetransfer) exc('Pindah gudang tidak terdaftar.');

	$id = $warehousetransfer['id'];

  try{

    pdo_begin_transaction();

    pm("DELETE FROM warehousetransfer WHERE `id` = ?", array($filters['id']));

    userlog('warehousetransferremove', $warehousetransfer, '', $_SESSION['user']['id'], $id);

    job_create_and_run('warehousetransfer_remove_ext', [ $id ]);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

}

function warehousetransfer_ext($id){

  $warehousetransfer = warehousetransferdetail(null, array('id'=>$id));
  $inventories = $warehousetransfer['inventories'];
  $date = $warehousetransfer['date'];
  $fromwarehouseid = $warehousetransfer['fromwarehouseid'];
  $towarehouseid = $warehousetransfer['towarehouseid'];

  $totalqty = 0;
  $inventorybalances = [];
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventory = $inventories[$i];
    $inventoryid = $inventory['inventoryid'];
    $qty = $inventory['qty'];

    $inventorybalances[] = array('inventoryid'=>$inventoryid, 'warehouseid'=>$fromwarehouseid, 'date'=>$date, 'out'=>$qty, 'amount'=>0, 'ref'=>'WT', 'refid'=>$id, 'createdon'=>$warehousetransfer['createdon']);
    $inventorybalances[] = array('inventoryid'=>$inventoryid, 'warehouseid'=>$towarehouseid, 'date'=>$date, 'in'=>$qty, 'amount'=>0, 'ref'=>'WT', 'refid'=>$id, 'createdon'=>$warehousetransfer['createdon']);
    $totalqty += $qty;
  }
  inventorybalanceentries($inventorybalances);

  $query = "UPDATE warehousetransfer SET totalqty = ? WHERE `id` = ?";
  pm($query, array($totalqty, $id));
  
}

function warehousetransfer_remove_ext($id){

  inventorybalanceremove(array('ref'=>'WT', 'refid'=>$id));

}

function warehousetransfer_fix1(){

  // Fix difference between warehousetransferinventory and inventorybalance;
  $query = "select warehousetransferid, count(*) * 2 as cnt1, (select count(*) from inventorybalance where ref = 'WT' and refid = t1.warehousetransferid) as cnt2 from warehousetransferinventory t1 group by warehousetransferid having cnt1 != cnt2;";
  $rows = pmrs($query);
  if(is_array($rows) && count($rows) > 0){
    foreach($rows as $row){
      $id = $row['warehousetransferid'];
      warehousetransfer_ext($id);
      console_log([ 'warehousetransfer_ext', $id ]);
    }
  }
  else{
    console_log([ 'no error' ]);
  }

}

?>