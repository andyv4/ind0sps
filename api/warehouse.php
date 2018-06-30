<?php
require_once dirname(__FILE__) . '/log.php';

function warehouse_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>0, 'name'=>'moved', 'text'=>'Pindah', 'width'=>40, 'type'=>'html', 'html'=>'warehouselist_moved'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>1, 'name'=>'name', 'text'=>'Nama Gudang', 'width'=>300),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function warehousedetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $warehouse = mysql_get_row('warehouse', $filters, array('*'));

  if(is_array($columns) && in_array('detail', $columns)){
    $query = "SELECT ref, description, `date`, `in`, `out` FROM inventorybalance WHERE warehouseid = ? ORDER BY `date` DESC, `id` DESC";
    $warehouse['detail'] = pmrs($query, array($warehouse['id']));
  }
  if(is_array($columns) && in_array('inventories', $columns)){
    $query = "SELECT p1.inventoryid, p2.description, SUM(`in` - `out`) as qty, SUM(p1.amount) as `amount` FROM `inventorybalance` p1, inventory p2
    WHERE p1.warehouseid = ? AND p1.inventoryid = p2.id GROUP BY p1.inventoryid ORDER BY p2.description";
    $warehouse['inventories'] = pmrs($query, array($warehouse['id']));
  }

  return $warehouse;

}
function warehouselist($columns = null, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'isdefault'=>'t1.isdefault',
    'name'=>'t1.name',
    'city'=>'t1.city',
    'country'=>'t1.country',
    'total'=>'t1.total',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.code', 't1.name', 't1.createdby'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limitoffset);

  $query = "SELECT $columnquery FROM warehouse t1 $wherequery $sortquery $limitquery";
  $warehouses = pmrs($query, $params);

  if(in_arrayobject($columns, array('name'=>'createdby'))){
    $users = userlist(null, null);
    $users = array_index($users, array('id'), 1);
    for($i = 0 ; $i < count($warehouses) ; $i++)
      $warehouses[$i]['createdby'] = isset($users[$warehouses[$i]['createdby']]) ? $users[$warehouses[$i]['createdby']]['name'] : '-';
  }

  return $warehouses;

}
function warehouseinventory($columns, $filters){

  $warehouseid = ov('warehouseid', $filters);
  $inventoryid = ov('inventoryid', $filters);

  $query = "SELECT (SUM(`in`) + SUM(`out`)) as `qty` FROM inventorybalance WHERE warehouseid = ? AND inventoryid = ?";
  $qty = intval(pmc($query, array($warehouseid, $inventoryid)));

  return array('qty'=>$qty);
  
}

function warehouseentry($warehouse){

  $lock_file = __DIR__ . "/../usr/system/warehouse_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $code = ov('code', $warehouse);
  $name = ov('name', $warehouse);
  $address = ov('address', $warehouse);
  $city = ov('city', $warehouse);
  $country = ov('country', $warehouse);
  $total = 0;
  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  if(empty($code)) throw new Exception('Kode gudang harus diisi.');
  if(empty($name)) throw new Exception('Nama gudang harus diisi.');
  if(warehousedetail(null, array('code'=>$code))) throw new Exception('Kode gudang sudah ada.');
  if(warehousedetail(null, array('name'=>$name))) throw new Exception('Nama gudang sudah ada.');

  $query = "INSERT INTO warehouse(code, `name`, address, city, country, total, isdefault, createdon, createdby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($code, $name, $address, $city, $country, $total, 0, $createdon, $createdby));

  if(ov('isdefault', $warehouse)) warehouse_default_set($id);

  userlog('warehouseentry', $warehouse, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);
  
}
function warehousemodify($warehouse){

  $id = ov('id', $warehouse, 1);
  $current_warehouse = warehousedetail(null, array('id'=>$id));

  if(!$current_warehouse) throw new Exception('Warehouse not exists');

  $lock_file = __DIR__ . "/../usr/system/warehouse_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();

  if(isset($warehouse['isdefault']) && $current_warehouse['isdefault'] != $warehouse['isdefault'])
    $updatedrow['isdefault'] = $warehouse['isdefault'];

  if(isset($warehouse['code']) && $current_warehouse['code'] != $warehouse['code']){
    if(empty($warehouse['code'])) throw new Exception('Kode gudang harus diisi.');
    if(warehousedetail(null, array('code'=>$warehouse['code']))) throw new Exception('Kode gudang sudah ada.');
    $updatedrow['code'] = $warehouse['code'];
  }

  if(isset($warehouse['name']) && $current_warehouse['name'] != $warehouse['name']){
    if(empty($warehouse['name'])) throw new Exception('Nama gudang harus diisi.');
    if(warehousedetail(null, array('name'=>$warehouse['name']))) throw new Exception('Nama gudang sudah ada.');
    $updatedrow['name'] = $warehouse['name'];
  }

  if(isset($warehouse['address']) && $current_warehouse['address'] != $warehouse['address'])
    $updatedrow['address'] = $warehouse['address'];

  if(isset($warehouse['city']) && $current_warehouse['city'] != $warehouse['city'])
    $updatedrow['city'] = $warehouse['city'];

  if(isset($warehouse['country']) && $current_warehouse['country'] != $warehouse['country'])
    $updatedrow['country'] = $warehouse['country'];

  if(count($updatedrow) > 0){
    $updatedrow['lastupdatedon'] = date('YmdHis');
    mysql_update_row('warehouse', $updatedrow, array('id'=>$id));
  }

  if(ov('isdefault', $warehouse)) warehouse_default_set($id);

  userlog('warehousemodify', $current_warehouse, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);

}
function warehouseremove($filters){

 	$warehouse = warehousedetail(null, $filters);

 	if(!$warehouse) exc('Gudang tidak terdaftar.');

  $id = ov('id', $warehouse);

  $lock_file = __DIR__ . "/../usr/system/warehouse_remove_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

  $query = "DELETE FROM warehouse WHERE `id` = ?";
  pm($query, array($id));

  userlog('warehouseremove', $warehouse, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}
function warehouse_default_set($id){

  $queries = array();
  $queries[] = "UPDATE warehouse SET isdefault = 0";
  $queries[] = "UPDATE warehouse SET isdefault = 1 WHERE `id` = $id";
  mysqli_exec_multiples($queries);

}
function warehousemove($id){

  // Check for existing inventory
  $warehouse = pmr("SELECT moved FROM warehouse WHERE `id` = ?", array($id));
  if(!$warehouse) throw new Exception('Gudang tidak terdaftar');
  if($warehouse['moved']) throw new Exception('Gudang telah dipindah.');

  // Check if exists on second database
  if(intval(pmc("SELECT COUNT(*) FROM indosps2.warehouse WHERE `id` = ?", array($id))) > 0)
    throw new Exception('Gudang sudah dipindah.');

  // Update salesinvoice row
  pm("UPDATE warehouse SET moved = ? WHERE `id` = ?", array(1, $id));

}

function warehouse_updatemovestate(){

  // Fetch indosps2 customers
  $warehouses = pmrs("SELECT `id` FROM indosps2.warehouse");

  $warehouse_ids = array();
  foreach($warehouses as $warehouse)
    $warehouse_ids[] = $warehouse['id'];

  // Update indosps2 customers
  pm("UPDATE warehouse SET moved = 0");
  pm("UPDATE warehouse SET moved = 1 WHERE `id` IN (" . implode(', ', $warehouse_ids) . ")");

}
function warehousecalculate(){

  $warehouses = warehouselist(null, null);
  if(is_array($warehouses))
    for($i = 0 ; $i < count($warehouses) ; $i++){
      $warehouse = $warehouses[$i];
      $warehouseid = $warehouse['id'];

      $query = "UPDATE warehouse SET `total` = (SELECT SUM(`in` - `out`) FROM inventorybalance WHERE warehouseid = ?), `amount` = (SELECT SUM(`amount`) FROM inventorybalance WHERE warehouseid = ?)  WHERE `id` = ?";
      pm($query, array($warehouseid, $warehouseid, $warehouseid));
    }

}
function warehousecalculateall(){

  $date = date('Ymd');
  $warehouses = pmrs("SELECT id FROM warehouse");
  if(is_array($warehouses)){
    $queries = array();
    foreach($warehouses as $warehouse){
      $warehouseid = $warehouse['id'];
      $queries[] = "UPDATE warehouse SET 
				`total` = (SELECT SUM(`in` - `out`) FROM inventorybalance WHERE warehouseid = $warehouseid and `date` <= '$date'), 
				`amount` = (SELECT SUM(`amount`) FROM inventorybalance WHERE warehouseid = $warehouseid and `date` <= '$date')  
				WHERE `id` = $warehouseid";
    }
    mysqli_exec_multiples($queries);
  }

}

?>