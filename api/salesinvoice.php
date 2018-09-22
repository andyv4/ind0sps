<?php

require_once dirname(__FILE__) . '/code.php';
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/journalvoucher.php';
require_once dirname(__FILE__) . '/customer.php';
require_once dirname(__FILE__) . '/warehouse.php';
require_once dirname(__FILE__) . '/user.php';
require_once dirname(__FILE__) . '/salesorder.php';
require_once dirname(__FILE__) . '/salesinvoicegroup.php';
require_once dirname(__FILE__) . '/salesreceipt.php';
require_once dirname(__FILE__) . '/system.php';
require_once dirname(__FILE__) . '/taxreservation.php';
require_once dirname(__FILE__) . '/log.php';

$salesinvoice_columnaliases = [
  'type'=>"'salesinvoice'!",
  'id'=>'t1.id!',
  'isactive'=>'t1.isactive!',
  'ispaid'=>'t1.ispaid',
  'isgroup'=>'t1.isgroup',
  'isreceipt'=>'t1.isreceipt',
  'status'=>'t1.status',
  'isprint'=>'t1.isprint',
  'issent'=>'t1.issent',
  'isreconciled'=>'t1.isreconciled',
  'date'=>'t1.date',
  'code'=>'t1.code',
  'creditterm'=>'t1.creditterm',
  'pocode'=>'t1.pocode',
  'customerid'=>'t1.customerid',
  'customerdescription'=>'t1.customerdescription',
  'customeraddress'=>'t1.address',
  'avgsalesmargin'=>'t1.avgsalesmargin',
  'subtotal'=>'t1.subtotal',
  'discount'=>'t1.discount',
  'discountamount'=>'t1.discountamount',
  'taxable'=>'t1.taxable!',
  'tax_code'=>'t1.tax_code',
  'taxamount'=>'t1.taxamount',
  'deliverycharge'=>'t1.deliverycharge',
  'total'=>'t1.total',
  'paymentaccountid'=>'t1.paymentaccountid',
  'paymentaccountname'=>'(select `name` from chartofaccount where `id` = t1.paymentaccountid)',
  'paymentamount'=>'t1.paymentamount',
  'paymentdate'=>'t1.paymentdate',
  'inventoryid'=>'t2.inventoryid',
  'inventorycode'=>'t2.inventorycode',
  'inventorydescription'=>'t2.inventorydescription',
  'qty'=>'t2.qty',
  'returnqty'=>'t2.returnqty',
  'unit'=>'t2.unit',
  'unitprice'=>'t2.unitprice',
  'margin'=>'t2.margin',
  'unitdiscount'=>'t2.unitdiscount',
  'unitdiscountamount'=>'t2.unitdiscountamount',
  'unittotal'=>'t2.unittotal',
  'createdon'=>'t1.createdon',
  'warehousename'=>'(select `name` from warehouse where `id` = t1.warehouseid)',
  'moved'=>'t1.moved',
  'salesmanid'=>'t1.salesmanid',
  'salesmanname'=>'(select `name` from `user` where `id` = t1.salesmanid)',
  'duedays'=>'DATEDIFF(NOW(), DATE_ADD(`date`, INTERVAL `creditterm` DAY))',
  'costprice'=>'t2.costprice',
  'totalcostprice'=>'t2.totalcostprice',
];

function salesinvoicecode($date, $taxable, $release = ''){

  $taxable_code = systemvarget('salesinvoice_tax_code');
  $non_taxable_code = systemvarget('salesinvoice_nontax_code');
  $type_code = $taxable && !empty(trim($taxable_code)) ? $taxable_code : $non_taxable_code;
  $type_code2 = $taxable && !empty(trim($taxable_code)) ? 'SIT' : 'SIN';

  if($release) code_release($release);

  $code = code_reserve(
    $type_code2,
    date('Y', strtotime($date)),
    $type_code
  );

  return $code;

}
function salesinvoicedetail($columns, $filters){

  if($columns == null) $columns = array('*');
  $salesinvoice = mysql_get_row('salesinvoice', $filters, $columns);

  if($salesinvoice){
    $inventories = pmrs("select t1.*, t2.taxable_excluded from salesinvoiceinventory t1, inventory t2 where t1.salesinvoiceid = ? and t1.inventoryid = t2.id", [ $salesinvoice['id'] ]);

    // Costprice privilege
    $costprice_eligible = privilege_get('inventory', 'costprice');
    if(!$costprice_eligible)
      unset($salesinvoice['avgsalesmargin']);

    if(is_array($inventories))
      for($i = 0 ; $i < count($inventories) ; $i++){
        if(!$costprice_eligible) unset($inventories[$i]['costprice']);

        // Remove ending .00
        $inventories[$i]['qty'] = floatval($inventories[$i]['qty']);
        $inventories[$i]['unitprice'] = floatval($inventories[$i]['unitprice']);
        $inventories[$i]['costprice'] = floatval($inventories[$i]['costprice']);
        $inventories[$i]['totalcostprice'] = floatval($inventories[$i]['totalcostprice']);
        $inventories[$i]['unitdiscountamount'] = floatval($inventories[$i]['unitdiscountamount']);
        $inventories[$i]['unittotal'] = floatval($inventories[$i]['unittotal']);
      }
    $paymentaccount = chartofaccountdetail(null, array('id'=>$salesinvoice['paymentaccountid']));

    $salesinvoice['inventories'] = $inventories;
    $salesinvoice['warehousename'] = warehousedetail(null, array('id'=>$salesinvoice['warehouseid']))['name'];
    $salesinvoice['salesmanname'] = userdetail(null, array('id'=>$salesinvoice['salesmanid']))['name'];
    $salesinvoice['paymentaccountname'] = $paymentaccount ? $paymentaccount['name'] : '';

    //pm("update salesinvoicegroup set total = ? where `id` = ?", [ $salesinvoice['total'], $salesinvoice['id'] ]);

    if(in_array('customer', $columns)){
      $customer = pmr("select `id`, tax_registration_number from customer where `id` = ?", [ $salesinvoice['customerid'] ]);
      $salesinvoice['customer'] = [];
      foreach($customer as $key=>$value)
        $salesinvoice['customer' . (strlen($key) < 3 ? $key : '_' . $key)] = $value;
    }

  }

  return $salesinvoice;

}
function salesinvoicedetail_tax_to_nontax($columns, $filters){

  $salesinvoice = salesinvoicedetail($columns, $filters);

  $salesinvoice['taxamount'] = 0;
  $subtotal = 0;
  foreach($salesinvoice['inventories'] as $index=>$inventory){
    $salesinvoice['inventories'][$index]['unitprice'] = round($inventory['unitprice'] * 1.1);
    $salesinvoice['inventories'][$index]['unittotal'] = $salesinvoice['inventories'][$index]['unitprice'] * $inventory['qty'];
    $subtotal += $salesinvoice['inventories'][$index]['unittotal'];
  }
  $salesinvoice['subtotal'] = $subtotal;
  $salesinvoice['discountamount'] = $salesinvoice['discount'] > 0 ? $salesinvoice['discount'] / 100 * $subtotal : $salesinvoice['discountamount'];
  $salesinvoice['total'] = $subtotal - $salesinvoice['discountamount'] + $salesinvoice['deliverycharge'];
  return $salesinvoice;

}
function salesinvoicelist($columns, $sorts, $filters, $limits = null, $groups = null){

  global $salesinvoice_columnaliases;
  $params = [];

  if(!isset($filters) || !is_array($filters)) $filters = [];

  $wherequery = 'WHERE t1.id = t2.salesinvoiceid ' .
    str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesinvoice_columnaliases, $columns));

  if(is_array($groups) && count($groups) > 0){

    if(count($groups) > 0){

      if($groups[count($groups) - 1]['name'] == 'code'){
        $pivot_group = $groups[count($groups) - 1];
        $pivot_group_query = groupquery_from_groups([ $pivot_group ], $salesinvoice_columnaliases);
      }

      $group = $groups[0];
      $columnquery = columnquery_from_columnaliases($columns, $salesinvoice_columnaliases);
      $group_query = groupquery_from_groups([ $group ], $salesinvoice_columnaliases);
      $group_column = groupcolumn_from_group($group, $salesinvoice_columnaliases);

      $query = "SELECT $group_column FROM (
        SELECT $columnquery FROM salesinvoice t1, salesinvoiceinventory t2 $wherequery $pivot_group_query
      ) as s1 $group_query order by $group[name]";
      //exc([ $columnquery, $params ]);
      $data = pmrs($query, $params);

    }

  }
  else{

    $columnquery = columnquery_from_columnaliases($columns, $salesinvoice_columnaliases);
    $sortquery = sortquery_from_sorts($sorts, $salesinvoice_columnaliases);
    $limitquery = limitquery_from_limitoffset($limits);
    $query = "SELECT 'salesinvoice' as `type`, t1.id, $columnquery FROM salesinvoice t1, salesinvoiceinventory t2 $wherequery $sortquery $limitquery";

//    if($_SERVER['REMOTE_ADDR'] == '103.79.155.34') exc($query);
    $data = pmrs($query, $params);

  }

  if(is_array($data)){
    $costprice_privilege = privilege_get('inventory', 'costprice');
    if(!$costprice_privilege){
      for($i = 0 ; $i < count($data) ; $i++){
        if(isset($data[$i]['costprice'])) $data[$i]['costprice'] = 0;
        if(isset($data[$i]['totalcostprice'])) $data[$i]['totalcostprice'] = 0;
        if(isset($data[$i]['margin'])) $data[$i]['margin'] = 0;
      }
    }
  }

  //echo "<script>console.log(" . json_encode([ $query, $params ]) . ")</script>";
  //echo "<script>console.log(" . json_encode($data) . ")</script>";

  return $data;

}
function salesinvoicelist_onlytax($columns, $sorts, $filters, $limits = null){

  global $salesinvoice_columnaliases;

  $columns = [];
  foreach($salesinvoice_columnaliases as $key=>$value)
    if(strpos($value, 't1.') !== false)
      $columns[] = [ 'active'=>1, 'name'=>$key ];
  
  if(!$filters) $filters = [];
  
  $filters[] = [ 'name'=>'taxable', 'operator'=>'=', 'value'=>'1' ];

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesinvoice_columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesinvoiceid AND t2.inventoryid = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesinvoice_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesinvoice_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  $offset = $limits['offset'];

  $columnquery = strlen($columnquery) > 0 ? ', ' . $columnquery : $columnquery;

  $query = "SELECT t1.id, 
    (SELECT tax_registration_number FROM customer WHERE `id` = t1.customerid) as customer_tax_registration_number 
    $columnquery
     FROM salesinvoice t1, salesinvoiceinventory t2, inventory t3 $wherequery group by t1.id $sortquery $limitquery";

  $data = pmrs($query, $params);

  $salesinvoiceids = [];
  if(is_array($data))
    foreach($data as $obj)
      $salesinvoiceids[] = $obj['id'];

  if(count($salesinvoiceids) > 0){

    $items = pmrs("select t1.*, t2.taxable_excluded from salesinvoiceinventory t1, inventory t2 where t1.inventoryid = t2.id and t1.salesinvoiceid in (" . implode(', ', $salesinvoiceids) . ")");
    $items = array_index($items, [ 'salesinvoiceid' ]);

    $tax_period = systemvarget('tax_period');
    $tax_registration_number = systemvarget('tax_registration_number', '');
    $tax_company_name = systemvarget('tax_company_name', '');
    $tax_company_address = systemvarget('tax_company_address', '');
    $tax_decimal = systemvarget('tax_decimal', 1);

    for($i = 0 ; $i < count($data) ; $i++){

      $obj = $data[$i];
      $id = $obj['id'];

      $data[$i]['subtotal_with_discount'] = $data[$i]['subtotal'] - $data[$i]['discountamount'];
      $data[$i]['tax_period'] = $tax_period;
      $data[$i]['tax_year'] = date('Y');
      $data[$i]['tax_registration_number'] = $tax_registration_number;
      $data[$i]['tax_company_name'] = $tax_company_name;
      $data[$i]['tax_company_address'] = $tax_company_address;
      $data[$i]['tax_decimal'] = $tax_decimal;
      $data[$i]['items'] = $items[$id];
      $data[$i]['index'] = $offset + $i + 1;

    }

  }

  $rows = $data;
  $data = [];

  if(is_array($rows)){
    $counter = 0;
    foreach($rows as $obj){

      $counter++;
      $items = $obj['items'];
      $customerdescription = $obj['customerdescription'];

      if(!$customerdescription || count($items) <= 0) continue;

      $taxable_excluded = 1;
      for($i = 0 ; $i < count($items) ; $i++)
        if(!$items[$i]['taxable_excluded']){ $taxable_excluded = 0; break; }

      $customeraddress = $obj['customeraddress'];
      $tax_decimal = $obj['tax_decimal'];
      $tax_year = $obj['tax_year'];
      $tax_code = $obj['tax_code'];
      $tax_company_name = $obj['tax_company_name'];
      $tax_company_address = $obj['tax_company_address'];
      $code = $obj['code'];
      $date = date('d/m/Y', strtotime($obj['date']));
      $subtotal = $obj['subtotal'];
      $subtotal = number_format($subtotal, $tax_decimal);
      $subtotal_with_discount = $obj['subtotal_with_discount'];
      $subtotal_with_discount = number_format($subtotal_with_discount, 0, '', '');
      $customer_tax_registration_number = $obj['customer_tax_registration_number'];

      $taxamount = 0;
      for($i = 0 ; $i < count($items) ; $i++){
        $item = $items[$i];
        $unittotal = round($item['unittotal']);
        $unittaxamount = round($unittotal * .1);
        $taxamount += $unittaxamount;
      }
      $taxamount = number_format($taxamount, 0, '', '');

      $tax_code = str_replace('.', '', $tax_code); // Only allow numbers

      $data[] = [
        'column0'=>'FK',
        'column1'=>str_pad($taxable_excluded ? 8 : 1, 2, '0', STR_PAD_LEFT),
        'column2'=>'0',
        'column3'=>str_pad($tax_code, 13, '0', STR_PAD_LEFT),
        'column4'=>date('n', strtotime($obj['date'])),
        'column5'=>$tax_year,
        'column6'=>$date,
        'column7'=>str_pad($customer_tax_registration_number, 15, '0', STR_PAD_LEFT),
        'column8'=>trim(preg_replace('/\s+/', ' ', $customerdescription)),
        'column9'=>trim(preg_replace('/\s+/', ' ', $customeraddress)),
        'column10'=>$subtotal_with_discount,
        'column11'=>$taxamount,
        'column12'=>'0',
        'column13'=>$taxable_excluded ? '2' : '',
        'column14'=>'0',
        'column15'=>'0',
        'column16'=>'0',
        'column17'=>'0',
        'column18'=>$code
      ];

      for($i = 0 ; $i < count($items) ; $i++){
        $item = $items[$i];
        $unitprice = round($item['unitprice']);
        $qty = $item['qty'];
        $unittotal = round($item['unittotal']);
        $unitdiscountamount = round($item['unitdiscountamount']);
        $unittaxamount = round($unittotal * .1);

        $qty = number_format($qty, 1, '.', '');
        $unitprice = number_format($unitprice, 1, '.', '');
        $unittotal = number_format($unittotal, 1, '.', '');
        $unitdiscountamount = number_format($unitdiscountamount, 1, '.', '');
        $unittaxamount = number_format($unittaxamount, 1, '.', '');

        $data[] = [
          'column0'=>'OF',
          'column1'=>$item['inventorycode'],
          'column2'=>trim(preg_replace('/\s+/', ' ', $item['inventorydescription'])),
          'column3'=>$unitprice,
          'column4'=>$qty,
          'column5'=>$unittotal,
          'column6'=>$unitdiscountamount,
          'column7'=>$unittotal,
          'column8'=>$unittaxamount,
          'column9'=>'0',
          'column10'=>'0.0',
          'column11'=>'',
          'column12'=>'',
          'column13'=>'',
          'column14'=>'',
          'column15'=>'',
          'column16'=>'',
          'column17'=>'',
          'column18'=>'',
        ];
      }

    }
  }

  return $data;

}
function salesinvoice_issent($id, $state){

  $salesinvoice = pmr("SELECT `id`, issent, taxable, tax_code FROM salesinvoice WHERE `id` = ?", array($id));
  if($salesinvoice){

    pm("UPDATE salesinvoice SET issent = ?, senttime = ?, sentby = ? WHERE `id` = ?",
      array($state ? 1 : 0, date('YmdHis'), $_SESSION['user']['id'], $id));

//    if($salesinvoice['taxable'] && !$salesinvoice['tax_code']){
//      $tax_code = taxreservationpool_get('SI', $id);
//      salesinvoicemodify([
//        'id'=>$salesinvoice['id'],
//        'tax_code'=>$tax_code
//      ]);
//    }

  }

}
function salesinvoicepaymentaccountids(){

  $query = "SELECT * FROM chartofaccount WHERE accounttype LIKE 'Asset' OR code LIKE '000.00'";
  $rows = pmrs($query);
  return array_cast($rows, array('text'=>'name', 'value'=>'id'));

}
function salesinvoicegroup($group, $filters = null){

  $params = array();
  $wherequery = 'WHERE t1.id = t2.salesinvoiceid' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters));
  //$wherequery = wherequery_from_filters($params, $filters);
  $columnquery = columnquery_from_groupcolumns(ov('columns', $group));

  $groupaggregrate = ov('aggregrate', $group);
  switch($groupaggregrate){
    case 'monthly':
      $groupquery = "GROUP BY MONTH(`" . $group['name'] . "`)";
      break;
    default:
      $groupquery = "GROUP BY `" . $group['name'] . "`";
      break;
  }


  $query = "SELECT $columnquery FROM salesinvoice t1, salesinvoiceinventory t2 $wherequery $groupquery";
  //$query = "SELECT $columnquery FROM salesinvoice $wherequery $groupquery";

  //echo uijs("console.warn('$query')");
  //echo uijs("console.log(" . json_encode($params) . ")");

  $result = pmrs($query, $params);
  return $result;

}
function salesinvoicelistbyinventory_duedayssort($obj1, $obj2){
  if($obj1['duedays'] == $obj2['duedays']) return 0;
  return $obj1['duedays'] > $obj2['duedays'] ? -1 : 1;
}
function salesinvoicelistbyinventory($columns, $filters, $sorts = null){

  //$filters = privilege_get('salesinvoice', 'list') == 1 ? ' AND p1.createdby = ' . $_SESSION['user']['id'] : '';

  $columnmaps = array(
    'id'=>'p1.id',
    'ispaid'=>'p1.ispaid',
    'isgroup'=>'p1.isgroup',
    'code'=>'p1.code',
    'date'=>'p1.date',
    'customerdescription'=>'p1.customerdescription',
    'address'=>'p1.address',
    'subtotal'=>'p1.subtotal',
    'discount'=>'p1.discount',
    'discountamount'=>'p1.discountamount',
    'taxamount'=>'p1.taxamount',
    'deliverycharge'=>'p1.deliverycharge',
    'total'=>'p1.total',
    'paymentamount'=>'p1.paymentamount',
    'paymentdate'=>'p1.paymentdate',
    'inventoryid'=>'p2.inventoryid',
    'inventorycode'=>'p2.inventorycode',
    'inventorydescription'=>'p2.inventorydescription',
    'qty'=>'p2.qty',
    'unitprice'=>'p2.unitprice',
    'costprice'=>'p2.costprice',
    'avgsalesmargin'=>'p1.avgsalesmargin',
    'pocode'=>'p1.pocode',
    'createdon'=>'p1.createdon',
  );

  $columnqueries = 'p1.*, p2.*';
  if(is_array($columns)){
    $columnqueries = array('p1.id', 'p1.salesmanid', 'p1.warehouseid', 'p1.createdby', 'p1.paymentamount', 'p1.total');
    foreach($columns as $column)
      if(isset($columnmaps[$column])) $columnqueries[] = $columnmaps[$column];
    if(count($columnqueries) > 0) $columnqueries = implode(', ', $columnqueries);
  }

  $params = array();

  $wherequeries = '';
  if(is_array($filters)){
    if(isset($filters['key'])){
      $wherequeries = array();
      $key = $filters['key'];
      $wherequeries[] = "customerdescription LIKE ?";
      array_push($params, "%$key%");
      $wherequeries[] = "inventorydescription LIKE ?";
      array_push($params, "%$key%");
      $wherequeries[] = "inventorycode LIKE ?";
      array_push($params, "%$key%");

      $wherequeries = ' AND (' . implode(' OR ', $wherequeries) . ')';
    }
    else if(isset($filters['customerid'])){
      $wherequeries = ' AND p1.customerid = ?';
      array_push($params, $filters['customerid']);
    }
  }

  $query = "SELECT $columnqueries FROM salesinvoice p1, salesinvoiceinventory p2
    WHERE p1.id = p2.salesinvoiceid $wherequeries";
  $rows = pmrs($query, $params);

  if(is_array($rows)){
    $users = userlist(null, null);
    $users = array_index($users, array('id'), 1);
    $warehouses = warehouselist(null, null);
    $warehouses = array_index($warehouses, array('id'), 1);
    $costprice_eligible = privilege_get('inventory', 'costprice');


    for($i = 0 ; $i < count($rows) ; $i++){
      $dayelapsed = (strtotime(date('Ymd')) - strtotime($rows[$i]['date'])) / (60 * 60 * 24);
      $rows[$i]['duedays'] = $dayelapsed < $rows[$i]['creditterm'] || $rows[$i]['paymentamount'] >= $rows[$i]['total'] ? 0 : $dayelapsed - $rows[$i]['creditterm'];
      $rows[$i]['createdby'] = isset($users[$rows[$i]['createdby']]) ? $users[$rows[$i]['createdby']]['name'] : '';
      $rows[$i]['salesmanname'] = isset($users[$rows[$i]['salesmanid']]) ? $users[$rows[$i]['salesmanid']]['name'] : '';
      $rows[$i]['warehousename'] = isset($warehouses[$rows[$i]['warehouseid']]) ? $warehouses[$rows[$i]['warehouseid']]['name'] : '';

      if(!$costprice_eligible) unset($rows[$i]['avgsalesmargin']);
    }
  }

  return $rows;
  
}
function salesinvoice_uicolumns(){

  $columns = [
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>40),
    array('active'=>1, 'name'=>'_options', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options', 'align'=>'center'),
    array('active'=>1, 'name'=>'isactive', 'text'=>'Aktif', 'width'=>40, 'type'=>'html', 'html'=>'grid_isactive', 'align'=>'center'),
    array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>40, 'type'=>'html', 'html'=>'grid_ispaid', 'align'=>'center'),
    array('active'=>1, 'name'=>'taxable', 'text'=>'PPn', 'width'=>30, 'type'=>'html', 'html'=>'grid_taxable', 'align'=>'center', 'datatype'=>'boolean'),
    array('active'=>1, 'name'=>'issent', 'text'=>'Terkirim', 'width'=>40, 'type'=>'html', 'html'=>'grid_issent', 'align'=>'center'),
    array('active'=>0, 'name'=>'isprint', 'text'=>'Cetak', 'width'=>40, 'type'=>'html', 'html'=>'grid_isprint', 'align'=>'center'),
    array('active'=>1, 'name'=>'isgroup', 'text'=>'Grup Faktur', 'width'=>40, 'type'=>'html', 'html'=>'grid_isgroup', 'align'=>'center'),
    array('active'=>0, 'name'=>'isreceipt', 'text'=>'Kwitansi', 'width'=>40, 'type'=>'html', 'html'=>'grid_isreceipt', 'align'=>'center'),
    array('active'=>0, 'name'=>'isreconciled', 'text'=>'Rekonsil', 'width'=>40, 'type'=>'html', 'html'=>'grid_isreconciled', 'align'=>'center'),
    array('active'=>1, 'name'=>'journal', 'text'=>'Jurnal', 'width'=>40, 'align'=>'center', 'type'=>'html', 'html'=>'grid_journaloption'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>110),
    array('active'=>0, 'name'=>'tax_code', 'text'=>'Kode Pajak', 'width'=>100),
    array('active'=>0, 'name'=>'pocode', 'text'=>'Kode PO', 'width'=>100),
    array('active'=>0, 'name'=>'salesmanid', 'text'=>'ID Salesman', 'width'=>40),
    array('active'=>0, 'name'=>'salesmanname', 'text'=>'Nama Salesman', 'width'=>100),
    array('active'=>0, 'name'=>'creditterm', 'text'=>'Lama Kredit', 'width'=>100, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'warehouseid', 'text'=>'ID Gudang', 'width'=>30, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'warehousename', 'text'=>'Nama Gudang', 'width'=>80),
    array('active'=>0, 'name'=>'customerid', 'text'=>'Id Pelanggan', 'width'=>30),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>0, 'name'=>'customeraddress', 'text'=>'Alamat', 'width'=>200),
    array('active'=>0, 'name'=>'customertaxcode', 'text'=>'NPWP', 'width'=>100),
    array('active'=>0, 'name'=>'discount', 'text'=>'Diskon%', 'width'=>40, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'discountamount', 'text'=>'Jumlah Diskon', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'taxamount', 'text'=>'Jumlah PPn', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'deliverycharge', 'text'=>'Ongkos Kirim', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'paymentaccountid', 'text'=>'ID Akun Pembayaran', 'width'=>40),
    array('active'=>0, 'name'=>'paymentaccountname', 'text'=>'Akun Pembayaran', 'width'=>100),
    array('active'=>0, 'name'=>'paymentdate', 'text'=>'Tgl Pembayaran', 'width'=>80, 'datatype'=>'date'),
    array('active'=>0, 'name'=>'avgsalesmargin', 'text'=>'Margin', 'width'=>50, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'paymentamount', 'text'=>'Jumlah Pembayaran', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'Id Barang', 'width'=>30),
    array('active'=>1, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>80),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Barang', 'width'=>200),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>80, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'costprice', 'text'=>'Harga Modal Barang', 'width'=>80, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'totalcostprice', 'text'=>'Total Modal Barang', 'width'=>80, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'margin', 'text'=>'Margin Barang', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unittotal', 'text'=>'Total Barang', 'width'=>80, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'returnqty', 'text'=>'Total Retur', 'width'=>80, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat', 'width'=>120, 'datatype'=>'datetime'),
    array('active'=>0, 'name'=>'salesinvoicegroupid', 'text'=>'ID Grup Faktur', 'width'=>40),
    array('active'=>0, 'name'=>'salesreceiptid', 'text'=>'ID Kwitansi', 'width'=>40),
  ];
  return $columns;

}
function salesinvoice_salesreceiptentry($salesinvoiceids){

  $salesinvoices = array();
  $total = 0;
  for($i = 0 ; $i < count($salesinvoiceids) ; $i++){
    $salesinvoice = salesinvoicedetail(null, array('id'=>$salesinvoiceids[$i]));
    $salesinvoices[] = $salesinvoice;
    $total += $salesinvoice['total'];
  }

  $salesreceipt = array(
    'code'=>salesreceiptcode(),
    'date'=>date('Ymd'),
    'customerdescription'=>$salesinvoices[0]['customerdescription'],
    'address'=>$salesinvoices[0]['address'],
    'salesinvoices'=>$salesinvoices,
    'total'=>$total
  );
  return $salesreceipt;

}
function salesinvoice_salesgroupentry($salesinvoiceids){

  if(!is_array($salesinvoiceids) || count($salesinvoiceids) == 0) throw new Exception('Invalid parameter.');

  $sid = array();
  for($i = 0 ; $i < count($salesinvoiceids) ; $i++)
    $sid[$salesinvoiceids[$i]] = 1;

  $salesinvoices = array();
  $total = 0;
  foreach($sid as $salesinvoiceid=>$temp){
    $salesinvoice = salesinvoicedetail(null, array('id'=>$salesinvoiceid));
    if($salesinvoice['isgroup']) throw new Exception('Faktur dengan kode ' . $salesinvoice['code'] . ' sudah ada grup.');
    $total += $salesinvoice['total'];
    $salesinvoices[] = $salesinvoice;
  }
  $customerdescription = $salesinvoices[0]['customerdescription'];
  $address = $salesinvoices[0]['address'];

  $salesgroup = array(
    'code'=>salesinvoicegroupcode(),
    'date'=>date('Ymd'),
    'customerdescription'=>$customerdescription,
    'address'=>$address,
    'salesinvoices'=>$salesinvoices,
    'total'=>$total
  );

  return $salesgroup;

}

function salesinvoiceentry($salesinvoice){

  $lock_file = __DIR__ . '/../usr/system/salesinvoice_entry.lock';
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan faktur, silakan ulangi beberapa saat lagi.');

  $warnings = [];
  $system_salesminimummargin = systemvarget('salesminimummargin');

  $isactive = 1;
  $status = 1;
  $code = ov('code', $salesinvoice, 1);
  $date = ov('date', $salesinvoice, 1, array('type'=>'date'));
  $customerid = ov('customerid', $salesinvoice);
  $address = ov('address', $salesinvoice, 0, '');
  $warehouseid = ov('warehouseid', $salesinvoice, 1);
  $pocode = ov('pocode', $salesinvoice);
  $creditterm = ov('creditterm', $salesinvoice);
  $inventories = ov('inventories', $salesinvoice, 1);
  $discount = ov('discount', $salesinvoice, 0, 0);
  $discountamount = ov('discountamount', $salesinvoice, 0, 0);
  $taxable = ov('taxable', $salesinvoice, 0, 0);
  $tax_code = ov('tax_code', $salesinvoice);
  $ispaid = ov('ispaid', $salesinvoice, 0, 0);
  $paymentamount = ov('paymentamount', $salesinvoice, 0, 0);
  $paymentdate = ov('paymentdate', $salesinvoice);
  $paymentaccountid = ov('paymentaccountid', $salesinvoice, 0);
  $deliverycharge = ov('deliverycharge', $salesinvoice);
  $salesorderid = ov('salesorderid', $salesinvoice, 0);
  $salesmanid = ov('salesmanid', $salesinvoice);
  $note = ov('note', $salesinvoice);

  // Validation
  if(pmc("select count(*) from salesinvoice where `code` = ?", [ $code ]) > 0){
    $code = salesinvoicecode($date, $taxable);
    $warnings[] = "Faktur berhasil disimpan dengan kode $code";
  }
  $customer = customerdetail(null, array('id'=>$customerid)); if(!$customer) throw new Exception('Pelanggan belum diisi.');
  if(!is_array($inventories) && count($inventories) == 0) throw new Exception('Barang belum diisi.');
  if(!$warehouseid || !($warehouse = warehousedetail(null, array('id'=>$warehouseid)))) throw new Exception('Gudang belum diisi.');
  if(!is_array($inventories) || count($inventories) == 0) throw new Exception('Barang belum diisi.');
  if($taxable && $tax_code && pmc("select count(*) from salesinvoice where tax_code = ?", [ $tax_code ]) > 0) exc('Kode pajak sudah ada.');
  customer_has_due_invoice($customerid);

  // Group inventory by code and unitprice
  if(systemvarget('salesinvoice_item_grouping')){
    $inventories = array_index($inventories, [ 'inventorycode', 'unitprice' ]);
    $temp = [];
    foreach($inventories as $inventorycode=>$inventorycodes){
      foreach($inventorycodes as $unitprice=>$inventoryunits){
        $qty = 0;
        foreach($inventoryunits as $inventoryunit)
          $qty+= $inventoryunit['qty'];
        $inventoryunit = $inventoryunits[0];
        $inventoryunit['qty'] = $qty;
        $temp[] = $inventoryunit;
      }
    }
    $inventories = $temp;
  }

  // Extended field
  $customerdescription = $customer['description'];
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  $inventorycount = 0;
  $inventory_taxable_excluded = [];
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventory = $inventories[$i];
    $inventorydescription = ov('inventorydescription', $inventory);
    if(empty($inventorydescription)) continue;
    $inventory_data = inventorydetail(null, array('description'=>$inventorydescription, 'taxable'=>$taxable));
    if(!$inventory) throw new Exception("Barang tidak terdaftar ($inventorydescription)");
    $qty = ov('qty', $inventory, 1, array('type'=>'decimal'));
    $unitprice = ov('unitprice', $inventory, 1, array('type'=>'money'));
    $avgcostprice = $inventory_data['avgcostprice'];

    // Qty validation
    if(systemvarget('use_qty')){
      if($inventory_data['qty'] <= 0)
        exc("Stok $inventorydescription tidak ada.");
      else if($inventory_data['qty'] < $qty)
        exc("Stok $inventorydescription tidak cukup, stok tersisa: " . $inventory_data['qty']);
    }

    // Costprice validation
    if(systemvarget('uselowestprice') > 0){
      if($inventory_data['lowestprice'] > 0){
        if($unitprice < $inventory_data['lowestprice'])
          exc('Harga minimal yang diset untuk barang ini adalah Rp. ' . number_format($inventory_data['lowestprice']));
      }
      else{
        $costprice = inventorycostprice_get($inventory_data['id']);
        $minimum_costprice = $costprice + ($system_salesminimummargin / 100 * $costprice);
        if($unitprice < $minimum_costprice)
          exc('Harga minimal untuk barang ini adalah Rp. ' . number_format($minimum_costprice));
      }
    }

    $inventories[$i]['inventoryid'] = $inventory_data['id'];
    $inventories[$i]['inventorycode'] = $inventory_data['code'];
    $inventories[$i]['qty'] = $qty;
    $inventories[$i]['avgcostprice'] = $avgcostprice;
    $inventories[$i]['unit'] = $inventory_data['unit'];
    $inventories[$i]['unitprice'] = $unitprice;
    $inventory_taxable_excluded[$inventory_data['taxable_excluded']] = 1;
    $inventorycount++;
  }
  if($inventorycount <= 0) throw new Exception('Barang harus diisi.');
  if(floatval($customer['creditlimit'] > 0 && floatval($customer['receivable']) + floatval($salesinvoice['total']) > floatval($customer['creditlimit'])))
    throw new Exception('Piutang pelanggan melebihi batas.');

  // Check if taxable invoice contains mixed product variation
  if($taxable && count(array_keys($inventory_taxable_excluded)) > 1) exc('Tidak dapat membuat faktur dari barang pajak & non pajak.');

  $query = "INSERT INTO salesinvoice(`status`, isactive, code, `date`, customerid, customerdescription, address, note, pocode, creditterm, warehouseid,
      discount, discountamount, taxable, tax_code, ispaid, paymentdate, paymentamount, paymentaccountid, salesorderid, deliverycharge, salesmanid,
      createdon, createdby, lastupdatedon)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($status, $isactive, $code, $date, $customerid, $customerdescription, $address, $note, $pocode, $creditterm, $warehouseid,
    $discount, $discountamount, $taxable, $tax_code, $ispaid, $paymentdate, $paymentamount, $paymentaccountid, $salesorderid, $deliverycharge,
    $salesmanid, $createdon, $createdby, $lastupdatedon));

  try{
  
    $params = $paramstr = array();
    for($i = 0 ; $i < count($inventories) ; $i++){

      $row = $inventories[$i];
      $inventorydescription = ov('inventorydescription', $row, 0);
      if(empty($inventorydescription)) continue;
      $inventoryid = $row['inventoryid'];
      $inventorycode = $row['inventorycode'];
      $qty = $row['qty'];
      $unit = $row['unit'];
      $unitprice = $row['unitprice'];
      $unittotal = $qty * $unitprice;

      $paramstr[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, 0, 0, $unittotal);
    }
    $query = "INSERT INTO salesinvoiceinventory(salesinvoiceid, inventoryid, inventorycode,
      inventorydescription, qty, unit, unitprice, unitdiscount, unitdiscountamount, unittotal) VALUES " . implode(',', $paramstr);
    pm($query, $params);

    salesinvoicecalculate($id);
    code_commit($code);

    userlog('salesinvoiceentry', $salesinvoice, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

    $result = array('id'=>$id, 'warnings'=>$warnings);
    return $result;

  }
  catch(Exception $ex){
  
  	salesinvoiceremove(array('id'=>$id));

    fclose($fp);
    unlink($lock_file);

    throw $ex;
  
  }

}
function salesinvoicemodify($salesinvoice){

  $id = ov('id', $salesinvoice, 1);
  $current = salesinvoicedetail(null, array('id'=>$id));
  if(!$current) throw new Exception("Invoice tidak ada.");

  $lock_file = __DIR__ . "/../usr/system/salesinvoice_modify_$id.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan faktur, silakan ulangi beberapa saat lagi.');

  // VALIDATION
  if($current['isreconciled']) throw new Exception('Tidak dapat mengubah faktur, sudah rekonsil.'); // Check if already reconciled

  // - Check if inventory is valid
  $inventory_taxable_excluded = [];
  $inventory_modified = false;
  if(isset($salesinvoice['inventories']) && is_array($salesinvoice['inventories']) && count($salesinvoice['inventories']) > 0) {

    // Check if inventories modified
    $inventory_modified = !is_array($current['inventories']) ? true : array_object_is_modified($current['inventories'], $salesinvoice['inventories'], [ 'inventorycode', 'qty:number', 'unitprice:number' ]);

    // If modified perform validation on each inventory
    if($inventory_modified){

      $inventories = $salesinvoice['inventories'];
      $taxable = isset($salesinvoice['taxable']) ? $salesinvoice['taxable'] : $current['taxable'];

      $current_inventories = $current['inventories'];
      $current_inventories_qty = $current_inventories_ids = [];
      foreach($current_inventories as $current_inventory){
        if(!isset($current_inventories_qty[$current_inventory['inventoryid']]))
          $current_inventories_qty[$current_inventory['inventoryid']] = 0;
        $current_inventories_qty[$current_inventory['inventoryid']] += $current_inventory['qty'];
      }

      $system_salesminimummargin = systemvarget('salesminimummargin');
      $temp = [];
      for($i = 0 ; $i < count($inventories) ; $i++){
        $inventory = $inventories[$i];
        $inventorydescription = ov('inventorydescription', $inventory);
        if(!$inventorydescription) continue;
        $inventory_data = inventorydetail(null, array('description'=>$inventorydescription, 'taxable'=>$taxable));
        if(!$inventory) throw new Exception("Barang tidak terdaftar ($inventorydescription)");
        $qty = ov('qty', $inventory, 1, array('type'=>'decimal'));
        $unitprice = ov('unitprice', $inventory, 1, array('type'=>'money'));

        if(systemvarget('use_qty')){
          $inventory_data_qty = $inventory_data['qty'] + (isset($current_inventories_qty[$inventory_data['id']]) ? $current_inventories_qty[$inventory_data['id']] : 0);
          if($inventory_data_qty <= 0)
            exc("Stok $inventorydescription tidak ada.");
          else if($inventory_data_qty < $qty)
            exc("Stok $inventorydescription tidak cukup, stok tersisa: " . $inventory_data_qty);
        }

        // Costprice validation
        if(systemvarget('uselowestprice') > 0){
          if($inventory_data['lowestprice'] > 0){
            if($unitprice < $inventory_data['lowestprice'])
              exc('Harga minimal yang diset untuk barang ini adalah Rp. ' . number_format($inventory_data['lowestprice']));
          }
          else{
            $costprice = inventorycostprice_get($inventory_data['id']);
            $minimum_costprice = $costprice + ($system_salesminimummargin / 100 * $costprice);
            if($unitprice < $minimum_costprice)
              exc('Harga minimal untuk barang ini adalah Rp. ' . number_format($minimum_costprice));
          }
        }
        $inventories[$i]['id'] = $inventory_data['id'];
        $inventories[$i]['code'] = $inventory_data['code'];
        $temp[] = $inventories[$i];
        $inventory_taxable_excluded[$inventory_data['taxable_excluded']] = 1;
      }
      $salesinvoice['inventories'] = $temp;

    }

  }

  // Check if taxable invoice contains mixed product variation
  if($current['taxable'] && count(array_keys($inventory_taxable_excluded)) > 1) exc('Faktur dengan barang tidak sejenis tidak dapat disimpan.');

  $customerids_changed = array();
  $warehouseids_changed = array();

  $updatedrow = array();

  if(isset($salesinvoice['isactive']) && $salesinvoice['isactive'] != $current['isactive'])
    $updatedrow['isactive'] = $salesinvoice['isactive'];

  if(isset($salesinvoice['date']) && strtotime($salesinvoice['date']) != strtotime($current['date']))
    $updatedrow['date'] = date('Ymd', strtotime($salesinvoice['date']));

  if(isset($salesinvoice['customerid']) && $salesinvoice['customerid'] != $current['customerid']){
    $customer = customerdetail(null, array('id'=>$salesinvoice['customerid']));
    if(!$customer) throw new Exception('Pelanggan tidak terdaftar.');
    customer_has_due_invoice($salesinvoice['customerid']);
    $updatedrow['customerid'] = $salesinvoice['customerid'];
    $updatedrow['customerdescription'] = $customer['description'];
    array_push($customerids_changed, $current['customerid'], $salesinvoice['customerid']);
  }

  if(isset($salesinvoice['address']) && $salesinvoice['address'] != $current['address'])
    $updatedrow['address'] = $salesinvoice['address'];

  if(isset($salesinvoice['pocode']) && $salesinvoice['pocode'] != $current['pocode'])
    $updatedrow['pocode'] = $salesinvoice['pocode'];

  if(isset($salesinvoice['creditterm']) && $salesinvoice['creditterm'] != $current['creditterm'])
    $updatedrow['creditterm'] = $salesinvoice['creditterm'];

  if(isset($salesinvoice['warehouseid']) && $salesinvoice['warehouseid'] != $current['warehouseid']){
    $updatedrow['warehouseid'] = $salesinvoice['warehouseid'];
    array_push($warehouseids_changed, $current['warehouseid'], $salesinvoice['warehouseid']);
  }

  if(isset($salesinvoice['salesmanid']) && $salesinvoice['salesmanid'] != $current['salesmanid'])
    $updatedrow['salesmanid'] = $salesinvoice['salesmanid'];

  if(isset($salesinvoice['note']) && $salesinvoice['note'] != $current['note'])
    $updatedrow['note'] = $salesinvoice['note'];

  if(isset($salesinvoice['discount']) && $salesinvoice['discount'] != $current['discount'])
    $updatedrow['discount'] = $salesinvoice['discount'];

  if(isset($salesinvoice['discountamount']) && $salesinvoice['discountamount'] != $current['discountamount'])
    $updatedrow['discountamount'] = $salesinvoice['discountamount'];

  if(isset($salesinvoice['deliverycharge']) && $salesinvoice['deliverycharge'] != $current['deliverycharge'])
    $updatedrow['deliverycharge'] = $salesinvoice['deliverycharge'];

  if(isset($salesinvoice['taxable']) && intval($salesinvoice['taxable']) != intval($current['taxable']))
    $updatedrow['taxable'] = $salesinvoice['taxable'];

  // Payment section
  if(isset($salesinvoice['ispaid']) && $salesinvoice['ispaid'] != $current['ispaid'])
    $updatedrow['ispaid'] = $salesinvoice['ispaid'];
  if(isset($salesinvoice['paymentdate']) && strtotime($salesinvoice['paymentdate']) && strtotime($salesinvoice['paymentdate']) != strtotime($current['paymentdate']))
    $updatedrow['paymentdate'] = date('Ymd', strtotime($salesinvoice['paymentdate']));
  if(isset($salesinvoice['paymentaccountname']) && $salesinvoice['paymentaccountname'] != $current['paymentaccountname']){
    $paymentaccount = chartofaccountdetail(null, array('name'=>$salesinvoice['paymentaccountname']));
    if(!$paymentaccount) throw new Exception('Akun pembayaran tidak terdaftar.');
    $updatedrow['paymentaccountid'] = $paymentaccount['id'];
  }
  if(isset($salesinvoice['paymentamount']) && $salesinvoice['paymentamount'] != $current['paymentamount'])
    $updatedrow['paymentamount'] = $salesinvoice['paymentamount'];
  if(isset($salesinvoice['paymentaccountid']) && $salesinvoice['paymentaccountid'] != 999 && $salesinvoice['paymentaccountid'] != $current['paymentaccountid'])
    $updatedrow['paymentaccountid'] = $salesinvoice['paymentaccountid'];

  // Isgroup section
  if(isset($salesinvoice['isgroup']) && $salesinvoice['isgroup'] != $current['isgroup'])
    $updatedrow['isgroup'] = $salesinvoice['isgroup'];
  if(isset($salesinvoice['salesinvoicegroupid']) && $salesinvoice['salesinvoicegroupid'] != $current['salesinvoicegroupid'])
    $updatedrow['salesinvoicegroupid'] = $salesinvoice['salesinvoicegroupid'];

  // Is reconciled
  if(isset($salesinvoice['isreconciled']) && $salesinvoice['isreconciled'] != $current['isreconciled'])
    $updatedrow['isreconciled'] = $salesinvoice['isreconciled'];

  // Update tax code
  if($current['taxable'] && isset($salesinvoice['tax_code']) &&
    ($salesinvoice['tax_code'] != $current['tax_code'] || empty($salesinvoice['tax_code']))){

    if(empty($salesinvoice['tax_code'])){
      // Release current tax code
      $salesinvoice['tax_code'] = taxreservationpool_get('SI', $id);
    }

    taxreservationpool_set('SI', $id, $salesinvoice['tax_code']);

    $updatedrow['tax_code'] = $salesinvoice['tax_code'];
  }

  if(count($updatedrow) > 0)
    mysql_update_row('salesinvoice', $updatedrow, array('id'=>$id));

  if(isset($updatedrow['code']))
    code_commit($updatedrow['code'], $current['code']);

  if($inventory_modified && isset($salesinvoice['inventories']) && is_array($salesinvoice['inventories']) && count($salesinvoice['inventories']) > 0) {

    $inventories = $salesinvoice['inventories'];

    // Group inventory by code and unitprice
    if(systemvarget('salesinvoice_item_grouping')) {
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

    $queries = $params = $paramstr = [];

    $queries[] = "delete from salesinvoiceinventory where salesinvoiceid = ?";
    array_push($params, $id);

    for($i = 0 ; $i < count($inventories) ; $i++){
      $row = $inventories[$i];
      $inventorydescription = $row['inventorydescription'];
      $inventoryid = $row['id'];
      $inventorycode = $row['code'];
      $qty = $row['qty'];
      $unit = $row['unit'];
      $unitprice = $row['unitprice'];
      $unittotal = $qty * $unitprice;

      $paramstr[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $inventoryid, $inventorycode, $inventorydescription, $qty, $unit, $unitprice, 0, 0, $unittotal);
    }
    if(count($paramstr) > 0)
      $queries[] = "INSERT INTO salesinvoiceinventory(salesinvoiceid, inventoryid, inventorycode,
        inventorydescription, qty, unit, unitprice, unitdiscount, unitdiscountamount, unittotal) VALUES " . implode(',', $paramstr);

    // Apply changes to db
    if(count($queries) > 0)
      pm(implode(';', $queries), $params);

    $updatedrow['inventories'] = $salesinvoice['inventories'];

  }
  salesinvoicecalculate($id, $inventory_modified);

  userlog('salesinvoicemodify', $current, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  return array('id'=>$id);

}
function salesinvoiceremove($filters){

  $salesinvoice = salesinvoicedetail(null, $filters);

  if($salesinvoice){

    $id = $salesinvoice['id'];
    $code = $salesinvoice['code'];

    $lock_file = __DIR__ . "/../usr/system/salesinvoice_remove_$id.lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus faktur, silakan ulangi beberapa saat lagi.');

    if($salesinvoice['isgroup']) throw new Exception('Tidak dapat menghapus faktur ini, sudah ada grup faktur.');
    if($salesinvoice['isreconciled']) throw new Exception('Tidak dapat menghapus faktur ini, sudah rekonsil.');
    if(intval(pmc("SELECT COUNT(*) FROM salesreturninventory WHERE salesinvoiceinventoryid IN (SELECT t2.id FROM salesinvoice t1, salesinvoiceinventory t2
     WHERE t1.id = t2.salesinvoiceid AND t1.id = ?)", array($id)))) throw new Exception('Tidak dapat menghapus faktur ini, sudah ada retur.'); // Check if sales return exists

    inventorybalanceremove(array('ref'=>'SI', 'refid'=>$id));
    journalvoucherremove(array('ref'=>'SI', 'refid'=>$id));
    pm("UPDATE salesorder SET isinvoiced = 0 WHERE `id` = ?", array($salesinvoice['salesorderid']));
    pm("DELETE FROM salesinvoice WHERE `id` = ?", array($id));
    code_release($code);
    taxreservationpool_remove($salesinvoice['tax_code'], 'SI', $id);

    userlog('salesinvoiceremove', $salesinvoice, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

    global $_REQUIRE_WORKER; $_REQUIRE_WORKER = true;

  }
  else
    throw new Exception('Faktur tidak ada.');

}

function salesinvoice_salesinvoicegroup_clear($salesinvoicegroupid){

  pm("UPDATE salesinvoice SET salesinvoicegroupid = null, isgroup = 0 WHERE salesinvoicegroupid = ?", array($salesinvoicegroupid));

}
function salesinvoicecalculate($id, $inventory_ischanged = true){

  $salesinvoice = salesinvoicedetail(null, array('id'=>$id));
  if(!$salesinvoice) return;
  if(!$salesinvoice['customerdescription']) return;
  if(count($salesinvoice['inventories']) <= 0) return;

  $date = ov('date', $salesinvoice);
  $customerid = $salesinvoice['customerid'];
  $code = ov('code', $salesinvoice);
  $salesorderid = $salesinvoice['salesorderid'];
  $salesorder = salesorderdetail(null, array('id'=>$salesorderid));

  // Subtotal, discountamount, taxamount, total
  $inventories = $salesinvoice['inventories'];
  $discountamount = ov('discountamount', $salesinvoice);
  $subtotal = 0;
  $taxable_excluded = 1;
  for($i = 0 ; $i < count($inventories) ; $i++){
    $row = $inventories[$i];
    $inventoryid = ov('inventoryid', $row);
    $inventory = inventorydetail(null, array('id'=>$inventoryid));
    $qty = ov('qty', $row);
    $unitprice = ov('unitprice', $row);
    $unittotal = $qty * $unitprice;
    $subtotal += $unittotal;
    if(!$inventory['taxable_excluded']) $taxable_excluded = 0;
  }
  $total = $subtotal;
  $discount = $salesinvoice['discount'];
  if(intval($discount) && $discount > 0)
    $discountamount = $discount / 100 * $total;
  if(!floatval($discountamount)) $discountamount = 0;
  $total -= $discountamount;
  $taxable = $salesinvoice['taxable'];
  $taxamount = $taxable && !$taxable_excluded ? $total * 0.1 : 0;
  $deliverycharge = $salesinvoice['deliverycharge'];
  $total += floor($taxamount + $deliverycharge);
  $total = salesinvoice_total($salesinvoice)['total'];
  $paymentamount = $salesinvoice['paymentamount'];
  $paymentaccountid = $salesinvoice['paymentaccountid'];
  $ispaid = abs($paymentamount - $total) < 1 ? 1 : ($paymentamount > 0 ? 2 : 0);
  $paymentdate = $salesinvoice['paymentdate'];
  $query = "UPDATE salesinvoice SET subtotal = ?, discountamount = ?, taxamount = ?, ispaid = ?, total = ? WHERE `id` = ?";
  pm($query, array($subtotal, $discountamount, $taxamount, $ispaid, $total, $id));

  // Journal voucher
  if($paymentamount > 0){

    if($paymentamount != $total && abs($paymentamount - $total) < 1)
    $total = $paymentamount;

    $details = array(
      array('coaid'=>6, 'debitamount'=>0, 'creditamount'=>$total),
      array('coaid'=>$paymentaccountid, 'debitamount'=>$paymentamount, 'creditamount'=>0),
    );
    if($paymentamount < $total)
      $details[] = array('coaid'=>4, 'debitamount'=>$total - $paymentamount, 'creditamount'=>0);
    $jvdate = $paymentdate;

  }
  else{
    $details = array(
      array('coaid'=>6, 'debitamount'=>0, 'creditamount'=>$total),
      array('coaid'=>4, 'debitamount'=>$total, 'creditamount'=>0)
    );
    $jvdate = $date;
  }
  $details[] = array('coaid'=>10, 'debitamount'=>0, 'creditamount'=>0); // Persediaan Barang Dagang
  $details[] = array('coaid'=>11, 'debitamount'=>0, 'creditamount'=>0); // COGS
  $journal = array(
    'ref'=>'SI',
    'refid'=>$id,
    'type'=>'A',
    'date'=>$jvdate,
    'description'=>$salesinvoice['customerdescription'],
    'details'=>$details
  );
  journalvoucherentryormodify($journal);

  // Inventory balance
  $inventorybalances = [];
  for($i = 0 ; $i < count($inventories) ; $i++){
    $inventory = $inventories[$i];
    $inventoryid = $inventory['inventoryid'];
    if(!$inventoryid) continue;
    $qty = ov('qty', $inventory);

    $inventorybalance = array(
      'ref'=>'SI',
      'refid'=>$id,
      'refitemid'=>$inventory['id'],
      'date'=>$salesinvoice['date'],
      'inventoryid'=>$inventoryid,
      'warehouseid'=>$salesinvoice['warehouseid'],
      'description'=>$salesinvoice['customerdescription'],
      'out'=>$qty,
      'createdon'=>$salesinvoice['createdon']
    );
    $inventorybalances[] = $inventorybalance;
  }
  inventorybalanceremove(array('ref'=>'SI', 'refid'=>$id));
  inventorybalanceentries($inventorybalances);

  // salesorderid
  if($salesorderid) pm("UPDATE salesorder SET isinvoiced = 1 WHERE `id` = ?", array($salesorderid));
  if($salesinvoice['isgroup']) salesinvoicegroup_salesinvoicemodify($salesinvoice['salesinvoicegroupid']);

  customerreceivablecalculate($customerid);
  code_commit($code);

  global $_REQUIRE_WORKER; $_REQUIRE_WORKER = true;

}
function salesinvoice_release_unused(){

  //pm("update salesinvoice set code = '' where `id` not in (select salesinvoiceid from salesinvoiceinventory);");

}
function salesinvoice_fill_unfilled_cogs(){

  // Find unfilled cogs
  $rows = pmrs("SELECT t1.ref, t1.refid from journalvoucher t1, journalvoucherdetail t2
    where t1.id = t2.jvid and t1.ref = 'SI' and t2.coaid = 10 and t2.credit = 0 ORDER BY t1.lastupdatedon DESC LIMIT 10", [  ]);

  $count = 0;
  foreach($rows as $row){

    $id = $row['refid'];
    $inventories = pmrs("select qty, costprice from salesinvoiceinventory where salesinvoiceid = ?", [ $id ]);
    $totalcostprice = 0;
    if(is_array($inventories)){
      foreach($inventories as $inventory){
        $totalcostprice += $inventory['costprice'] * $inventory['qty'];
      }
    }
    journalvoucheritemmodify([ 'ref'=>'SI', 'refid'=>$id, 'coaid'=>10 ], [ 'credit'=>$totalcostprice ]);
    journalvoucheritemmodify([ 'ref'=>'SI', 'refid'=>$id, 'coaid'=>11 ], [ 'debit'=>$totalcostprice ]);
    $count++;

  }
  echo "Modified unfilled cogs: $count" . PHP_EOL;

}
function salesinvoice_process_no_tax_code($progress_callback = null){

  $salesinvoices = pmrs("select `id` from salesinvoice where taxable = 1 and (tax_code = '' or tax_code is null)");

  $count = 0;
  if(is_array($salesinvoices)){
    foreach($salesinvoices as $salesinvoice){

      $tax_code = taxreservationpool_get('SI', $salesinvoice['id']);
      mysql_update_row('salesinvoice', [ 'tax_code'=>$tax_code ], array('id'=>$salesinvoice['id']));
      $count++;

      if(is_callable($progress_callback)){
        call_user_func_array($progress_callback, [
          [ 'max'=>count($salesinvoices), 'current'=>$count, 'percentage'=>round($count/count($salesinvoices)*100) ]
        ]);
        ob_end_flush();
        ob_flush();
      }

    }
  }
  
  return [
    'count'=>$count
  ];

}

/**
 * @param $start_date
 * @param $end_date
 */

function salesinvoice_total($salesinvoice){

  $taxable = $salesinvoice['taxable'];
  $salesinvoice['taxamount'] = 0;
  $subtotal = 0;
  foreach($salesinvoice['inventories'] as $index=>$inventory){
    $taxable_excluded = ov('taxable_excluded', $inventory, 0, 1);
    $current_taxable = $taxable && !$taxable_excluded;
    $salesinvoice['inventories'][$index]['unitprice'] = round($inventory['unitprice'] * ($current_taxable ? 1.1 : 1));
    $salesinvoice['inventories'][$index]['unittotal'] = $salesinvoice['inventories'][$index]['unitprice'] * $inventory['qty'];
    $subtotal += $salesinvoice['inventories'][$index]['unittotal'];
  }
  $discountamount = $salesinvoice['discount'] > 0 ? $salesinvoice['discount'] / 100 * $subtotal : $salesinvoice['discountamount'];
  $deliverycharge = ov('deliverycharge', $salesinvoice, 0, 0);
  $total = $subtotal - $discountamount + $deliverycharge;
//  if($_SESSION['user']['userid'] == 'andy') exc($total);
  return [
    'subtotal'=>$subtotal,
    'discountamount'=>$discountamount,
    'total'=>$total,
  ];

}
function salesinvoicetaxcodegenerate($start_date, $end_date){

  // Retrieve salesinvoices
  $salesinvoices = pmrs("select t1.id, t1.code, t3.taxable_excluded from salesinvoice t1, salesinvoiceinventory t2, inventory t3 
    where t1.id = t2.salesinvoiceid and t2.inventoryid = t3.id and t1.taxable = 1 and t1.date >= ? and t1.date <= ? 
    group by t1.id
    order by t1.code", [ $start_date, $end_date ]);

  if(is_array($salesinvoices))
    foreach($salesinvoices as $salesinvoice)
      $salesinvoiceids[] = $salesinvoice['id'];

  if(!$salesinvoices) exc('Tidak ada faktur untuk diproses dalam range tanggal ini.');

  // Release and clear tax code
  taxreservationpool_release_batch('SI', $salesinvoiceids);
  pm("update salesinvoice set tax_code = '' where taxable = 1 and `date` >= ? and `date` <= ?", [ $start_date, $end_date ]);

  $codes = taxreservationpool_reserve_batch('SI', $salesinvoiceids);

  $queries = $params = [];
  for($i = 0 ; $i < count($salesinvoices) ; $i++){

    $salesinvoice = $salesinvoices[$i];
    $code = $codes[$i];
    $salesinvoiceid = $salesinvoice['id'];

    $queries[] = "update salesinvoice set tax_code = ? where `id` = ?";
    array_push($params, $code, $salesinvoiceid);

  }
  pm(implode(';', $queries), $params);

}
function salesinvoicetaxnontaxrecap($start_date, $end_date){

  // Retrieve salesinvoices
  $salesinvoices = pmrs("select t1.id, t1.code, t1.total from salesinvoice t1, salesinvoiceinventory t2, inventory t3 
    where t1.id = t2.salesinvoiceid and t2.inventoryid = t3.id and t1.taxable = 1 and t1.date >= ? and t1.date <= ? 
    and t3.taxable_excluded = 1
    group by t1.id
    order by t1.code", [ $start_date, $end_date ]);

  $total = 0;
  foreach($salesinvoices as $salesinvoice)
    $total += $salesinvoice['total'];

  $result = [
    'total'=>$total,
    'count'=>count($salesinvoices)
  ];

  return $result;

}

function salesinvoice_notification_generate(){

  $notifications = [];

  // User with customer list
  $users = pmrs("select distinct(userid) as `id` from userprivilege where `module` = 'salesinvoice' and `key` = 'list'");

  $count = pmc("select count(*) from salesinvoice where customerdescription like 'cash' and ispaid = 0");

  $notifications[] = [
    'key'=>'salesinvoice.cash.due',
    'date'=>date('Ymd'),
    'title'=>'Faktur cash belum lunas',
    'description'=>$count . ' faktur cash belum lunas.',
    'users'=>$users
  ];

  return $notifications;

}

function salesinvoice_audit($start_date, $end_date){

  // Retrieve logs from date range
  $logs = pmrs("select refid from userlog where `timestamp` between ? and ?
    and `action` in ('salesinvoiceentry')",
    [ $start_date, $end_date ]);
  $salesinvoices = array_index($logs, [ 'refid' ], true);

  // Retrieve logs from salesinvoice ids
  $salesinvoiceids = [];
  foreach($salesinvoices as $salesinvoiceid=>$salesinvoice)
    $salesinvoiceids[] = $salesinvoiceid;
  $salesinvoices = [];
  do{
    $current_salesinvoiceids = array_splice($salesinvoiceids, 0, 300);
    if(count($current_salesinvoiceids) > 0){

      $logs = pmrs("select * from userlog where `action` in ('salesinvoiceentry', 'salesinvoicemodify', 'salesinvoiceremove')
        and refid in (" . implode(', ', $current_salesinvoiceids) . ") order by `timestamp`");
      $logs = array_index($logs, [ 'refid' ]);
      foreach($logs as $key=>$log)
        $salesinvoices[$key] = $log;
    }
  }
  while(count($current_salesinvoiceids) > 0);

  // Auditing
  // Entering loop to find ...
  $result_removed_salesinvoices = [];
  $result_missing_salesinvoices = [];
  $result_mismatch_salesinvoices = [];
  $result_ok_salesinvoices = [];
  foreach($salesinvoices as $salesinvoiceid => $logs){

    $type = 'ok';

    // 1. Find deleted sales invoice
    foreach($logs as $index=>$log){
      if($log['action'] == 'salesinvoiceremove'){
        $type = 'removed';
        $result_removed_salesinvoices[] = $salesinvoiceid;
      }
    }

    // 2. Find missing salesinvoice
    $salesinvoice = pmc("select * from salesinvoice where `id` = ?", [ $salesinvoiceid ]);
    if(!$salesinvoice){
      $type = 'missing';
      $result_missing_salesinvoices[] = $salesinvoiceid;
    }

    // 3. Inconsistent state
    if($logs[count($logs) - 1]['action'] != 'salesinvoiceremove'){
      $last_log = $logs[count($logs) - 1];
      $inconsitency_data = salesinvoice_compare_with_logid($last_log['id']);
      if(!$inconsitency_data['matched']){
        $type = 'inconsistent';
        $result_mismatch_salesinvoices[] = $salesinvoiceid;
      }
    }

    if($type == 'ok')
      $result_ok_salesinvoices[] = $salesinvoiceid;

  }

  $data = [];
  foreach($result_removed_salesinvoices as $salesinvoiceid){
    $salesinvoice = salesinvoicedetail(null, [ 'id'=>$salesinvoiceid ]);
    $salesinvoice['type'] = 'removed';
    $salesinvoice['log_count'] = count($salesinvoices[$salesinvoiceid]);
    $data[] = $salesinvoice;
  }
  foreach($result_missing_salesinvoices as $salesinvoiceid){
    $salesinvoice = salesinvoicedetail(null, [ 'id'=>$salesinvoiceid ]);
    $salesinvoice['type'] = 'missing';
    $salesinvoice['log_count'] = count($salesinvoices[$salesinvoiceid]);
    $data[] = $salesinvoice;
  }
  foreach($result_mismatch_salesinvoices as $salesinvoiceid){
    $salesinvoice = salesinvoicedetail(null, [ 'id'=>$salesinvoiceid ]);
    $salesinvoice['type'] = 'inconsistent';
    $salesinvoice['log_count'] = count($salesinvoices[$salesinvoiceid]);
    $data[] = $salesinvoice;
  }
  foreach($result_ok_salesinvoices as $salesinvoiceid){
    $salesinvoice = salesinvoicedetail(null, [ 'id'=>$salesinvoiceid ]);
    $salesinvoice['type'] = 'ok';
    $salesinvoice['log_count'] = count($salesinvoices[$salesinvoiceid]);
    //$data[] = $salesinvoice;
  }

  $results = [
    'data'=>$data,
    'total'=>count($salesinvoices),
    'total_ok'=>count($result_ok_salesinvoices),
    'total_removed'=>count($result_removed_salesinvoices),
    'total_missing'=>count($result_missing_salesinvoices),
    'total_inconsistent'=>count($result_mismatch_salesinvoices),
  ];
  
  return $results;

}

function salesinvoice_getlogs($id){

  $logs = pmrs("select * from userlog where `action` in ('salesinvoiceentry', 'salesinvoicemodify', 'salesinvoiceremove')
        and refid = ? order by `timestamp`", [ $id ]);
  return $logs;

}

function salesinvoice_compare_with_logid($logid){

  $columns = [
    'status'=>[ 'datatype'=>'int' ],
    'isprint'=>[ 'datatype'=>'int' ],
    'isactive'=>[ 'datatype'=>'int' ],
    'code'=>[ 'datatype'=>'string' ],
    'date'=>[ 'datatype'=>'date' ],
    'customerid'=>[ 'datatype'=>'int' ],
    'address'=>[ 'datatype'=>'string' ],
    'pocode'=>[ 'datatype'=>'string' ],
    'creditterm'=>[ 'datatype'=>'int' ],
    'warehouseid'=>[ 'datatype'=>'int' ],
    'note'=>[ 'datatype'=>'string' ],
    'subtotal'=>[ 'datatype'=>'double' ],
    'discount'=>[ 'datatype'=>'double' ],
    'discountamount'=>[ 'datatype'=>'double' ],
    'taxable'=>[ 'datatype'=>'int' ],
    'taxamount'=>[ 'datatype'=>'double' ],
    'total'=>[ 'datatype'=>'double' ],
    'inventorysummary'=>[ 'datatype'=>'string' ],
    'paymentamount'=>[ 'datatype'=>'double' ],
    'paymentaccountid'=>[ 'datatype'=>'int' ],
    'ispaid'=>[ 'datatype'=>'int' ],
    'salesmanid'=>[ 'datatype'=>'int' ],
    'salesorderid'=>[ 'datatype'=>'int' ],
    'isreceipt'=>[ 'datatype'=>'int' ],
    'salesreceiptid'=>[ 'datatype'=>'int' ],
    'isgroup'=>[ 'datatype'=>'int' ],
    'salesinvoicegroupid'=>[ 'datatype'=>'int' ],
    'paymentdate'=>[ 'datatype'=>'date' ],
    'deliverycharge'=>[ 'datatype'=>'double' ],
    'isreconciled'=>[ 'datatype'=>'int' ],
    'inventories'=>[ 'datatype'=>'array_of_object' ],
//    'tax_code'=>[ 'datatype'=>'string' ],
//    'createdon'=>[ 'datatype'=>'datetime' ],
//    'createdby'=>[ 'datatype'=>'int' ],
//    'lastupdatedon'=>[ 'datatype'=>'datetime' ],
//    'id'=>[ 'datatype'=>'int' ],
//    'customerdescription'=>[ 'datatype'=>'string' ],
//    'salesmanname'=>[ 'datatype'=>'string' ],
//    'warehousename'=>[ 'datatype'=>'string' ],
//    'paymentaccountname'=>[ 'datatype'=>'string' ],
//    'senttime'=>[ 'datatype'=>'datetime' ],
//    'sentby'=>[ 'datatype'=>'string' ],
//    'issent'=>[ 'datatype'=>'int' ],
  ];

  $iv_columns = [
    'inventorycode'=>[ 'datatype'=>'string' ],
    'qty'=>[ 'datatype'=>'double' ],
    'unit'=>[ 'datatype'=>'string' ],
    'unitprice'=>[ 'datatype'=>'double' ],
  ];

  $salesinvoiceid = pmc("select refid from userlog where `id` = ?", [ $logid ]);
  if(!$salesinvoiceid) return [];

  $log = pmr("select `action`, `data1`, data2 from userlog where `id` = ?", [ $logid ]);
  $action = $log['action'];

  $skipped_columns = [];
  switch($action){
    case 'salesinvoiceentry':
      $skipped_columns = [ 'paymentdate', 'isactive', 'status' ];
      break;
  }

  $data = [];
  $data[] = unserialize($log['data1']);
  if(!empty(unserialize($log['data2']))) $data[] = unserialize($log['data2']);
  $data[] = salesinvoicedetail(null, [ 'id'=>$salesinvoiceid ]);

  $results = [];
  foreach($columns as $key=>$column){
    if(in_array($key, $skipped_columns)) continue;
    $row = [];
    $value = null;
    foreach($data as $index=>$obj){

      if(array_key_exists($key, $obj)){
        $current_value = ov($key, $obj);
        if($value === null) $value = $current_value;
        switch($column['datatype']){
          case 'date':
            $current_value = !$current_value ? '-' : date('j M Y', strtotime($current_value));
            break;
          case 'datetime':
            $current_value = !$current_value ? '-' : date('j M Y H:i:s', strtotime($current_value));
            break;
          case 'int':
            $current_value = intval($current_value);
            break;
        }
      }
      else
        $current_value = '-';

      $row[] = $current_value;
    }

    $matched = true;
    switch($action){
      case 'salesinvoicemodify':
        $index1 = count($data) - 2;
        $index2 = count($data) - 1;
        break;
      default:
        $index1 = 0;
        $index2 = count($data) - 1;
        break;
    }
    $current_value = array_key_exists($key, $data[$index1]) ? $data[$index1][$key] : null;
    $value = array_key_exists($key, $data[$index2]) ? $data[$index2][$key] : null;
    if($current_value !== null){
      switch($column['datatype']){
        case 'date':
          if(date('Ymd', strtotime($current_value)) != date('Ymd', strtotime($value))) $matched = false;
          break;
        case 'datetime':
          if(date('YmdHis', strtotime($current_value)) != date('YmdHis', strtotime($value))) $matched = false;
          break;
        case 'int':
          if(intval($current_value) != intval($value)) $matched = false;
          break;
        case 'double':
          if(doubleval($current_value) != doubleval($value)) $matched = false;
          break;
        case 'array_of_object':
          $iv_matched = true;
          if(count($current_value[$key]) != count($value[$key])){
            $iv_matched = false;
          }
          else{

          }
          $matched = $iv_matched;
          break;
        default:
          if($current_value != $value) $matched = false;
          break;
      }
    }

    $results[] = [
      'matched'=>$matched,
      'key'=>$key,
      'value'=>$row,
      'datatype'=>$column['datatype']
    ];
  }

  $last_is_matched = true;
  foreach($results as $obj)
    if(!$obj['matched']){
      $last_is_matched = false;
      break;
    }
  return [
    'matched'=>$last_is_matched,
    'data'=>$results
  ];

}

?>