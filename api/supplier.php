<?php

require_once dirname(__FILE__) . '/log.php';
require_once dirname(__FILE__) . '/user.php';

function supplier_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>0, 'name'=>'moved', 'text'=>'Pindah', 'width'=>40, 'type'=>'html', 'html'=>'supplierlist_moved'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>1, 'name'=>'description', 'text'=>'Nama Supplier', 'width'=>300),
    array('active'=>1, 'name'=>'tax_registration_number', 'text'=>'Nomor NPWP', 'width'=>150),
    array('active'=>1, 'name'=>'city', 'text'=>'Kota', 'width'=>100),
    array('active'=>1, 'name'=>'payable', 'text'=>'Hutang', 'width'=>120, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function supplierdetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $supplier = mysql_get_row('supplier', $filters, $columns);

  if($supplier){

    $purchaseinvoices = pmrs("SELECT t1.ispaid, t1.id, t1.code, t1.date, t1.total, t2.inventorycode, t2.inventorydescription, t2.qty, t2.unitprice FROM purchaseinvoice t1, purchaseinvoiceinventory t2
      WHERE t1.supplierid = ? AND t1.id = t2.purchaseinvoiceid", array($supplier['id']));
    $supplier['purchaseinvoices'] = $purchaseinvoices;

  }

  return $supplier;

}
function supplierlist($columns, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'isactive'=>'t1.isactive',
    'code'=>'t1.code',
    'description'=>'t1.description',
    'tax_registration_number'=>'t1.tax_registration_number',
    'createdon'=>'t1.createdon',
    'address'=>'t1.address',
    'city'=>'t1.city',
    'country'=>'t1.country',
    'payable'=>'t1.payable',
    'phone1'=>'t1.phone1',
    'phone2'=>'t1.phone2',
    'fax1'=>'t1.fax1',
    'fax2'=>'t1.fax2',
    'email'=>'t1.email',
    'contactperson'=>'t1.contactperson',
    'note'=>'t1.note'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.code', 't1.description'));
  $columnquery = strlen($columnquery) > 0 ? ', ' . $columnquery : $columnquery;
  $sortquery = sortquery_from_sorts($sorts);
  $wherequery = wherequery_from_filters($params, $filters);
  $limitquery = limitquery_from_limitoffset($limitoffset);

  $query = "SELECT t1.id, t1.createdby $columnquery FROM supplier t1 $wherequery $sortquery $limitquery";
  $suppliers = pmrs($query, $params);

  if(in_arrayobject($columns, array('name'=>'createdby'))){
    $users = userlist(null, null);
    $users = array_index($users, array('id'), 1);
    for($i = 0 ; $i < count($suppliers) ; $i++)
      $suppliers[$i]['createdby'] = isset($users[$suppliers[$i]['createdby']]) ? $users[$suppliers[$i]['createdby']]['name'] : '-';
  }

  return $suppliers;

}

function supplierentry($supplier){

  $lock_file = __DIR__ . "/../usr/system/supplier_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $isactive = ov('isactive', $supplier,0, 1);
  $code = ov('code', $supplier);
  $description = ov('description', $supplier);
  $tax_registration_number = ov('tax_registration_number', $supplier);
  $address = ov('address', $supplier);
  $city = ov('city', $supplier);
  $country = ov('country', $supplier);
  $phone1 = ov('phone1', $supplier);
  $phone2 = ov('phone2', $supplier);
  $fax1 = ov('fax1', $supplier);
  $fax2 = ov('fax2', $supplier);
  $email = ov('email', $supplier);
  $contactperson = ov('contactperson', $supplier);
  $note = ov('note', $supplier);
  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  if(empty($code)) throw new Exception(excmsg('s01'));
  if(supplierdetail(null, array('code'=>$code))) throw new Exception(excmsg('s03'));
  if(empty($description)) throw new Exception(excmsg('s02'));
  if(supplierdetail(null, array('description'=>$description))) throw new Exception(excmsg('s04'));

  $query = "INSERT INTO supplier(isactive, code, description, tax_registration_number, address, city, country, phone1, phone2, fax1, fax2, email, contactperson,
    note, createdon, createdby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($isactive, $code, $description, $tax_registration_number, $address, $city, $country, $phone1, $phone2, $fax1, $fax2, $email, $contactperson,
    $note, $createdon, $createdby));

  userlog('supplierentry', $supplier, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;
  
}
function suppliermodify($supplier){

  $id = ov('id', $supplier, 1);
  $current_supplier = supplierdetail(null, array('id'=>$id));

  if(!$current_supplier) exc('Supplier tidak terdaftar.');

  $lock_file = __DIR__ . "/../usr/system/supplier_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();

  if(isset($supplier['isactive']) && $current_supplier['isactive'] != $supplier['isactive'])
    $updatedrow['isactive'] = $supplier['isactive'];

  if(isset($supplier['code']) && $current_supplier['code'] != $supplier['code']){
    if(empty($supplier['code'])) throw new Exception(excmsg('s01'));
    if(supplierdetail(null, array('code'=>$supplier['code']))) throw new Exception(excmsg('s03'));
    $updatedrow['code'] = $supplier['code'];
  }

  if(isset($supplier['description']) && $current_supplier['description'] != $supplier['description']){
    if(empty($supplier['description'])) throw new Exception(excmsg('s02'));
    if(supplierdetail(null, array('description'=>$supplier['description']))) throw new Exception(excmsg('s04'));
    $updatedrow['description'] = $supplier['description'];
  }

  if(isset($supplier['tax_registration_number']) && $current_supplier['tax_registration_number'] != $supplier['tax_registration_number'])
    $updatedrow['tax_registration_number'] = $supplier['tax_registration_number'];

  if(isset($supplier['address']) && $current_supplier['address'] != $supplier['address'])
    $updatedrow['address'] = $supplier['address'];

  if(isset($supplier['city']) && $current_supplier['city'] != $supplier['city'])
    $updatedrow['city'] = $supplier['city'];

  if(isset($supplier['country']) && $current_supplier['country'] != $supplier['country'])
    $updatedrow['country'] = $supplier['country'];

  if(isset($supplier['phone1']) && $current_supplier['phone1'] != $supplier['phone1'])
    $updatedrow['phone1'] = $supplier['phone1'];

  if(isset($supplier['phone2']) && $current_supplier['phone2'] != $supplier['phone2'])
    $updatedrow['phone2'] = $supplier['phone2'];

  if(isset($supplier['fax1']) && $current_supplier['fax1'] != $supplier['fax1'])
    $updatedrow['fax1'] = $supplier['fax1'];

  if(isset($supplier['fax2']) && $current_supplier['fax2'] != $supplier['fax2'])
    $updatedrow['fax2'] = $supplier['fax2'];

  if(isset($supplier['email']) && $current_supplier['email'] != $supplier['email'])
    $updatedrow['email'] = $supplier['email'];

  if(isset($supplier['contactperson']) && $current_supplier['contactperson'] != $supplier['contactperson'])
    $updatedrow['contactperson'] = $supplier['contactperson'];

  if(isset($supplier['note']) && $current_supplier['note'] != $supplier['note'])
    $updatedrow['note'] = $supplier['note'];

  if(count($updatedrow) > 0)
    mysql_update_row('supplier', $updatedrow, array('id'=>$id));

  userlog('suppliermodify', $current_supplier, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;

}
function suppliermove($id){

  // Check for existing supplier
  $supplier = pmr("SELECT moved FROM supplier WHERE `id` = ?", array($id));
  if(!$supplier) throw new Exception('Supplier tidak terdaftar');
  if($supplier['moved']) throw new Exception('Supplier telah dipindah.');

  // Move to next database
  pm("INSERT INTO indosps2.supplier SELECT * FROM supplier WHERE `id` = ?", array($id));

  // Update salesinvoice row
  pm("UPDATE supplier SET moved = ? WHERE `id` = ?", array(1, $id));

}
function supplier_updatemovestate(){

  // Fetch indosps2 supplier
  $suppliers = pmrs("SELECT `id` FROM indosps2.supplier");

  $supplier_ids = array();
  foreach($suppliers as $supplier)
    $supplier_ids[] = $supplier['id'];

  // Update indosps2 customers
  pm("UPDATE supplier SET moved = 0");
  pm("UPDATE supplier SET moved = 1 WHERE `id` IN (" . implode(', ', $supplier_ids) . ")");

}
function supplierremove($filters){

	$supplier = supplierdetail(null, $filters);

	if(!$supplier) exc('Supplier tidak terdaftar.');

  $id = ov('id', $supplier);

  $lock_file = __DIR__ . "/../usr/system/supplier_remove_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

  if(intval(pmc("SELECT COUNT(*) FROM purchaseorder WHERE supplierid = ?", array($id))) > 0) throw new Exception(excmsg('s05'));
  if(intval(pmc("SELECT COUNT(*) FROM purchaseinvoice WHERE supplierid = ?", array($id))) > 0) throw new Exception(excmsg('s05'));

  $query = "DELETE FROM supplier WHERE `id` = ?";

  pm($query, array($id));

  userlog('supplierremove', $supplier, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}

function supplierpayablecalculate($suppliers){

  if(is_array($suppliers) && count($suppliers) > 0){
    for($i = 0 ; $i < count($suppliers) ; $i++){
      $supplierid = $suppliers[$i];

      $query = "UPDATE supplier SET payable = (SELECT SUM(total) FROM purchaseinvoice WHERE supplierid = ? AND ispaid = 0)
        WHERE `id` = ?";
      pm($query, array($supplierid, $supplierid));
    }
  }

}
function supplierpayablecalculateall(){

  $query = "UPDATE supplier t1 SET payable = (SELECT SUM(total) FROM purchaseinvoice WHERE supplierid = t1.id AND ispaid = 0)";
  pm($query);

}

?>