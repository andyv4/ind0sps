<?php
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/privilege.php';
require_once dirname(__FILE__) . '/inventory.php';
require_once dirname(__FILE__) . '/salesinvoice.php';
require_once dirname(__FILE__) . '/staff.php';
require_once dirname(__FILE__) . '/user.php';

$customers_columnaliases = array(
  'id'=>'t1.id!',
  'isactive'=>'t1.isactive',
  'issuspended'=>'t1.issuspended',
  'code'=>'t1.code',
  'description'=>'t1.description',
  'address'=>'t1.address',
  'city'=>'t1.city',
  'country'=>'t1.country',
  'discount'=>'t1.discount',
  'taxable'=>'t1.taxable',
  'creditlimit'=>'t1.creditlimit',
  'creditterm'=>'t1.creditterm',
  'receivable'=>'t1.receivable',
  'totalsales'=>'t1.totalsales',
  'avgsalesmargin'=>'t1.avgsalesmargin',
  'phone1'=>'t1.phone1',
  'phone2'=>'t1.phone2',
  'fax1'=>'t1.fax1',
  'fax2'=>'t1.fax2',
  'contactperson'=>'t1.contactperson',
  'email'=>'t1.email',
  'createdon'=>'t1.createdon',
  'moved'=>'t1.moved',
  'defaultsalesmanid'=>'t1.defaultsalesmanid!',
  'defaultsalesmanname'=>'(select `name` from `user` where `id` = t1.defaultsalesmanid)',
  'type'=>"'customer'!",
  'override_sales'=>'t1.override_sales',
  'sales_companyname'=>'t1.sales_companyname',
  'sales_addressline1'=>'t1.sales_addressline1',
  'sales_addressline2'=>'t1.sales_addressline2',
  'sales_addressline3'=>'t1.sales_addressline3',
  'salesinvoicegroup_combinable'=>'t1.salesinvoicegroup_combinable',
);

function customer_uicolumns(){

  $columns = array(
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'customerlist_options'),
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>1, 'name'=>'isactive', 'text'=>'Aktif', 'width'=>30, 'type'=>'html', 'html'=>'customerlist_isactive'),
    array('active'=>1, 'text'=>'Status', 'name'=>'issuspended', 'width'=>'50', 'align'=>'center', 'type'=>'html', 'html'=>'customerlist_status'),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>1, 'name'=>'description', 'text'=>'Nama Pelanggan', 'width'=>300),
    array('active'=>1, 'name'=>'tax_registration_number', 'text'=>'No NPWP', 'width'=>150),
    array('active'=>1, 'name'=>'address', 'text'=>'Alamat', 'width'=>120),
    array('active'=>1, 'name'=>'billingaddress', 'text'=>'Alamat Penagihan', 'width'=>120),
    array('active'=>0, 'name'=>'city', 'text'=>'Kota', 'width'=>100),
    array('active'=>0, 'name'=>'country', 'text'=>'Negara', 'width'=>100),
    array('active'=>0, 'name'=>'discount', 'text'=>'Diskon%', 'width'=>100, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'taxable', 'text'=>'PPn', 'width'=>30, 'type'=>'html', 'html'=>'customerlist_taxable'),
    array('active'=>0, 'name'=>'creditlimit', 'text'=>'Batas Kredit', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'creditterm', 'text'=>'Lama Kredit', 'width'=>100, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'receivable', 'text'=>'Piutang', 'width'=>100, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'totalsales', 'text'=>'Total Penjualan', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'avgsalesmargin', 'text'=>'Margin Rata2', 'width'=>100, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'phone1', 'text'=>'Telp 1', 'width'=>100),
    array('active'=>0, 'name'=>'phone2', 'text'=>'Telp 2', 'width'=>100),
    array('active'=>0, 'name'=>'fax1', 'text'=>'Fax 1', 'width'=>100),
    array('active'=>0, 'name'=>'fax2', 'text'=>'Fax 2', 'width'=>100),
    array('active'=>0, 'name'=>'contactperson', 'text'=>'Kontak', 'width'=>100),
    array('active'=>0, 'name'=>'email', 'text'=>'Email', 'width'=>100),
    array('active'=>1, 'name'=>'defaultsalesmanname', 'text'=>'Salesman', 'width'=>100),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function customerdetail($columns, $filters){

  $customer = mysql_get_row('customer', $filters, array_exclude($columns, array('inventories')));

  if($customer){
    $users = userlist();
    $users = is_array($users) ? array_index($users, array('id'), 1) : null;

    if(is_array($columns) && in_array('defaultsalesmanname', $columns))
      $customer['defaultsalesmanname'] = isset($users[$customer['defaultsalesmanid']]) ? $users[$customer['defaultsalesmanid']]['name'] : '';

    // Get customer inventories
    if(is_array($columns) && in_array('inventories', $columns)){
      $customerid = $customer['id'];
      $inventories = inventorylist([
        [ 'name'=>'code' ],
        [ 'name'=>'description' ],
        [ 'name'=>'price' ],
      ], null);
      $customerinventories = pmrs("SELECT t1.inventoryid, t1.price FROM customerinventory t1 WHERE t1.customerid = ?", array($customerid));
      $customerinventories = array_index($customerinventories, array('inventoryid'), 1);
      $rows = array();
      for($i = 0 ; $i < count($inventories) ; $i++){
        $inventory = $inventories[$i];
        $inventoryid = $inventory['id'];

        $rows[] = array(
            'inventoryid'=>$inventoryid,
            'code'=>$inventory['code'],
            'description'=>$inventory['description'],
            'price'=>$inventory['price'],
            'customerprice'=>isset($customerinventories[$inventoryid]) ? $customerinventories[$inventoryid]['price'] : 0
        );
      }
      $customer['inventories'] = $rows;
    }

    if(is_array($columns) && in_array('salesinvoices', $columns))
      $customer['salesinvoices'] = customersaleslist($customer['id']);
  }

  return $customer;

}
function customerlist_hints_asitems($param){

  $hint = ov('hint', $param);
  $items = pmrs("SELECT `id`, description FROM customer WHERE (code LIKE ? OR description LIKE ?) AND isactive = 1",
    array("%$hint%", "%$hint%"));
  $items = array_cast($items, array('text'=>'description', 'value'=>'id'));
  return $items;

}
function customerlist_hints_asitems2($param){

  $hint = ov('hint', $param);
  $items = pmrs("SELECT `id`, description FROM customer WHERE (code LIKE ? OR description LIKE ?) AND isactive = 1",
      array("%$hint%", "%$hint%"));
  $items = array_cast($items, array('text'=>'description', 'value'=>'description'));
  return $items;

}
function customerlist($columns, $sorts = null, $filters = null, $limits = null, $groups = null){

  global $customers_columnaliases;

  // Generating sql queries
  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $customers_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $customers_columnaliases);
  
  // Fetch data
  if(is_array($groups) && count($groups) > 0){

    if(count($groups) > 0){

      $group = $groups[0];
      $group_query = groupquery_from_groups([ $group ], $customers_columnaliases);
      $group_column = groupcolumn_from_group($group, $customers_columnaliases);

      $query = "SELECT $group_column FROM (
        SELECT $columnquery FROM customer t1 $wherequery
      ) as s1 $group_query";
      $data = pmrs($query, $params);

    }

  }
  else{

    $sortquery = sortquery_from_sorts($sorts, $customers_columnaliases);
    $limitquery = limitquery_from_limitoffset($limits);
    $query = "SELECT $columnquery FROM customer t1 $wherequery $sortquery $limitquery";
    $data = pmrs($query, $params);
    
  }

  return $data;

}
function customersaleslist($customerid, $status = null){

  $salesinvoices = salesinvoicelist(null, array('customerid'=>$customerid));

  $results = array();
  for($i = 0 ; $i < count($salesinvoices) ; $i++){
    $result = array('type'=>'SI');
    $salesinvoice = $salesinvoices[$i];
    $result = array_merge($result, $salesinvoice);
    $results[] = $result;
  }
  return $results;
}

function customerentry($customer){

  $isactive = ov('isactive', $customer, 0, 1);
  $salesinvoicegroup_combinable = ov('salesinvoicegroup_combinable', $customer, 0, 0);
  $code = ov('code', $customer, 1, '', 'string', array('notempty'=>1));
  $description = ov('description', $customer, 1, '', 'string', array('notempty'=>1));
  $tax_registration_number = ov('tax_registration_number', $customer, 1, '', 'string');
  $address = ov('address', $customer);
  $billingaddress = ov('billingaddress', $customer);
  $city = ov('city', $customer);
  $country = ov('country', $customer);
  $phone1 = ov('phone1', $customer);
  $phone2 = ov('phone2', $customer);
  $fax1 = ov('fax1', $customer);
  $fax2 = ov('fax2', $customer);
  $contactperson = ov('contactperson', $customer);
  $email = ov('email', $customer);
  $note = ov('note', $customer);
  $discount = ov('discount', $customer);
  $taxable = ov('taxable', $customer, 0);
  $creditlimit = ov('creditlimit', $customer, 0);
  $creditterm = ov('creditterm', $customer, 0);
  $inventories = ov('inventories', $customer, 0);
  $defaultsalesmanid = ov('defaultsalesmanid', $customer);
  $override_sales = ov('override_sales', $customer, 0, 0);
  $sales_companyname = ov('sales_companyname', $customer, 0, '');
  $sales_addressline1 = ov('sales_addressline1', $customer, 0, '');
  $sales_addressline2 = ov('sales_addressline2', $customer, 0, '');
  $sales_addressline3 = ov('sales_addressline3', $customer, 0, '');
  $createdon =  date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  if(empty($code)) throw new Exception(excmsg('c01'));
  if(customerdetail(null, array('code'=>$code))) throw new Exception(excmsg('c03'));
  if(empty($description)) throw new Exception(excmsg('c02'));
  if(customerdetail(null, array('description'=>$description))) throw new Exception(excmsg('c04'));
  if(!$defaultsalesmanid) $defaultsalesmanid = 1;

  $lock_file = __DIR__ . "/../usr/system/customer_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $query = "INSERT INTO customer(isactive, code, description, tax_registration_number, address, billingaddress, city, country, phone1, phone2, fax1, fax2, contactperson,
    email, note, discount, taxable, creditlimit, creditterm, defaultsalesmanid, override_sales, sales_companyname, sales_addressline1, sales_addressline2,
    sales_addressline3, createdon, createdby)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($isactive, $code, $description, $tax_registration_number, $address, $billingaddress, $city, $country, $phone1, $phone2, $fax1, $fax2, $contactperson,
    $email, $note, $discount, $taxable, $creditlimit, $creditterm, $defaultsalesmanid, $override_sales, $sales_companyname, $sales_addressline1,
    $sales_addressline2, $sales_addressline3, $createdon, $createdby));

  if(is_array($inventories)){
    pm("DELETE FROM customerinventory WHERE customerid = ?", array($id));
    $paramstr = array();
    $params = array();
    for($i = 0 ; $i < count($inventories) ; $i++){
      $customerinventory = $inventories[$i];
      $inventorydescription = $customerinventory['description'];
      $inventory = inventorydetail(null, array('description'=>$inventorydescription));
      $inventoryid = $inventory['id'];
      if(!$inventory) continue;
      $price = floatval($customerinventory['customerprice']);
      if($price <= 0) continue;

      $paramstr[] = "(?, ?, ?)";
      $params[] = $id;
      $params[] = $inventoryid;
      $params[] = $price;
    }
    if(count($params) > 0){
      $query = "INSERT INTO customerinventory(customerid, inventoryid, price) VALUES " . implode(',', $paramstr);
      pm($query, $params);
    }
  }

  userlog('customerentry', $customer, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;

}
function customermodify($customer){

  $id = ov('id', $customer, 1);
  $current_customer = customerdetail(null, array('id'=>$id));

  if(!$current_customer) exc('Pelanggan tidak terdaftar.');

  $lock_file = __DIR__ . "/../usr/system/customer_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();

  if(isset($customer['isactive']) && $current_customer['isactive'] != $customer['isactive']){
    $user = staffdetail([ 'dept' ], [ 'id'=>$_SESSION['user']['id'] ]);
    if(privilege_get('customer', 'modify') && in_array($user['dept'], [ 'accounting', 'management' ])) {
      $updatedrow['isactive'] = $customer['isactive'];
    }
  }

  if(isset($customer['code']) && $current_customer['code'] != $customer['code']){
    if(empty($customer['code'])) throw new Exception(excmsg('c01'));
    if(customerdetail(null, array('code'=>$customer['code']))) throw new Exception(excmsg('c03'));
    $updatedrow['code'] = $customer['code'];
  }

  if(isset($customer['description']) && $current_customer['description'] != $customer['description']){
    if(empty($customer['description'])) throw new Exception(excmsg('c02'));
    if(customerdetail(null, array('description'=>$customer['description']))) throw new Exception(excmsg('c04'));
    $updatedrow['description'] = $customer['description'];
  }

  if(isset($customer['tax_registration_number']) && $current_customer['tax_registration_number'] != $customer['tax_registration_number'])
    $updatedrow['tax_registration_number'] = $customer['tax_registration_number'];

  if(isset($customer['address']) && $current_customer['address'] != $customer['address'])
    $updatedrow['address'] = $customer['address'];

  if(isset($customer['salesinvoicegroup_combinable']) && $current_customer['salesinvoicegroup_combinable'] != $customer['salesinvoicegroup_combinable'])
    $updatedrow['salesinvoicegroup_combinable'] = $customer['salesinvoicegroup_combinable'];

  if(isset($customer['billingaddress']) && $current_customer['billingaddress'] != $customer['billingaddress'])
    $updatedrow['billingaddress'] = $customer['billingaddress'];

  if(isset($customer['city']) && $current_customer['city'] != $customer['city'])
    $updatedrow['city'] = $customer['city'];

  if(isset($customer['country']) && $current_customer['country'] != $customer['country'])
    $updatedrow['country'] = $customer['country'];

  if(isset($customer['phone1']) && $current_customer['phone1'] != $customer['phone1'])
    $updatedrow['phone1'] = $customer['phone1'];

  if(isset($customer['phone2']) && $current_customer['phone2'] != $customer['phone2'])
    $updatedrow['phone2'] = $customer['phone2'];

  if(isset($customer['fax1']) && $current_customer['fax1'] != $customer['fax1'])
    $updatedrow['fax1'] = $customer['fax1'];

  if(isset($customer['fax2']) && $current_customer['fax2'] != $customer['fax2'])
    $updatedrow['fax2'] = $customer['fax2'];

  if(isset($customer['contactperson']) && $current_customer['contactperson'] != $customer['contactperson'])
    $updatedrow['contactperson'] = $customer['contactperson'];

  if(isset($customer['email']) && $current_customer['email'] != $customer['email'])
    $updatedrow['email'] = $customer['email'];

  if(isset($customer['note']) && $current_customer['note'] != $customer['note'])
    $updatedrow['note'] = $customer['note'];

  if(isset($customer['discount']) && $current_customer['discount'] != $customer['discount'])
    $updatedrow['discount'] = $customer['discount'];

  if(isset($customer['taxable']) && $current_customer['taxable'] != $customer['taxable'])
    $updatedrow['taxable'] = $customer['taxable'];

  if(isset($customer['creditterm']) && $current_customer['creditterm'] != $customer['creditterm'])
    $updatedrow['creditterm'] = $customer['creditterm'];

  if(isset($customer['creditlimit']) && $current_customer['creditlimit'] != $customer['creditlimit'])
    $updatedrow['creditlimit'] = $customer['creditlimit'];

  if(isset($customer['override_sales']) && $current_customer['override_sales'] != $customer['override_sales'])
    $updatedrow['override_sales'] = $customer['override_sales'];

  if(isset($customer['sales_companyname']) && $current_customer['sales_companyname'] != $customer['sales_companyname'])
    $updatedrow['sales_companyname'] = $customer['sales_companyname'];

  if(isset($customer['sales_addressline1']) && $current_customer['sales_addressline1'] != $customer['sales_addressline1'])
    $updatedrow['sales_addressline1'] = $customer['sales_addressline1'];

  if(isset($customer['sales_addressline2']) && $current_customer['sales_addressline2'] != $customer['sales_addressline2'])
    $updatedrow['sales_addressline2'] = $customer['sales_addressline2'];

  if(isset($customer['sales_addressline3']) && $current_customer['sales_addressline3'] != $customer['sales_addressline3'])
    $updatedrow['sales_addressline3'] = $customer['sales_addressline3'];

  if(isset($customer['defaultsalesmanid']) && !empty($customer['defaultsalesmanid']) && $current_customer['defaultsalesmanid'] != $customer['defaultsalesmanid'])
    $updatedrow['defaultsalesmanid'] = $customer['defaultsalesmanid'];

  if(count($updatedrow) > 0)
    mysql_update_row('customer', $updatedrow, array('id'=>$id));

  if(is_array($customer['inventories'])){
    $inventories = $customer['inventories'];
    pm("DELETE FROM customerinventory WHERE customerid = ?", array($id));
    $paramstr = array();
    $params = array();
    for($i = 0 ; $i < count($inventories) ; $i++){
      $customerinventory = $inventories[$i];
      $inventorydescription = $customerinventory['description'];
      $inventory = inventorydetail(null, array('description'=>$inventorydescription));
      $inventoryid = $inventory['id'];
      if(!$inventory) continue;
      $price = $customerinventory['customerprice'];
      if($price <= 0) continue;

      $paramstr[] = "(?, ?, ?)";
      $params[] = $id;
      $params[] = $inventoryid;
      $params[] = $price;
    }
    if(count($params) > 0){
      $query = "INSERT INTO customerinventory(customerid, inventoryid, price) VALUES " . implode(',', $paramstr);
      pm($query, $params);
    }

    $updatedrow['inventories'] = $customer['inventories'];
  }

  fclose($fp);
  unlink($lock_file);

  userlog('customermodify', $current_customer, $updatedrow, $_SESSION['user']['id'], $id);

  $result = array('id'=>$id);
  return $result;
  
}
function customerremove($filters){

  if(isset($filters['id'])){

    $id = ov('id', $filters);
    $current_customer = customerdetail(null, array('id'=>$id));

    if(!$current_customer) throw new Exception('Pelanggan tidak terdaftar.');

    $lock_file = __DIR__ . "/../usr/system/customer_remove_" . $id . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    $query = "DELETE FROM customer WHERE `id` = ?";
    pm($query, array($id));

    userlog('customerremove', $current_customer, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }

}

function customerpricechange($customerid, $inventoryid, $price){

  pm("DELETE FROM customerinventory WHERE customerid = ? AND inventoryid = ?", array($customerid, $inventoryid));
  pm("INSERT INTO customerinventory(customerid, inventoryid, price) VALUES (?, ?, ?)", array($customerid, $inventoryid, $price));

}
function customermove($id){

  // Check for existing salesinvoice
  $customer = pmr("SELECT moved FROM customer WHERE `id` = ?", array($id));
  if(!$customer) throw new Exception('Pelanggan tidak terdaftar');
  if($customer['moved']) throw new Exception('Pelanggan telah dipindah.');

  // Move to next database
  pm("INSERT INTO indosps2.customer SELECT * FROM customer WHERE `id` = ?", array($id));

  // Update salesinvoice row
  pm("UPDATE customer SET moved = ? WHERE `id` = ?", array(1, $id));

}
function customer_updatemovestate(){

  // Fetch indosps2 customers
  $customers = pmrs("SELECT `id` FROM indosps2.customer");

  $customer_ids = array();
  foreach($customers as $customer)
    $customer_ids[] = $customer['id'];

  // Update indosps2 customers
  pm("UPDATE customer SET moved = 0");
  pm("UPDATE customer SET moved = 1 WHERE `id` IN (" . implode(', ', $customer_ids) . ")");

}
function customer_defaultsalesman(){

  pm("UPDATE customer SET defaultsalesmanid = 999 WHERE defaultsalesmanid is null OR defaultsalesmanid = 0");

}

function customerreceivablecalculate($customerid){

  $query = "UPDATE customer SET receivable = (SELECT SUM(total - paymentamount) FROM salesinvoice WHERE customerid = ?) WHERE `id` = ?";
  pm($query, array($customerid, $customerid));

}
function customerreceivablecalculateall(){

	$salesinvoices = pmrs("SELECT customerid, SUM(total - paymentamount) as receivable FROM salesinvoice WHERE ispaid != 1 GROUP BY customerid");
	if($salesinvoices){
		$queries = array();		
		foreach($salesinvoices as $salesinvoice){
			$customerid = $salesinvoice['customerid'];
			$receivable = $salesinvoice['receivable'];
			
			$queries[] = "UPDATE customer SET receivable = $receivable WHERE id = $customerid";
		}
		mysqli_exec_multiples($queries);	
	}

}

function customertotalsalescalculateall(){

  pm("update customer t1 set totalsales = (select sum(paymentamount) from salesinvoice where customerid = t1.id)");

}

function customercalculateall(){

  customerreceivablecalculateall();
  customertotalsalescalculateall();
  pm("update customer t1 set avgsalesmargin = (select avg(avgsalesmargin) from salesinvoice where customerid = t1.id and avgsalesmargin < ?)",
    [ MAX_MARGIN_THRESHOLD ]);

}
function customer_has_due_invoice($id){

  $has_due = false;
  $company_creditterm = systemvarget('customer_creditterm');

  $salesinvoices = pmr("select t1.id, t2.code, t1.creditterm, t2.creditterm as salesinvoice_creditterm, t2.date, sum(t2.total) as total from customer t1, salesinvoice t2 
      where t1.id = t2.customerid and t2.customerid = ? and t2.ispaid = 0 group by t1.id order by t2.date limit 1", [ $id ]);
  $salesinvoicegroups = pmr("select t1.id, t2.code, t1.creditterm, t2.date, sum(t2.total) as total, t3.creditterm as salesinvoice_creditterm from customer t1, salesinvoicegroup t2, salesinvoice t3 
      where t1.id = t3.customerid and t3.customerid = ? and t2.ispaid = 0 and t3.salesinvoicegroupid = t2.id group by t1.id order by t2.date limit 1", [ $id ]);
  $customer = is_array($salesinvoicegroups) ? $salesinvoicegroups : $salesinvoices;

  if($customer){
    $creditterm = $customer['salesinvoice_creditterm'] > 0 ? $customer['salesinvoice_creditterm'] : ($customer['creditterm'] > 0 ? $customer['creditterm'] : $company_creditterm);

    if($creditterm > 0){
      $salesinvoice_date = $customer['date'];
      $due_date = date('Y-m-d', mktime(0, 0, 0, date('m', strtotime($salesinvoice_date)), date('j', strtotime($salesinvoice_date)) + $creditterm, date('Y', strtotime($salesinvoice_date))));
      $code = $customer['code'];
      if(strtotime($due_date) <= strtotime('now')) exc("Pelanggan ini memiliki invoice yang seharusnya dibayar pada " . date('j M Y', strtotime($due_date)) . ". ($code)");
    }
  }
  return $has_due;

}
function customer_suspended_calc(){

  $company_creditterm = systemvarget('customer_creditterm');

  // Customers with 1 or more unpaid sales invoice
  $salesinvoices = pmrs("select t1.id, t1.creditterm, t2.creditterm as salesinvoice_creditterm, t2.date, sum(t2.total) as total from customer t1, salesinvoice t2 where t1.id = t2.customerid and t2.ispaid = 0 group by t1.id order by t2.date");

  // Customers with 1 or more unpaid sales invoice group
  $salesinvoicegroups = pmrs("select t1.id, t1.creditterm, t2.date, sum(t2.total) as total, t3.creditterm as salesinvoice_creditterm from customer t1, salesinvoicegroup t2, salesinvoice t3 where t1.id = t3.customerid and t2.ispaid = 0 and t3.salesinvoicegroupid = t2.id group by t1.id order by t2.date");

  // Merge customers with salesinvoicegroup priority
  $customers = [];
  if(is_array($salesinvoices))
    foreach($salesinvoices as $salesinvoice){
      $salesinvoice['__type'] = 'salesinvoice';
      $customers[$salesinvoice['id']] = $salesinvoice;
    }
  if(is_array($salesinvoicegroups))
    foreach($salesinvoicegroups as $salesinvoicegroup){
      $salesinvoicegroup['__type'] = 'salesinvoicegroup';
      $customers[$salesinvoicegroup['id']] = $salesinvoicegroup;
    }
  $customers = array_values($customers);

  // Customers with due sales invoice
  $due_customers = [];
  $due_customer_ids = [];
  foreach($customers as $customer){

    $creditterm = $customer['salesinvoice_creditterm'] > 0 ? $customer['salesinvoice_creditterm'] : ($customer['creditterm'] > 0 ? $customer['creditterm'] : $company_creditterm);
    if(!$creditterm) continue;

    $salesinvoice_date = $customer['date'];
    $customerid = $customer['id'];
    $__type = $customer['__type'];
    $due_date = date('Y-m-d', mktime(0, 0, 0, date('m', strtotime($salesinvoice_date)), date('j', strtotime($salesinvoice_date)) + $creditterm, date('Y', strtotime($salesinvoice_date))));

    if(strtotime($due_date) <= strtotime('now')){
      $due_customers[] = [
        'id'=>$customerid,
        'date'=>$salesinvoice_date,
        'due_date'=>$due_date,
        'due_days'=>(strtotime(date('Ymd', strtotime('now')) . '000000') - strtotime(date('Ymd', strtotime($salesinvoice_date)) . '000000')) / (60 * 60 * 24),
        'total'=>$customer['total'],
      ];
      $due_customer_ids[] = $customer['id'];
    }

  }

  pm("update customer set issuspended = 0 where issuspended = 1");
  if(count($due_customer_ids) > 0) pm("update customer set issuspended = 1 where `id` in (" . implode(',', $due_customer_ids) . ")");

  return $due_customers;

}

?>