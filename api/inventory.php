<?php
require_once dirname(__FILE__) . '/currency.php';
require_once dirname(__FILE__) . '/customer.php';
require_once dirname(__FILE__) . '/warehouse.php';
require_once dirname(__FILE__) . '/salesinvoice.php';
require_once dirname(__FILE__) . '/purchaseinvoice.php';
require_once dirname(__FILE__) . '/intl.php';
require_once dirname(__FILE__) . '/log.php';

$_INVENTORYCOSTPRICES = array();

function inventorydetail($columns = [], $filters){

  if(!is_array($columns)) $columns = [];
  $inventory = mysql_get_row('inventory', $filters, array('*'), null);

  if($inventory){

    $inventoryid = $inventory['id'];

    if(in_array('detail', $columns)) {
      $query = "SELECT * FROM inventorybalance WHERE inventoryid = ? ORDER BY `date`, section, `id`";
      $detail = pmrs($query, array($inventoryid));

      if(in_array('warehousename', $columns)){
        $warehouses = warehouselist(null, null);
        $warehouses = array_index($warehouses, array('id'), 1);
        if(is_array($detail))
          for($i = 0 ; $i < count($detail) ; $i++){
            $detail[$i]['warehousename'] = $warehouses[$detail[$i]['warehouseid']]['name'];
          }
      }

      $inventory['detail'] = $detail;
    }

    $costprice_eligible = true; // privilege_get('inventory', 'costprice');
    if(!$costprice_eligible){
      unset($inventory['avgsalesmargin']);
      unset($inventory['avgcostprice']);
    }

    // category
    if(in_array('categories', $columns) || $columns == '*'){
      $rows = pmrs("select * from categoryinventory where inventoryid = ?", array($inventoryid));
      if($rows){
        $categories = array();
        foreach($rows as $row)
          $categories[] = $row['categoryid'];
        $categories = count($categories) > 0 ? implode(',', $categories) : '';
        $inventory['categories'] = $categories;
      }
    }

    // warehouse
    if(in_array('warehouses', $columns) || $columns == '*'){
      $warehouses = pmrs("select `id`, code, `name` from warehouse");
      foreach($warehouses as $index=>$warehouse){
        $warehouse_qty_total_amount = pmr("SELECT qty, total_amount FROM inventorywarehouse WHERE warehouseid = ? AND inventoryid = ?", [ $warehouse['id'], $inventoryid ]);
        $warehouse['qty'] = isset($warehouse_qty_total_amount['qty']) ? $warehouse_qty_total_amount['qty'] : 0;
        $warehouse['total_amount'] = $warehouse_qty_total_amount['total_amount'] ? $warehouse_qty_total_amount['total_amount'] : 0;
        $warehouses[$index] = $warehouse;
      }
      $inventory['warehouses'] = $warehouses;
    }

    // costprice
    if(in_array('costprices', $columns) || $columns == '*'){
      $detail = pmr("select `id`, detail from inventorybalance where inventoryid = ? order by `date` desc, `section` desc, `id` desc limit 1", [ $inventoryid ]);
      $detail = json_decode($detail['detail'], 1);
      $costprices = $detail['items'];
      $inventory['costprices'] = $costprices;
    }

  }

  return $inventory;
}
function inventorylist($columns, $sorts = null, $filters = null, $limits = null, $groups = null){

  $columns_indexed = array_index($columns, [ 'name' ], 1);
  $soldqty1_date1 = date('Ymd', mktime(0, 0, 0, date('m') - 1, 1, date('Y')));
  $soldqty1_date2 = date('Ymd', mktime(0, 0, 0, date('m'), 0, date('Y')));
  $soldqty2_date1 = date('Ymd', mktime(0, 0, 0, date('m') - 2, 1, date('Y')));
  $soldqty2_date2 = date('Ymd', mktime(0, 0, 0, date('m') - 1, 0, date('Y')));
  $soldqty3_date1 = date('Ymd', mktime(0, 0, 0, date('m') - 3, 1, date('Y')));
  $soldqty3_date2 = date('Ymd', mktime(0, 0, 0, date('m') - 2, 0, date('Y')));
  $soldqty4_date1 = date('Ymd', mktime(0, 0, 0, date('m') - 4, 1, date('Y')));
  $soldqty4_date2 = date('Ymd', mktime(0, 0, 0, date('m') - 3, 0, date('Y')));
  $soldqty5_date1 = date('Ymd', mktime(0, 0, 0, date('m') - 5, 1, date('Y')));
  $soldqty5_date2 = date('Ymd', mktime(0, 0, 0, date('m') - 4, 0, date('Y')));
  $soldqty6_date1 = date('Ymd', mktime(0, 0, 0, date('m') - 6, 1, date('Y')));
  $soldqty6_date2 = date('Ymd', mktime(0, 0, 0, date('m') - 5, 0, date('Y')));

  $inventory_columnaliases = array(
    'taxable'=>'t1.taxable',
    'code'=>'t1.code',
    'isactive'=>'t1.isactive',
    'description'=>'t1.description',
    'unit'=>'t1.unit',
    'qty'=>'t1.qty',
    'price'=>'t1.price',
    'lowestprice'=>'t1.lowestprice',
    'purchaseorderqty'=>'t1.purchaseorderqty',
    'minqty'=>'t1.minqty',
    'avgsalesmargin'=>'t1.avgsalesmargin',
    'handlingfeeunit'=>'t1.handlingfeeunit',
    'createdon'=>'t1.createdon',
    'moved'=>'t1.moved',
    'imageurl'=>'t1.imageurl',
    'website_isactive'=>'t1.website_isactive',
    'soldqty'=>"(SELECT SUM(qty) FROM salesinvoiceinventory i1, salesinvoice i2 WHERE i1.salesinvoiceid = i2.id AND i1.inventoryid = t1.id)",
    'soldqty_1'=>"(SELECT SUM(qty) FROM salesinvoiceinventory i1, salesinvoice i2 WHERE i1.salesinvoiceid = i2.id AND i1.inventoryid = t1.id AND i2.date BETWEEN $soldqty1_date1 AND $soldqty1_date2)`",
    'soldqty_2'=>"(SELECT SUM(qty) FROM salesinvoiceinventory i1, salesinvoice i2 WHERE i1.salesinvoiceid = i2.id AND i1.inventoryid = t1.id AND i2.date BETWEEN $soldqty2_date1 AND $soldqty2_date2)",
    'soldqty_3'=>"(SELECT SUM(qty) FROM salesinvoiceinventory i1, salesinvoice i2 WHERE i1.salesinvoiceid = i2.id AND i1.inventoryid = t1.id AND i2.date BETWEEN $soldqty3_date1 AND $soldqty3_date2)",
    'soldqty_4'=>"(SELECT SUM(qty) FROM salesinvoiceinventory i1, salesinvoice i2 WHERE i1.salesinvoiceid = i2.id AND i1.inventoryid = t1.id AND i2.date BETWEEN $soldqty4_date1 AND $soldqty4_date2)",
    'soldqty_5'=>"(SELECT SUM(qty) FROM salesinvoiceinventory i1, salesinvoice i2 WHERE i1.salesinvoiceid = i2.id AND i1.inventoryid = t1.id AND i2.date BETWEEN $soldqty5_date1 AND $soldqty5_date2)",
    'soldqty_6'=>"(SELECT SUM(qty) FROM salesinvoiceinventory i1, salesinvoice i2 WHERE i1.salesinvoiceid = i2.id AND i1.inventoryid = t1.id AND i2.date BETWEEN $soldqty6_date1 AND $soldqty6_date2)",
    'categories'=>"(select group_concat(`name` separator ', ') from category s1, categoryinventory s2 where s1.id = s2.categoryid and s2.inventoryid = t1.id)",
  );
  if(privilege_get('inventory', 'costprice')) $inventory_columnaliases['avgcostprice'] = 't1.avgcostprice';

  if(isset($columns_indexed['unitvolume'])){
    $inventory_columnaliases['unitvolume'] = "(SELECT m3 FROM inventoryformula WHERE inventoryid = t1.id ORDER BY `date` DESC LIMIT 1)";
  }

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $inventory_columnaliases);
  $warehouses = pmrs("SELECT `id` FROM warehouse");
  foreach($warehouses as $warehouse)
    $inventory_columnaliases['qty_' . $warehouse['id']] = 'qty_' . $warehouse['id'];
  $wherequery = wherequery_from_filters($params, $filters, $inventory_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $inventory_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $warehouseids = [];
  foreach($columns_indexed as $column_indexed){
    if(strpos($column_indexed['name'], 'qty_') !== false && isset($column_indexed['active']) && $column_indexed['active'] > 0)
      $warehouseids[] = str_replace('qty_', '', $column_indexed['name']);
  }
  if(count($warehouseids) > 0){
    $columnquery_warehouse = array();
    foreach($warehouseids as $warehouseid){
      $columnquery_warehouse[] = "(SELECT qty FROM inventorywarehouse WHERE warehouseid = $warehouseid AND inventoryid = t1.id) as qty_" . $warehouseid;
    }
    $columnquery_warehouse = implode(', ', $columnquery_warehouse);
    if(strlen($columnquery_warehouse) > 0) $columnquery .= ', ' . $columnquery_warehouse;
  }

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $query = "SELECT 'inventory' as `type`, t1.id $columnquery FROM inventory t1 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);

//  if(count($warehouseids) > 0 && is_array($data) && count($data) > 0){
//
//    $date = date('Ymd');
//    $warehouse_qtys = pmrs("SELECT warehouseid, inventoryid, SUM(`in` - `out`) as qty FROM inventorybalance WHERE `date` <= '$date'
//      AND warehouseid in (" . implode(', ', $warehouseids) . ") GROUP BY warehouseid, inventoryid");
//    $warehouse_qtys = array_index($warehouse_qtys, [ 'inventoryid', 'warehouseid' ], 1);
//
//    for($i = 0 ; $i < count($data) ; $i++){
//
//      $id = $data[$i]['id'];
//      foreach($warehouseids as $warehouseid){
//        $data[$i]['qty_' . $warehouseid] = isset($warehouse_qtys[$id][$warehouseid]['qty']) ? $warehouse_qtys[$id][$warehouseid]['qty'] : 0;
//      }
//
//    }
//
//  }

  return $data;

}
function inventorymutation($columns, $sorts, $filters, $limits = null){

  $columnaliases = [
    'id'=>'t1.id!',
    'inventoryid'=>'t1.inventoryid',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'in'=>'t1.in',
    'out'=>'t1.out',
    'unitamount'=>'t1.unitamount',
    'warehousename'=>'t2.name'
  ];

  $params = [];
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases);
  $wherequery = 'WHERE t1.warehouseid = t2.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases, $columns));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT $columnquery FROM inventorybalance t1, warehouse t2 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}
function inventorycostpricelist($columns, $sorts, $filters, $limits = null){

  $columnaliases = [
    'id'=>'t1.id!',
    'inventoryid'=>'t1.inventoryid',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'in'=>'t1.in',
    'out'=>'t1.out',
    'unitamount'=>'t1.unitamount',
    'warehousename'=>'t2.name',
    'ref'=>'t1.ref',
    'refid'=>'t1.refid',
    'code'=>"(SELECT code FROM purchaseinvoice WHERE `id` = t1.refid)",
    'description'=>"(SELECT supplierdescription FROM purchaseinvoice WHERE `id` = t1.refid)",
  ];

  $params = [];
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases);
  $wherequery = "where t1.warehouseid = t2.id and t1.ref = 'PI'" . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases, $columns));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT $columnquery FROM inventorybalance t1, warehouse t2 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function inventoryentry($inventory){

  $taxable = ov('taxable', $inventory, 0, 0);
  $taxable_excluded = ov('taxable_excluded', $inventory, 0, 0);
  $code = ov('code', $inventory);
  $description = ov('description', $inventory);
  $unit = ov('unit', $inventory);
  $handlingfeeunit = ov('handlingfeeunit', $inventory);
  $categories = ov('categories', $inventory);
  $imageurl = ov('imageurl', $inventory);
  $fulldescription = ov('fulldescription', $inventory);
  $website_isactive = ov('website_isactive', $inventory);

  if(empty($code)) throw new Exception(excmsg('i00'));
  if(empty($description)) throw new Exception(excmsg('i01'));
  if(empty($unit)) throw new Exception(excmsg('i02'));
  if(inventorydetail(null, array('code'=>$code))) throw new Exception(excmsg('i03', [ 'code'=>$code, 'description'=>$description ]));
  if(inventorydetail(null, array('description'=>$description, 'taxable'=>$taxable))) throw new Exception(excmsg('i03', [ 'code'=>$code, 'description'=>$description ]));

  $isactive = 1;
  $price = ov('price', $inventory, 0, 0);
  $lowestprice = ov('lowestprice', $inventory, 0, 0);
  $salesbelowmargin = false;
  $qty = $minqty = 0;
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  $lock_file = __DIR__ . "/../usr/system/inventory_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $id = pmi("INSERT INTO inventory(
      isactive, taxable, code, description, fulldescription, unit, qty, minqty, price, lowestprice, salesbelowmargin, handlingfeeunit,
      imageurl, website_isactive, createdon, createdby, taxable_excluded
    ) 
    VALUES (
      ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
      ?, ?, ?, ?, ?
    )",
    array(
      $isactive, $taxable, $code, $description, $fulldescription, $unit, $qty, $minqty, $price, $lowestprice, $salesbelowmargin, $handlingfeeunit,
      $imageurl, $website_isactive, $createdon, $createdby, $taxable_excluded
    )
  );

  if(strlen($categories) > 0){
    $categories = explode(',', $categories);
    if(count($categories) > 0){
      pm("delete from categoryinventory where inventoryid = ?", array($id));
      for($i = 0 ; $i < count($categories) ; $i++){
        $categoryid = $categories[$i];
        pm("insert into categoryinventory(categoryid, inventoryid) values (?, ?) on duplicate key update inventoryid = ?",
            array($categoryid, $id, $id));
      }
    }
  }

  userlog('inventoryentry', $inventory, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;

}
function inventorymodify($inventory){

  $id = ov('id', $inventory, 1);
  $current_inventory = inventorydetail(null, array('id'=>$id));
  $taxable = ova('taxable', $inventory, $current_inventory);

  if(!$current_inventory) exc('Barang tidak terdaftar.');

  $lock_file = __DIR__ . "/../usr/system/inventory_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();

  if(isset($inventory['isactive']) && $current_inventory['isactive'] != $inventory['isactive'])
    $updatedrow['isactive'] = $inventory['isactive'];

  if(isset($inventory['taxable']) && $current_inventory['taxable'] != $inventory['taxable'])
    $updatedrow['taxable'] = $inventory['taxable'];

  if(isset($inventory['taxable_excluded']) && $current_inventory['taxable_excluded'] != $inventory['taxable_excluded'])
    $updatedrow['taxable_excluded'] = $inventory['taxable_excluded'];

  if(isset($inventory['code']) && $current_inventory['code'] != $inventory['code']){
    if(empty($inventory['code'])) throw new Exception(excmsg('i00'));
    if(inventorydetail(null, array('code'=>$inventory['code']))) throw new Exception(excmsg('i03'));
    $updatedrow['code'] = $inventory['code'];
  }

  if(isset($inventory['description']) && $current_inventory['description'] != $inventory['description']){
    if(empty($inventory['description'])) throw new Exception(excmsg('i01'));
    if(inventorydetail(null, array('description'=>$inventory['description'], 'taxable'=>$taxable))) throw new Exception(excmsg('i03'));
    $updatedrow['description'] = $inventory['description'];
  }

  if(isset($inventory['fulldescription']) && $inventory['fulldescription'] != $current_inventory['fulldescription'])
    $updatedrow['fulldescription'] = $inventory['fulldescription'];

  if(isset($inventory['unit']) && $current_inventory['unit'] != $inventory['unit']){
    if(empty($inventory['unit'])) throw new Exception(excmsg('i02'));
    $updatedrow['unit'] = $inventory['unit'];
  }

  if(isset($inventory['minqty']) && $current_inventory['minqty'] != $inventory['minqty'])
    $updatedrow['minqty'] = $inventory['minqty'];

  if(isset($inventory['price']) && $current_inventory['price'] != $inventory['price'])
    $updatedrow['price'] = $inventory['price'];

  if(isset($inventory['lowestprice']) && $current_inventory['lowestprice'] != $inventory['lowestprice'])
    $updatedrow['lowestprice'] = $inventory['lowestprice'];

  if(isset($inventory['salesbelowmargin']) && $current_inventory['salesbelowmargin'] != $inventory['salesbelowmargin'])
    $updatedrow['salesbelowmargin'] = $inventory['salesbelowmargin'];

  if(isset($inventory['handlingfeeunit']) && $current_inventory['handlingfeeunit'] != $inventory['handlingfeeunit'])
    $updatedrow['handlingfeeunit'] = $inventory['handlingfeeunit'];

  if(isset($inventory['imageurl']) && $inventory['imageurl'] != $current_inventory['imageurl'])
    $updatedrow['imageurl'] = $inventory['imageurl'];

  if(isset($inventory['website_isactive']) && $inventory['website_isactive'] != $current_inventory['website_isactive'])
    $updatedrow['website_isactive'] = $inventory['website_isactive'];

  if(count($updatedrow) > 0)
    mysql_update_row('inventory',$updatedrow, array('id'=>$id));

  if(isset($inventory['categories'])){
    $categories = $inventory['categories'];
    if(strlen($categories) > 0){
      $categories = explode(',', $categories);
      if(count($categories) > 0){
        pm("delete from categoryinventory where inventoryid = ?", array($id));
        for($i = 0 ; $i < count($categories) ; $i++){
          $categoryid = $categories[$i];
          pm("insert into categoryinventory(categoryid, inventoryid) values (?, ?) on duplicate key update inventoryid = ?",
              array($categoryid, $id, $id));
        }
      }
      $updatedrow['categories'] = $categories;
    }
  }

  userlog('inventorymodify', $current_inventory, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;

}
function inventoryremove($filters){

  if(isset($filters['id'])){

    $id = ov('id', $filters);
    $current_inventory = inventorydetail(null, array('id'=>$id));

    if(!$current_inventory) exc('Barang tidak terdaftar.');

    $lock_file = __DIR__ . "/../usr/system/inventory_remove_" . $id . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    $query = "DELETE FROM inventory WHERE `id` = ?";
    pm($query, array($id));

    userlog('inventoryremove', $current_inventory, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }
  
}

function inventorybalancelist($columns = null, $filters){

  if(isset($filters['id'])){

    return pmr("SELECT t1.*, t2.code as inventorycode, t2.description as inventorydescription 
      FROM inventorybalance t1, inventory t2 WHERE t1.id = ? and t1.inventoryid = t2.id",
      array($filters['id']));

  }
  else if(isset($filters['ids'])){

    $ids = array();
    foreach($filters['ids'] as $id)
      $ids[] = $id;

    if(count($ids) > 0)
      return pmrs("SELECT t1.*, t2.code as inventorycode, t2.description as inventorydescription 
      FROM inventorybalance t1, inventory t2 WHERE t1.id IN (" . implode(', ', $ids) . ") and t1.inventoryid = t2.id");
    return null;

  }
  else if(isset($filters['ref']) && isset($filters['refid'])){

    $ref = $filters['ref'];
    $refid = $filters['refid'];
    return pmrs("SELECT t1.*, t2.code as inventorycode, t2.description as inventorydescription 
      FROM inventorybalance t1, inventory t2 WHERE t1.ref = ? AND t1.refid = ? and t1.inventoryid = t2.id",
      array($ref, $refid));

  }

}

function inventorybalanceentry($inventorybalance){

  $fp = acquire_lock(__FUNCTION__);

  // Required parameters
  $inventoryid = ov('inventoryid', $inventorybalance, 1);
  $warehouseid = ov('warehouseid', $inventorybalance, 1);
  $date = ov('date', $inventorybalance, 1, array('type'=>'date'));
  $in = ov('in', $inventorybalance, 0, 0);
  $out = ov('out', $inventorybalance, 0, 0);
  $ref = ov('ref', $inventorybalance, 1);
  $refid = ov('refid', $inventorybalance, 1);
  $refitemid = ov('refitemid', $inventorybalance, 1);
  $description = ov('description', $inventorybalance, 0, '');
  $amount = ov('amount', $inventorybalance, 0, 0);
  $createdon = $lastupdatedon = date('YmdHis');
  $section = ov('section', $inventorybalance, 0, 0);
  $autoamount = $amount > 0 ? 0 : 1;
  $qty = $in > 0 ? $in : $out;
  if($qty > 0) $unitamount = round($amount / $qty, 2); else $unitamount = 0;
  if($autoamount && $section < 1) $section = 1;

  if($out > 0 && $amount > 0) $amount = $amount * -1;
  if($in > 0 && $amount < 0) $amount = $amount * -1;

  $query = "INSERT INTO inventorybalance(inventoryid, `date`, warehouseid, `section`, description, `in`, `out`, unitamount, 
        autoamount, `amount`, `ref`, `refid`, refitemid, createdon, lastupdatedon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $params = [ $inventoryid, $date, $warehouseid, $section, $description, $in, $out, $unitamount, $autoamount,
    $amount, $ref, $refid, $refitemid, $createdon, $lastupdatedon ];
  console_info([ $query, $params ]);
  pm($query, $params);

  release_lock($fp, __FUNCTION__);

}
function inventorybalanceentries($inventorybalances){

  $lock_file = __DIR__ . "/../usr/system/inventorybalance_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  foreach($inventorybalances as $inventorybalance)
    pm("delete from inventorybalance where ref =  ? and refid = ?", [ $inventorybalance['ref'], $inventorybalance['refid']]);

  foreach($inventorybalances as $inventorybalance){

    // Required parameters
    $inventoryid = ov('inventoryid', $inventorybalance, 1);
    $warehouseid = ov('warehouseid', $inventorybalance, 1);
    $date = ov('date', $inventorybalance, 1, array('type'=>'date'));
    $in = ov('in', $inventorybalance, 0, 0);
    $out = ov('out', $inventorybalance, 0, 0);
    $ref = ov('ref', $inventorybalance, 1);
    $refid = ov('refid', $inventorybalance, 1);
    $refitemid = ov('refitemid', $inventorybalance, 0, 0);
    $description = ov('description', $inventorybalance, 0, '');
    $amount = ov('amount', $inventorybalance, 0, 0);
    $createdon = $lastupdatedon = date('YmdHis');
    $section = ov('section', $inventorybalance, 0, 0);
    $autoamount = $amount > 0 ? 0 : 1;
    $qty = $in > 0 ? $in : $out;
    if($qty > 0) $unitamount = round($amount / $qty, 2); else $unitamount = 0;
    if($autoamount && $section < 1) $section = 1;

    if($out > 0 && $amount > 0) $amount = $amount * -1;
    if($in > 0 && $amount < 0) $amount = $amount * -1;

    $query = "INSERT INTO inventorybalance(inventoryid, `date`, warehouseid, `section`, description, `in`, `out`, unitamount, 
        autoamount, `amount`, `ref`, `refid`, refitemid, createdon, lastupdatedon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    pm($query, [ $inventoryid, $date, $warehouseid, $section, $description, $in, $out, $unitamount, $autoamount,
      $amount, $ref, $refid, $refitemid, $createdon, $lastupdatedon ]);

  }

  fclose($fp);
  unlink($lock_file);

}
function inventorybalanceremove($filters){

  if(isset($filters['ref']) && isset($filters['refid'])){

    $lock_file = __DIR__ . "/../usr/system/inventorybalance_remove_" . $filters['refid'] . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    $ref = $filters['ref'];
    $refid = $filters['refid'];
    pm("delete from inventorybalance where `ref` = ? and refid = ?", [ $ref, $refid ]);

    fclose($fp);
    unlink($lock_file);

  }

  else if(isset($filters['ref']) && isset($filters['refitemid'])){

    $ref = $filters['ref'];
    $refitemid = $filters['refitemid'];
    pm("delete from inventorybalance where `ref` = ? and refitemid = ?", [ $ref, $refitemid ]);

  }

}
function inventorybalancemodify($idObj, $inventorybalance){

  // Required parameters
  $inventoryid = ov('inventoryid', $inventorybalance, 1);
  $warehouseid = ov('warehouseid', $inventorybalance, 1);
  $date = ov('date', $inventorybalance, 1, array('type'=>'date'));
  $description = ov('description', $inventorybalance, 0, '');
  $section = ov('section', $inventorybalance, 0, 0);
  $in = ov('in', $inventorybalance, 0, 0);
  $out = ov('out', $inventorybalance, 0, 0);
  $amount = ov('amount', $inventorybalance, 0, 0);
  $autoamount = $amount > 0 ? 0 : 1;
  $qty = $in > 0 ? $in : $out;
  if($qty > 0) $unitamount = round($amount / $qty, 2); else $unitamount = 0;
  $ref = ov('ref', $inventorybalance, 1);
  $refid = ov('refid', $inventorybalance, 1);
  $lastupdatedon = date('YmdHis');

  $inventorybalance['lastupdatedon'] = $lastupdatedon;
  mysql_update_row('inventorybalance', $inventorybalance, $idObj);

}
function inventorymove($id){

  // Check for existing inventory
  $inventory = pmr("SELECT moved FROM inventory WHERE `id` = ?", array($id));
  if(!$inventory) throw new Exception('Barang tidak terdaftar');
  if($inventory['moved']) throw new Exception('Barang telah dipindah.');

  // Check if exists on second database
  if(intval(pmc("SELECT COUNT(*) FROM indosps2.inventory WHERE `id` = ?", array($id))) > 0)
    throw new Exception('Barang sudah dipindah.');

  // Move to next database
  pm("INSERT INTO indosps2.inventory SELECT * FROM inventory WHERE `id` = ?", array($id));

  // Update salesinvoice row
  pm("UPDATE inventory SET moved = ? WHERE `id` = ?", array(1, $id));

}
function inventory_updatemovestate(){

  // Fetch indosps2 customers
  $inventories = pmrs("SELECT `id` FROM indosps2.inventory");

  $inventory_ids = array();
  foreach($inventories as $inventory)
    $inventory_ids[] = $inventory['id'];

}

function inventoryqty_calculate($inventoryids){

  if(is_array($inventoryids) && count($inventoryids) > 0){

    $date = date('Ymd');
    //$query = "UPDATE inventory t1 SET qty = (select qty from inventorybalance where inventoryid = t1.id and `date` <= '$date' ORDER BY `date` DESC, `id` DESC limit 1) WHERE t1.id IN (" . implode(', ', $inventoryids) . ");";
    $query = "UPDATE inventory t1 SET qty = (select SUM(`in` - `out`) from inventorybalance where inventoryid = t1.id and `date` <= ?) WHERE t1.id IN (" . implode(', ', $inventoryids) . ");";
    pm($query, [ $date ]);

  }

}
function inventoryqty_calculateall(){

  $date = date('Ymd');

//  $inventory_qtys = pmrs("SELECT inventoryid, SUM(`in` - `out`) as qty FROM inventorybalance WHERE `date` <= '$date' GROUP BY inventoryid");
//  $inventory_qtys = array_index($inventory_qtys, [ 'inventoryid' ], 1);
//  exc($inventory_qtys);

//  $query = "update inventory t1 set qty = (select qty from inventorybalance where inventoryid = t1.id and `date` <= ? order by `date` desc, `section` desc, `id` desc limit 1);";
//  $params = [ $date ];
  $query = "update inventory t1 set qty = (select SUM(`in` - `out`) from inventorybalance where inventoryid = t1.id and `date` <= ?);";
  $params = [ $date ];
  pm($query, $params);

}
function inventorysoldqty_calculate($inventoryids){

  if(is_array($inventoryids) && count($inventoryids) > 0){
    $inventoryids = implode(', ', $inventoryids);
    $query = "update inventory t1 set soldqty = (select abs(sum(`out`)) from inventorybalance where
    inventoryid = t1.id and ref = 'SI') where `id` in (" . $inventoryids . ")";
    pm($query);
  }

}
function inventorysoldqty_calculateall(){

  $query = "update inventory t1 set soldqty = (select abs(sum(`out`)) from inventorybalance where
    inventoryid = t1.id and ref = 'SI')";
  pm($query);

}

function inventoryqty($inventoryid, $warehouseid, $date){

  $qty = pmc("SELECT SUM(`in` - `out`) FROM inventorybalance WHERE inventoryid = ? AND warehouseid = ? AND `date` <= ? ORDER BY `date`, autoamount, `id`", array($inventoryid, $warehouseid, $date));
  return $qty;

}
function inventory_purchaseorderqty(){

  $rows = pmrs("SELECT t2.inventoryid, SUM(t2.qty) as qty FROM purchaseorder t1, purchaseorderinventory t2
    WHERE t1.id = t2.purchaseorderid AND t1.isinvoiced <> 1 GROUP BY t2.inventoryid");

  $queries = $params = [];

  $queries[] = "UPDATE inventory SET purchaseorderqty = 0";

  if(is_array($rows))
    for($i = 0 ; $i < count($rows) ; $i++){
      $row = $rows[$i];
      $inventoryid = $row['inventoryid'];
      $qty = $row['qty'];
      $queries[] = "UPDATE inventory SET purchaseorderqty = ? WHERE `id` = ?";
      array_push($params, $qty, $inventoryid);
    }

  if(count($queries) > 0) pm(implode(';', $queries), $params);
  
}

function inventorycostprice($id = null, $date = null){

  $method = systemvarget('costprice_method', 'average');

  switch($method){

    case 'fifo':
      inventorycostprice_fifo($id, $date);
      break;

    default:
      inventorycostprice_avg($id, $date);
      break;

  }

}
function inventorycostprice_fifo($id = null, $date = null){

  if(!$id){
    $ids = pmrs("select `id` from inventory");
    foreach($ids as $obj)
      inventorycostprice_fifo_id($obj['id'], $date);
  }
  else
    inventorycostprice_fifo_id($id, $date);

}
function inventorycostprice_fifo_id($id, $date = null){

  /*
   * detail:
   * - qty:double
   * - costprice:double
   * - deficit:double
   * - items:array
   *   - qty:double
   *   - price:double
   *   - date:string (y-m-d)
   */

  if(!$date) $date = date('Y-m-d', strtotime(pmc("select min(createdon) from inventorybalance where inventoryid = ?", [ $id ])));

  // Get previous detail
  $row_prev = pmr("select qty, detail from inventorybalance where inventoryid = ? and `date` < ? order by `date` desc, `id` desc limit 1", [ $id, $date ]);
  if(!$row_prev['detail']) $detail = [ 'qty'=>0, 'costprice'=>0, 'deficit'=>0, 'items'=>[] ];
  else $detail = json_decode($row_prev['detail'], true);

  $current_qty = $row_prev['qty'];

  // Update every inventorybalance from date
  $rows = pmrs("select * from inventorybalance where inventoryid = ? and `date` >= ? order by `date`, `section`, `id`", [ $id, $date ]);

  $queries = $params = [];
  for($i = 0 ; $i < count($rows) ; $i++){

    $row = $rows[$i];
    $rowid = $row['id'];
    $date = $row['date'];
    $section = $row['section'];
    $ref = $row['ref'];
    $refid = $row['refid'];
    $refitemid = $row['refitemid'];
    $unitamount = $row['unitamount'];
    $in = $row['in'];
    $out = $row['out'];
    $autoamount = $row['autoamount'];

    if($in > 0){

      switch($ref){

        case 'IA':

          $iadetail = pmr("select code from inventoryadjustment where `id` = ?", [ $refid ]);

          $detail['items'][] =  [
            'qty'=>$in,
            'price'=>$unitamount,
            'date'=>date('Ymd', strtotime($date)),
            'detail'=>$iadetail
          ];
          break;
        case 'PI':
          $purchaseinvoicecostprice = purchaseinvoice_get_costprice($refid, $refitemid);
          $unitamount = $purchaseinvoicecostprice['unitamount'];
          $detail['items'][] = [
            'qty'=>$in,
            'price'=>$unitamount,
            'date'=>date('Ymd', strtotime($date)),
            'detail'=>$purchaseinvoicecostprice
          ];
          break;

      }

    }
    else if($out > 0){

      switch($ref){

        case 'SI':
        case 'IA':
        case 'SJS':

          //echo json_encode([ $out, $detail['deficit'] ]) . PHP_EOL;

          // Decrease deficit items
          if($detail['deficit'] > 0) {
            for($j = 0 ; $j < count($detail['items']) ; $j++) {
              if ($detail['items'][$j]['qty'] < $detail['deficit']) {
                $detail['deficit'] -= $detail['items'][$j]['qty'];
              } else {
                $detail['items'][$j]['qty'] -= $detail['deficit'];
                $detail['deficit'] = 0;
              }
            }
          }

          // Remove empty detail items
          $temp = [];
          foreach($detail['items'] as $detailitem)
            if($detailitem['qty'] > 0) $temp[] = $detailitem;
          $detail['items'] = $temp;

          $qty = $out;
          $totalprice = 0;
          for($j = 0 ; $j < count($detail['items']) ; $j++){
            if($qty > 0){
              if($detail['items'][$j]['qty'] < $qty){
                $qty -= $detail['items'][$j]['qty'];
                $totalprice += $detail['items'][$j]['qty'] * $detail['items'][$j]['price'];
                $detail['items'][$j]['qty'] = 0;
              }
              else{
                $detail['items'][$j]['qty'] -= $qty;
                $totalprice += $qty * $detail['items'][$j]['price'];
                $qty = 0;
              }
            }
          }
          if($qty > 0)
            $detail['deficit'] += $qty;

          $unitamount = $out > 0 ? round($totalprice / $out, 2) : 0;
          // Update salesinvoiceinventory
          $queries[] = "update salesinvoiceinventory set costprice = ?, margin = (unitprice - ?) / ? * 100 where salesinvoiceid = ? and inventoryid = ?";
          array_push($params, $unitamount, $unitamount, $unitamount, $refid, $id);
          $salesinvoiceids[] = $refid;

          break;

      }

    }

    $current_qty += $in > 0 ? $in : ($out * -1);

    // Remove empty detail items
    $temp = [];
    foreach($detail['items'] as $detailitem)
      if($detailitem['qty'] > 0) $temp[] = $detailitem;
    $detail['items'] = $temp;

    // Calculate fifo detail
    $qty = $costprice = 0;
    for($j = 0 ; $j < count($detail['items']) ; $j++){
      $detailitem = $detail['items'][$j];
      $qty += $detailitem['qty'];
      if($j == 0) $costprice = $detailitem['price'];
    }
    $detail['qty'] = $qty;
    $detail['costprice'] = $costprice;

    $queries[] = "update inventorybalance set unitamount = ?, amount = ?, qty = ?, costprice = ?, detail = ?
      where `id` = ?";
    array_push($params, $unitamount, $unitamount * (abs($in - $out)), $current_qty, $costprice, json_encode($detail),
      $rowid);

    if(count($queries) > 0){
      pm(implode(';', $queries), $params);
      $queries = $params = [];
    }

  }

  $queries[] = "update inventory t1 set avgcostprice = 
    (select costprice from inventorybalance where inventoryid = t1.id order by `date` desc, `section` desc, `id` desc limit 1)
    where `id` = ?";
  array_push($params, $id);

  if(count($queries) > 0)
    pm(implode(';', $queries), $params);

  // Update salesinvoice
  if(isset($salesinvoiceids) && is_array($salesinvoiceids) && count($salesinvoiceids) > 0)
    pm("update salesinvoice t1 set avgsalesmargin = (select avg(margin) from salesinvoiceinventory where salesinvoiceid = t1.id)
      where `id` in (" . implode(', ', $salesinvoiceids) . ")");


}
function inventorycostprice_avg($id = null, $date){


}
function inventorycostprice_get($id, $date = null){

  if(!$date) $date = date('Ymd');
  $costprice = pmc("select costprice from inventorybalance where inventoryid = ? and `date` <= ?
    order by `date` desc, `section` desc, `id` desc limit 1", [ $id, $date ]);
  return $costprice;

}

function inventoryanalysisgenerate(){

  $suppliers = pmrs("select `id`, description from supplier");

  $qty_ordered_ = [];
  $sum_qty_ordered_ = [];
  $max_if_supplier_qty = [];
  $t2_qty_ordered_ = [];
  if(is_array($suppliers))
    foreach($suppliers as $supplier){
      $supplierid = $supplier['id'];
      $sum_qty_ordered_[] = 'SUM(qty_ordered_' . $supplierid . ') as qty_ordered_' . $supplierid;
      $t2_qty_ordered_[] = 't2.qty_ordered_' . $supplierid;

      $qty_ordered_[] = 'qty_ordered_' . $supplierid . ' DOUBLE(16,2)';
      $max_if_supplier_qty[] = "MAX(IF(supplierid = $supplierid, qty, 0)) as qty_ordered_" . $supplierid;
    }

  $uid = 7;

  pm("DROP TABLE IF EXISTS vv_inventory$uid");
  pm("DROP TABLE IF EXISTS vv_inventory_sold_per_customer_per_month$uid");
  pm("DROP TABLE IF EXISTS vv_inventory_purchased_per_supplier_per_month$uid");
  pm("DROP TABLE IF EXISTS vv_inventory_ordered$uid");

  pm("CREATE TABLE IF NOT EXISTS vv_inventory_sold_per_customer_per_month$uid (
    inventoryid INT(10),
    customerid INT(10),
    `year` INT(4),
    `month` INT(2),
    `qty` DOUBLE(16,2),
    PRIMARY KEY (inventoryid, customerid, `year`, `month`)
  )ENGINE=InnoDB, DEFAULT CHARSET=utf8");

  pm("INSERT INTO vv_inventory_sold_per_customer_per_month$uid
    SELECT t2.inventoryid, t1.customerid, YEAR(`date`), MONTH(`date`), SUM(qty) as `qty` 
    FROM salesinvoice t1, salesinvoiceinventory t2
    WHERE t1.id = t2.salesinvoiceid
    GROUP BY inventoryid, t1.customerid, YEAR(`date`), MONTH(`date`)
    ORDER BY inventoryid, t1.customerid, YEAR(`date`), MONTH(`date`)");

  pm("CREATE TABLE vv_inventory_purchased_per_supplier_per_month$uid (
    inventoryid INT(10),
    supplierid INT(10),
    `year` INT(4),
    `month` INT(2),
    `qty` DOUBLE(16,2),
    PRIMARY KEY (inventoryid, supplierid, `year`, `month`)
  )ENGINE=InnoDB, DEFAULT CHARSET=utf8");

  pm("INSERT INTO vv_inventory_purchased_per_supplier_per_month$uid
    SELECT t2.inventoryid, t1.supplierid, YEAR(`date`), MONTH(`date`), SUM(qty) as `qty` 
    FROM purchaseinvoice t1, purchaseinvoiceinventory t2
    WHERE t1.id = t2.purchaseinvoiceid AND t1.supplierid is not null
    GROUP BY inventoryid, t1.supplierid, YEAR(`date`), MONTH(`date`)
    ORDER BY inventoryid, t1.supplierid, YEAR(`date`), MONTH(`date`)
  ");

  pm("CREATE TABLE vv_inventory_ordered$uid (
    inventoryid INT(10),    
    qty_ordered DOUBLE(16,2),
    " . implode(', ', $qty_ordered_) . ",
    PRIMARY KEY (inventoryid)
    )ENGINE=InnoDB, DEFAULT CHARSET=utf8
  ");

  pm("INSERT INTO vv_inventory_ordered$uid 
    SELECT 
    inventoryid,
    SUM(qty),
    " . implode(', ', $max_if_supplier_qty) . "
    FROM
    (
    SELECT t1.inventoryid, t2.supplierid, SUM(t1.qty) as qty
    FROM purchaseorderinventory t1, purchaseorder t2
    WHERE t1.purchaseorderid = t2.id AND t1.inventoryid IS NOT NULL AND t2.isinvoiced = 0
    GROUP BY t1.inventoryid, t2.supplierid
    ) as t1
    GROUP BY inventoryid
  ");

  $row = pmr("select " . implode(', ', $sum_qty_ordered_) . " from vv_inventory_ordered$uid");
  $vv_inventory_qty_ordered_ = [];
  $vv_inventory_qty_purchased_ = [];
  $vv_inventory_t2_qty_ordered_ = [];
  foreach($row as $key=>$value)
    if($value > 0 || $value < 0){
      $vv_inventory_qty_ordered_[] = $key . ' DOUBLE(16,2)';
      $vv_inventory_qty_purchased_[] = 'qty_purchased_' . (str_replace('qty_ordered_', '', $key)) . ' DOUBLE(16,2)';
      $vv_inventory_t2_qty_ordered_[] = $key;
      $vv_inventory_t2_qty_purchased_[] = "(SELECT SUM(qty) FROM purchaseinvoice u1, purchaseinvoiceinventory u2
        WHERE u1.id = u2.purchaseinvoiceid AND u1.supplierid = " . (str_replace('qty_ordered_', '', $key)) . " AND u2.inventoryid = t1.id)";
    }
  $vv_inventory_months = [];
  $vv_inventory_months_queries = [];
  $vv_avg_months = [];

  for($i = 6 ; $i >= 1 ; $i--){
    $timestamp = date('Ymd', mktime(0, 0, 0, date('m') - $i, date('j'), date('Y')));
    $timestamp2 = date('Ym', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));

    $vv_inventory_months[] = 'qty_sold_' . $timestamp2 . ' DOUBLE(16,2)';
    $vv_inventory_months_queries[] = "(SELECT SUM(qty) FROM vv_inventory_sold_per_customer_per_month$uid WHERE inventoryid = t1.id 
      AND `year` = " . date('Y', strtotime($timestamp)) . " AND `month` = " . date('m', strtotime($timestamp)) . ")";
    $vv_avg_months[] = "(`month` = " . date('n', strtotime($timestamp)) . " AND `year` = " . date('Y', strtotime($timestamp)) . ")";
  }

  pm("DROP VIEW IF EXISTS vw_inventory_sold_per_months$uid");
  pm("
  CREATE VIEW vw_inventory_sold_per_months$uid
  AS
  SELECT inventoryid, `year`, `month`, SUM(qty) as qty 
  FROM vv_inventory_sold_per_customer_per_month$uid 
  GROUP BY inventoryid, `year`, `month`
  ");

  pm("DROP VIEW IF EXISTS vw_inventory_purchased_per_months$uid");
  pm("
  CREATE VIEW vw_inventory_purchased_per_months$uid
  AS
  SELECT inventoryid, `year`, `month`, SUM(qty) as qty 
  FROM vv_inventory_purchased_per_supplier_per_month$uid
  GROUP BY inventoryid, `year`, `month`
  ");

  pm("CREATE TABLE vv_inventory$uid(
    `id` INT(10),
    code VARCHAR(10),
    description VARCHAR(100),
    unit VARCHAR(10),
    qty_in_stock DOUBLE(16,2),
    n_days_remaining_stock INT(10),
    avg_qty_sold_per_month DOUBLE(16,2),
    qty_ordered DOUBLE(16,2),
    qty_purchased DOUBLE(16,2),
    qty_sold DOUBLE(16,2),
    avg_qty_purchased_per_month DOUBLE(16,2),
    " . implode(', ', $vv_inventory_qty_ordered_) . ",
    " . implode(', ', $vv_inventory_qty_purchased_) . ",
    " . implode(', ', $vv_inventory_months) . ",
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB, DEFAULT CHARSET=utf8");

  pm("INSERT INTO vv_inventory$uid
    SELECT 
    t1.id, t1.code, t1.description, t1.unit, t1.qty,
    t1.qty / (SELECT AVG(qty) FROM vw_inventory_sold_per_months$uid WHERE inventoryid = t1.id AND (" . implode('OR', $vv_avg_months) . ")) * 31,
    (SELECT AVG(qty) FROM vw_inventory_sold_per_months$uid WHERE inventoryid = t1.id AND (" . implode('OR', $vv_avg_months) . ")),
    t2.qty_ordered,
    (SELECT SUM(qty) FROM purchaseinvoiceinventory WHERE inventoryid = t1.id),
    (SELECT SUM(qty) FROM salesinvoiceinventory WHERE inventoryid = t1.id),
    (SELECT AVG(qty) FROM vw_inventory_purchased_per_months$uid WHERE inventoryid = t1.id AND (" . implode('OR', $vv_avg_months) . ")),
    " . implode(', ', $vv_inventory_t2_qty_ordered_) . ",
    " . implode(', ', $vv_inventory_t2_qty_purchased_) . ",
    " . implode(',', $vv_inventory_months_queries) . "
    FROM inventory t1
    LEFT OUTER JOIN vv_inventory_ordered$uid t2
    ON t1.id = t2.inventoryid
  ");

}
function inventoryanalysislist($columns = null, $sorts = null, $filters = null, $limits = null, $flag = 0){

  $uid = 7;

  $exists = pmc("select count(*) from information_schema.tables where table_schema = 'indosps' and table_name = 'vv_inventory$uid'");
  if(!$exists) inventoryanalysisgenerate();

  $column_alias = [];
  $table_columns = pmrs("SHOW COLUMNS FROM vv_inventory$uid;");
  foreach($table_columns as $table_column)
    $column_alias[$table_column['Field']] = $table_column['Field'];

  $columns_indexed = array_index($columns, [ 'name' ], 1);

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $column_alias);
  $wherequery = wherequery_from_filters($params, $filters, $column_alias);
  $sortquery = sortquery_from_sorts($sorts, $column_alias);
  $limitquery = limitquery_from_limitoffset($limits);
  $columnquery = strlen($columnquery) > 0 ? ', ' . $columnquery : '';
  $query = "SELECT `id` $columnquery FROM vv_inventory$uid t1 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);

  if(is_array($data) && count($data) > 0){

    $supplier_columns = [];

    $inventoryids = [];
    for($i = 0 ; $i < count($data) ; $i++){
      $inventoryids[] = $data[$i]['id'];
    }
    $inventory_ordered = pmrs("select * from vv_inventory_ordered$uid where inventoryid in (" . implode(', ', $inventoryids) . ")");
    if(is_array($inventory_ordered)){
      foreach($inventory_ordered as $inventory_ordered_obj){
        foreach($inventory_ordered_obj as $key=>$value){
          if(strpos($key, 'qty_ordered_') !== false && ($value > 0 || $value < 0)){
            $supplierid = str_replace('qty_ordered_', '', $key);
            $supplier_columns[$supplierid] = 1;
          }
        }
      }
    }
    $inventory_ordered = array_index($inventory_ordered, [ 'inventoryid' ], 1);
    $supplier_columns = array_keys($supplier_columns);
    $suppliers = count($supplier_columns) > 0 ? pmrs("select `id`, description from supplier where `id` in (" . implode(', ', $supplier_columns) . ")") : [];
    $suppliers = array_index($suppliers, [ 'id' ], true);

    for($i = 0 ; $i < count($data) ; $i++){

      // qty_ordered_detail
      if(isset($columns_indexed['qty_ordered_detail']) && $columns_indexed['qty_ordered_detail']['active']){
        $data[$i]['qty_ordered_detail'] = [];
        $inventory = $inventory_ordered[$data[$i]['id']];
        foreach($suppliers as $supplier){
          $data[$i]['qty_ordered_detail'][$supplier['description']] = $inventory['qty_ordered_' . $supplier['id']];
        }
      }

      // Remove id if not visible
      //if(!isset($columns_indexed['id']) || !$columns_indexed['id']['active']){
      // unset($data[$i]['id']);
      //}

    }

  }
  return $data;

}

function inventoryformula_set($date, $inventoryid, $name, $value){

  $valid_columns = [ 'm3', 'cbmperkg' ];

  if(!in_array($name, $valid_columns)) return;
  if(!$inventoryid) return;

  // Date default to today
  if(strtotime($date) == strtotime('invalid')){ $date = date('Ymd'); }

  $lastupdatedon = date('YmdHis');

  // Store to inventoryformula
  $params = [ $date, $inventoryid, $value, $lastupdatedon, $value, $lastupdatedon ];
  $query = "INSERT INTO inventoryformula (`date`, inventoryid, `$name`, lastupdatedon) VALUES (?, ?, ?, ?)
    on duplicate key update `$name` = ?, lastupdatedon = ?";
  pm($query, $params);

  // Calculate freight charge
  $query = "UPDATE inventoryformula SET freightcharge = m3 * cbmperkg WHERE inventoryid = ? AND `date` = ?";
  $params = [ $inventoryid, $date ];
  pm($query, $params);

  // Return current row
  $query = "SELECT * FROM inventoryformula WHERE inventoryid = ? AND `date` = ?";
  $row = pmr($query, $params);

  return $row;

}

function inventory_freightcharge_get($inventoryid, $date){

  $obj = pmr("select m3, cbmperkg, freightcharge from inventoryformula where inventoryid = ? and `date` <= ? order by `date` desc limit 1",
    [ $inventoryid, $date ]);
  if(!$obj) $obj = [ 'm3'=>0, 'cbmperkg'=>0, 'freightcharge'=>0 ];
  return $obj;

}

function inventory_taxable_replicate($mode, $prefix){

  $report = [
    'success'=>0,
    'failed'=>0,
    'verbose'=>[]
  ];

  $non_taxable_inventories = pmrs("select * from inventory where taxable = 0");
  $taxable_inventories = pmrs("select code, description from inventory where taxable = 1");
  $taxable_inventories = array_index($taxable_inventories, [ 'description' ], true);

  $new_taxable_inventories = [];
  if(is_array($non_taxable_inventories)){
    foreach($non_taxable_inventories as $non_taxable_inventory){
      if(!isset($taxable_inventories[$non_taxable_inventory['description']])){

        switch($mode){
          case 'end-with':
            $non_taxable_inventory['code'] = $non_taxable_inventory['code'] . $prefix;
            break;
          default:
            $non_taxable_inventory['code'] = $prefix . $non_taxable_inventory['code'];
            break;
        }

        $non_taxable_inventory['taxable'] = 1;
        unset($non_taxable_inventory['id']);
        $non_taxable_inventory['price'] = $non_taxable_inventory['price'] / 1.1;
        $new_taxable_inventories[] = $non_taxable_inventory;
      }
    }
  }

  foreach($new_taxable_inventories as $new_taxable_inventory){
    try{
      inventoryentry($new_taxable_inventory);
      $report['success']++;
    }
    catch(Exception $ex){
      $report['failed']++;
      $report['verbose'][] = $ex->getMessage();
    }
  }

  return $report;

}

function inventory_taxable_remove($mode, $prefix){

  $reports = [
    'success'=>0,
    'failed'=>0,
    'verbose'=>[]
  ];

  // Get inventoryids
  switch($mode){
    case 'end-with':
      $inventoryids = pmrs("select `id` from inventory where taxable = 1 and code like ?", [ "%$prefix" ]);
      break;
    case 'start-with':
    default:
      $inventoryids = pmrs("select `id` from inventory where taxable = 1 and code like ?", [ "$prefix%" ]);
      break;
  }

  if(is_array($inventoryids))
    foreach($inventoryids as $inventory){
      try{
        inventoryremove([ 'id'=>$inventory['id'] ]);
        $reports['success']++;

      }
      catch(Exception $ex){
        $reports['failed']++;
        $reports['verbose'][] = $ex->getMessage();
      }
    }

  return $reports;

}

function inventory_warehouse_calc(){

  $query0 = "update inventorywarehouse set qty = 0, total_amount = 0";

  $date = system_date('Ymd') . '235959';
  $query1 = "insert into inventorywarehouse (inventoryid, warehouseid, qty, total_amount) 
    SELECT t1.inventoryid, t1.warehouseid, SUM(t1.`in` - t1.`out`) as qty, SUM(t1.amount) as total_amount
    FROM inventorybalance t1, warehouse t2, inventory t3 
    WHERE t1.warehouseid = t2.id and t1.inventoryid = t3.id
    AND t1.`date` <= ? 
    GROUP BY inventoryid, warehouseid
    ON DUPLICATE KEY UPDATE qty = VALUES(qty), total_amount = VALUES(total_amount);";
  $params = [ $date ];

  try{
    pm($query0);
    pm($query1, $params);
  }
  catch(Exception $ex){
    // TODO handle this exception
    // SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction
  }

}

function inventorybalance_calc($inventoryid, $start_date = null){

  $limit = 1000;

  $rows = pmrs("select ");

}

/**
 * Table index required:
 * - fk_salesinvoiceinventory_2
 * - fk_purchaseinvoiceinventory_2
 * - fk_sampleinvoiceinventory_2
 * - fk_inventoryadjustmentdetail_2
 * - fk_warehousetransferinventory_2
 */
function inventorybalance_calc_refitemid(){

  $limit = 10000;
  $offset = 0;
  $processed = 0; // Count of row processed
  $total = 0;
  $queries = $params = [];

  do{

    $rows = pmrs("select `id`, inventoryid, `in`, `out`, `ref`, refid from inventorybalance where refitemid is null or refitemid = 0 limit $limit offset $offset", []);
    foreach($rows as $row){

      $id = $row['id'];
      $ref = $row['ref'];
      $refid = $row['refid'];
      $inventoryid = $row['inventoryid'];
      $in = $row['in'];
      $out = $row['out'];

      $refitemid = 0;
      switch($ref){

        case 'IA':
          if($in > 0)
            $refitemid = pmc("select `id` from inventoryadjustmentdetail where inventoryid = ? and qty = ?", [ $inventoryid, $in ]);
          else
            $refitemid = pmc("select `id` from inventoryadjustmentdetail where inventoryid = ? and qty = ?", [ $inventoryid, $out * -1 ]);
          break;
        case 'PI':
          $refitemid = pmc("select `id` from purchaseinvoiceinventory where inventoryid = ? and qty = ?", [ $inventoryid, $in ]);
          break;
        case 'SI':
          $refitemid = pmc("select `id` from salesinvoiceinventory where inventoryid = ? and qty = ?", [ $inventoryid, $out ]);
          break;
        case 'SJS':
          $refitemid = pmc("select `id` from sampleinvoiceinventory where inventoryid = ? and qty = ?", [ $inventoryid, $out ]);
          break;
        case 'WT':
          $refitemid = pmc("select `id` from warehousetransferinventory where inventoryid = ? and qty = ?", [ $inventoryid, $in > 0 ? $in : abs($out) ]);
          break;

      }

      if($refitemid){
        $queries[] = "update inventorybalance set refitemid = ? where `id` = ?";
        array_push($params, $refitemid, $id);
        //echo "refitemid: {$refitemid}, ref: {$ref}, refid: {$refid}, inventoryid: {$inventoryid}" . PHP_EOL;
        $processed++;
      }
      if(!$refitemid && $refid) echo "refitemid not found. ref: {$ref}, refid: {$refid}, inventoryid: {$inventoryid}" . PHP_EOL;
      $total++;

      if(count($queries) > 1000){
        pm(implode(';', $queries), $params);
        $queries = $params = [];
      }

    }

    $offset += $limit;

  }
  while(count($rows) > 0);

  echo "{$total}/{$processed} processed" . PHP_EOL;

}

?>
