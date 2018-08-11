<?php
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/salesinvoice.php';
require_once dirname(__FILE__) . '/salesreturn.php';
require_once dirname(__FILE__) . '/salesreceipt.php';

function salesinvoicegroup_uicolumns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'salesinvoicegrouplist_options'),
    array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>40, 'type'=>'html', 'align'=>'center', 'html'=>'salesinvoicegrouplist_ispaid'),
    array('active'=>1, 'name'=>'isreceipt', 'text'=>'Kwitansi', 'width'=>55, 'type'=>'html', 'align'=>'center', 'html'=>'salesinvoicegrouplist_isreceipt'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>90, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>70),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>150),
    array('active'=>0, 'name'=>'address', 'text'=>'Alamat', 'width'=>150),
    array('active'=>0, 'name'=>'note', 'text'=>'Catatan', 'width'=>150),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>90, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'paymentaccountid', 'text'=>'ID Akun Pembayaran', 'width'=>30, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'paymentaccountname', 'text'=>'Akun Pembayaran', 'width'=>90),
    array('active'=>0, 'name'=>'paymentdate', 'text'=>'Tgl Pembayaran', 'width'=>100, 'datatype'=>'date'),
    array('active'=>0, 'name'=>'paymentamount', 'text'=>'Pembayaran', 'width'=>90, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'salesreceiptid', 'text'=>'ID Kwitansi', 'width'=>30, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'salesinvoiceid', 'text'=>'ID Faktur', 'width'=>30, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'itemtype', 'text'=>'Tipe', 'width'=>40),
    array('active'=>1, 'name'=>'itemcode', 'text'=>'Kode Faktur', 'width'=>80, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'itemtotal', 'text'=>'Total Faktur', 'width'=>80, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'datetime'),
  );
  return $columns;

}
function salesinvoicegroupdetail($columns, $filters){

  $salesinvoicegroup = mysql_get_row('salesinvoicegroup', $filters, $columns);

  if($salesinvoicegroup){
    $items = pmrs("SELECT `type`, typeid,
      IF(`type` = 'SN', (SELECT code FROM salesreturn WHERE `id` = typeid), (SELECT code FROM salesinvoice WHERE `id` = typeid)) as code,
      IF(`type` = 'SN', (SELECT `date` FROM salesreturn WHERE `id` = typeid), (SELECT `date` FROM salesinvoice WHERE `id` = typeid)) as date,
      IF(`type` = 'SN', (SELECT customerdescription FROM salesreturn WHERE `id` = typeid), (SELECT customerdescription FROM salesinvoice WHERE `id` = typeid)) as customerdescription,
      IF(`type` = 'SN', (SELECT total FROM salesreturn WHERE `id` = typeid), (SELECT total FROM salesinvoice WHERE `id` = typeid)) as total,
      IF(`type` = 'SN', (SELECT ispaid FROM salesreturn WHERE `id` = typeid), (SELECT ispaid FROM salesinvoice WHERE `id` = typeid)) as ispaid,
      IF(`type` = 'SN', (SELECT returnamount FROM salesreturn WHERE `id` = typeid), (SELECT paymentamount FROM salesinvoice WHERE `id` = typeid)) as paymentamount,
      IF(`type` = 'SN', FALSE, (SELECT taxable FROM salesinvoice WHERE `id` = typeid)) as taxable
      FROM salesinvoicegroupitem WHERE salesinvoicegroupid = ?", array($salesinvoicegroup['id']));

    $total = 0;
    foreach($items as $index=>$item){
      $items[$index]['total'] = floor($item['total']);
      $total += floor($item['total']);
    }
    $salesinvoicegroup['items'] = $items;
    $salesinvoicegroup['total'] = floor($total);
    pm("update salesinvoicegroup set total = ? where `id` = ?", [ $total, $salesinvoicegroup['id'] ]);
    $paymentaccount = chartofaccountdetail(null, array('id'=>$salesinvoicegroup['paymentaccountid']));
    $salesinvoicegroup['paymentaccountname'] = isset($paymentaccount['name']) ? $paymentaccount['name'] : '';
  }

  return $salesinvoicegroup;
  
}
function salesinvoicegrouplist($columns, $sorts = null, $filters = null, $groups = null, $limitoffset = null){

  $columnaliases = array(
    'code'=>'t1.code',
    'date'=>'t1.date',
    'customerdescription'=>'t1.customerdescription',
    'total'=>'t1.total',
    'invoicecode'=>'t2.code as invoicecode',
    'invoicetotal'=>'t2.total as invoicetotal',
    'invoiceispaid'=>'t2.ispaid as invoiceispaid',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id'));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $wherequery = "WHERE t1.id = t2.salesinvoicegroupid" . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM salesinvoicegroup t1, salesinvoice t2 $wherequery $sortquery $limitquery";
  $salesinvoicegroups = pmrs($query, $params);

  return $salesinvoicegroups;

}
function salesinvoicegroup_customerhint($hint){

  $query = "SELECT `id`, code, `date`, customerid, customerdescription, total, ispaid, paymentamount FROM salesinvoice
    WHERE customerdescription LIKE ? AND (isgroup is null || isgroup != 1) AND ispaid != 1 GROUP BY code ORDER BY `date`, `id`";
  $rows = pmrs($query, array("%$hint%"));

  $results = array();
  if(is_array($rows) && count($rows) > 0){
    $rows = array_index($rows, array('customerid'));

    // Load address from customer
    $customerids = array();
    foreach($rows as $customerid=>$arr)
      $customerids[] = $customerid;
    if(count($customerids) > 0){
      $customers = pmrs("SELECT `id`, description, address FROM customer WHERE `id` IN (" . implode(', ', $customerids) . ")");
      $customers = array_index($customers, array('id'), 1);
    }

    foreach($rows as $customerid=>$arr){
      if(count($arr) == 0) continue;

      $customer = isset($customers[$customerid]) ? $customers[$customerid] : array();
      $results[] = array(
        'customerid'=>$customerid,
        'customerdescription'=>ov('description', $customer),
        'address'=>ov('address', $customer),
        'salesinvoices'=>$arr
      );
    }
  }
  return $results;

}
function salesinvoicegroupcode(){

  $prefix = systemvarget('salesinvoicegroupprefix', 'FP');
  $prefix_plus_year = $prefix . '/' . date('y') . '/';

  $query = "SELECT code FROM salesinvoicegroup WHERE code LIKE ?";
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
function salesinvoicegroup_ispaid($id, $ispaid, $paymentdate, $paymentaccountid){

  $current = salesinvoicegroupdetail(null, array('id'=>$id));
  $items = $current['items'];
  if(is_debugmode()) console_log($items);
  for($i = 0 ; $i < count($items) ; $i++){
    $item = $items[$i];
    if($item['type'] == 'SI'){
      $salesinvoice = salesinvoicedetail(null, array('id'=>$item['typeid']));
      salesinvoicemodify(array(
        'id'=>$salesinvoice['id'],
        'paymentamount'=>$ispaid ? $salesinvoice['total'] : 0,
        'paymentdate'=>$paymentdate,
        'paymentaccountid'=>$paymentaccountid
      ));
    }
  }

  pm("UPDATE salesinvoicegroup SET ispaid = ?, paymentdate = ?, paymentaccountid = ? WHERE `id` = ?",
    array($ispaid, $paymentdate, $paymentaccountid, $id));

}

function salesinvoicegroupentry($salesinvoicegroup){

  $lock_file = __DIR__ . "/../usr/system/salesinvoicegroup_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

	/*
	code:string
	date:date
	customerdescription:string
	address:string
	note:string
	total:double
	ispaid:boolean
	paymentamount:double
	paymentaccountid:int
	paymentdate:date
	items:
		type:string
		typeid:int
		ispaid:int
		paymentamount:double	
	*/

  // Parameter extraction
  $code = ov('code', $salesinvoicegroup);
  $date = ov('date', $salesinvoicegroup);
  $customerdescription = ov('customerdescription', $salesinvoicegroup);
  $address = ov('address', $salesinvoicegroup);
  $items = ov('items', $salesinvoicegroup);
  $note = ov('note', $salesinvoicegroup);
  $total = ov('total', $salesinvoicegroup);
  $ispaid = ov('ispaid', $salesinvoicegroup);
  $paymentaccountid = ov('paymentaccountid', $salesinvoicegroup);
  $paymentamount = ov('paymentamount', $salesinvoicegroup);
  $paymentdate = ov('paymentdate', $salesinvoicegroup);
  $createdon = $lastupdatedon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  // Validation
  if(empty($code)) throw new Exception('Kode grup faktur harus diisi.');
  if(empty($customerdescription)) throw new Exception('Nama pelanggan harus diisi.');
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(pmc("SELECT COUNT(*) FROM salesinvoicegroup WHERE code = ?", array($code)) > 0) throw new Exception('Kode grup faktur sudah ada.');
  if(count($items) == 0) throw new Exception('Faktur/retur harus diisi.');
  $taxable = null;
  foreach($items as $item){
    $type = ov('type', $item, 1);
    $typeid = ov('typeid', $item);
    switch($type){
      case 'SI':
        $salesinvoice = pmr("SELECT customerid, customerdescription, taxable FROM salesinvoice WHERE `id` = ?", [ $typeid ]);
        if(!$salesinvoice) throw new Exception('Faktur yang dimasukkan salah.');

        /*$is_genki = strpos(strtolower($salesinvoice['customerdescription']), 'genki') !== false ||
          strpos(strtolower($salesinvoice['customerdescription']), 'aeon') !== false ||
          strpos(strtolower($salesinvoice['customerdescription']), 'suncity') !== false ||
          strpos(strtolower($salesinvoice['customerdescription']), 'inti idola') !== false ||
          strpos(strtolower($salesinvoice['customerdescription']), 'fishman') !== false;*/

        $combinable = pmc("select salesinvoicegroup_combinable from customer where `id` = ?", [ $salesinvoice['customerid'] ]);
        if(!$combinable){
          if($taxable === null) $taxable = $salesinvoice['taxable'];
          else if($taxable != $salesinvoice['taxable']) exc('Tidak bisa menggabungkan faktur pajak dan non pajak dalam 1 (satu) group faktur.');
        }
        break;
      case 'SN':
        if(pmc("SELECT COUNT(*) FROM salesreturn WHERE `id` = ?", array($typeid)) <= 0) throw new Exception('Retur yang dimasukkan salah.');
        break;
      default:
        throw new Exception('Kesalahan pada faktur/retur yang dimasukkan, silakan diulang kembali.');
        break;
    }
  }
  if($ispaid && ($paymentaccountid == 999 || !chartofaccountdetail(null, array('id'=>$paymentaccountid))))
    throw new Exception('Akun pembayaran belum diisi.');
  if($total <= 0) throw new Exception('Total tidak dapat berisi nilai minus.');
  if(!$paymentaccountid) $paymentaccountid = chartofaccountdetail(null, array('code'=>'000.00'))['id'];

 	// Insert to salesinvoicegroup
  $query = "INSERT INTO salesinvoicegroup(code, `date`, customerdescription, address, note, total, paymentaccountid, paymentamount, paymentdate, ispaid, isreceipt,
    createdon, lastupdatedon, createdby) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($code, $date, $customerdescription, $address, $note, $total, $paymentaccountid, $paymentamount, $paymentdate, $ispaid, $isreceipt, $createdon,
    $lastupdatedon, $createdby));

  try{
    $queries = $params = $salesinvoices = array();
    for($i = 0 ; $i < count($items) ; $i++){
      $item = $items[$i];
      $type = $item['type'];
      $typeid = $item['typeid'];

      $queries[] = "(?, ?, ?)";
      array_push($params, $id, $type, $typeid);

      switch($type){
        case 'SI':
          $salesinvoices[] = array(
            'id'=>$typeid,
            'isgroup'=>1,
            'salesinvoicegroupid'=>$id,
            'paymentamount'=>$item['paymentamount'],
            'paymentaccountid'=>$paymentaccountid,
            'paymentdate'=>$paymentdate
          );
          break;
      }
    }
    pm("INSERT INTO salesinvoicegroupitem (`salesinvoicegroupid`, `type`, `typeid`) VALUES " . implode(', ', $queries), $params);

    foreach($salesinvoices as $salesinvoice)
      salesinvoicemodify($salesinvoice);

    userlog('salesinvoicegroupentry', $salesinvoicegroup, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

    return array('id'=>$id);

  }
  catch(Exception $ex){

		salesinvoicegroupremove(array('id'=>$id));

    fclose($fp);
    unlink($lock_file);

    throw $ex;

  }

}
function salesinvoicegroupmodify($salesinvoicegroup){

  // Parameter extraction
  $id = ov('id', $salesinvoicegroup);
  $current = salesinvoicegroupdetail(null, array('id'=>$id));

  // Validation
  if(isset($salesinvoicegroup['items']) && count($salesinvoicegroup['items']) == 0) throw new Exception('Faktur/retur harus diisi.');
  if(isset($salesinvoicegroup['total']) && $salesinvoicegroup['total'] <= 0) throw new Exception('Total tidak dapat berisi nilai minus.');

  $lock_file = __DIR__ . "/../usr/system/salesinvoicegroup_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  // Get updated cols & update database
  $updatedcols = array();
  // - Date
  if(isset($salesinvoicegroup['date']) && $salesinvoicegroup['date'] != date('Ymd', strtotime($current['date']))){
    if(!isdate($salesinvoicegroup['date'])) exc('Format tanggal salah');
    $updatedcols['date'] = $salesinvoicegroup['date'];
  }
  // - Customer description
  if(isset($salesinvoicegroup['customerdescription']) && $salesinvoicegroup['customerdescription'] != $current['customerdescription'])
    $updatedcols['customerdescription'] = $salesinvoicegroup['customerdescription'];
  // - Address
  if(isset($salesinvoicegroup['address']) && $salesinvoicegroup['address'] != $current['address'])
    $updatedcols['address'] = $salesinvoicegroup['address'];
  // - Note
  if(isset($salesinvoicegroup['note']) && $salesinvoicegroup['note'] != $current['note'])
    $updatedcols['note'] = $salesinvoicegroup['note'];
  // - Ispaid
  if(isset($salesinvoicegroup['ispaid']) && $salesinvoicegroup['ispaid'] != $current['ispaid'])
    $updatedcols['ispaid'] = $salesinvoicegroup['ispaid'];
  // - Total
  if(isset($salesinvoicegroup['total']) && $salesinvoicegroup['total'] != $current['total'])
    $updatedcols['total'] = $salesinvoicegroup['total'];
  // - Payment amount
  if(isset($salesinvoicegroup['paymentamount']) && $salesinvoicegroup['paymentamount'] != $current['paymentamount'])
    $updatedcols['paymentamount'] = $salesinvoicegroup['paymentamount'];

  if(isset($salesinvoicegroup['paymentdate']) && $salesinvoicegroup['paymentdate'] != date('Ymd', strtotime($current['paymentdate'])))
    $updatedcols['paymentdate'] = $salesinvoicegroup['paymentdate'];

  if(isset($salesinvoicegroup['paymentaccountid']) && $salesinvoicegroup['paymentaccountid'] != $current['paymentaccountid'])
    $updatedcols['paymentaccountid'] = $salesinvoicegroup['paymentaccountid'] ? $salesinvoicegroup['paymentaccountid'] : chartofaccountdetail(null, array('code'=>'000.00'))['id'];

  if(count($updatedcols) > 0){
    mysql_update_row('salesinvoicegroup', $updatedcols, array('id'=>$id));
  }

  // Items update handler
  if(isset($salesinvoicegroup['items']) && is_array($salesinvoicegroup['items'])){

    $current = salesinvoicegroupdetail(null, array('id'=>$id));
    $items = $salesinvoicegroup['items'];

    $queries = $params = $salesinvoices = array();
    for($i = 0 ; $i < count($items) ; $i++){
      $item = $items[$i];
      $type = $item['type'];
      $typeid = $item['typeid'];

      $queries[] = "(?, ?, ?)";
      array_push($params, $id, $type, $typeid);

      switch($type){
        case 'SI':
          $salesinvoices[] = array(
              'id'=>$typeid,
              'isgroup'=>1,
              'salesinvoicegroupid'=>$id,
              'paymentamount'=>$item['paymentamount'],
              'paymentaccountid'=>$current['paymentaccountid'],
              'paymentdate'=>$current['paymentdate']
          );
          break;
      }
    }
    pm("DELETE FROM salesinvoicegroupitem WHERE salesinvoicegroupid = ?", array($id));
    pm("INSERT INTO salesinvoicegroupitem (`salesinvoicegroupid`, `type`, `typeid`) VALUES " . implode(', ', $queries), $params);

    salesinvoice_salesinvoicegroup_clear($id);

    foreach($salesinvoices as $salesinvoice)
      salesinvoicemodify($salesinvoice);

    $updatedcols['items'] = $salesinvoicegroup['items'];
  }

  userlog('salesinvoicegroupmodify', $current, $updatedcols, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  global $_REQUIRE_WORKER;
  $_REQUIRE_WORKER = true;

  return array('id'=>$id);

}
function salesinvoicegroupremove($filters){

  if(is_debugmode()) console_warn("Removing salesinvoicegroup...");
  if(is_debugmode()) console_warn("Input:");
  if(is_debugmode()) console_log($filters);

  $salesinvoicegroup = salesinvoicegroupdetail(null, $filters);

  if(!$salesinvoicegroup) exc('Grup faktur tidak terdaftar.');

  $id = $salesinvoicegroup['id'];

  if($salesinvoicegroup['isreceipt']) throw new Exception('Tidak dapat menhapus, sudah ada kuitansi');

  $lock_file = __DIR__ . "/../usr/system/salesinvoicegroup_remove_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

  pm("UPDATE salesinvoice SET isgroup = 0, salesinvoicegroupid = null WHERE salesinvoicegroupid = ?", array($id));
  pm("UPDATE salesreturn SET isgroup = 0, salesinvoicegroupid = null WHERE salesinvoicegroupid = ?", array($id));
  pm("DELETE FROM salesinvoicegroup WHERE `id` = ?", array($id));

  userlog('salesinvoicegroupremove', $salesinvoicegroup, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

}

function salesinvoicegroup_salesreceiptentry($salesinvoicegroupids){

  $items = array();
  $total = 0;
  for($i = 0 ; $i < count($salesinvoicegroupids) ; $i++){
    $salesinvoicegroup = salesinvoicegroupdetail(null, array('id'=>$salesinvoicegroupids[$i]));
    if($salesinvoicegroup['isreceipt']) throw new Exception('Grup faktur dengan kode ' . $salesinvoicegroup['code'] . ' sudah ada kwitansi.');
    $total += $salesinvoicegroup['total'];
    $items[] = array(
      'ref'=>'IG',
      'refid'=>$salesinvoicegroup['id'],
      'date'=>$salesinvoicegroup['date'],
      'code'=>$salesinvoicegroup['code'],
      'total'=>$salesinvoicegroup['total']
    );
  }
  $salesreceipt = array(
    'code'=>salesreceiptcode(),
    'date'=>date('Ymd'),
    'customerdescription'=>$salesinvoicegroup['customerdescription'],
    'address'=>$salesinvoicegroup['address'],
    'items'=>$items,
    'total'=>$total
  );
  return $salesreceipt;

}
function salesinvoicegroup_salesinvoicemodify($id){

  $current = salesinvoicegroupdetail(null, array('id'=>$id));

  $salesinvoices = pmrs("SELECT * FROM salesinvoice WHERE salesinvoicegroupid = ?", array($id));
  $total = 0;
  $paymentamount = 0;
  $paymentaccountid = null;
  $paymentdate = null;
  for($i = 0 ; $i < count($salesinvoices) ; $i++){
    $salesinvoice = $salesinvoices[$i];
    $salesinvoice_total = $salesinvoice['total'];
    $salesinvoice_paymentamount = $salesinvoice['paymentamount'];
    $salesinvoice_paymentaccountid = $salesinvoice['paymentaccountid'];
    $salesinvoice_paymentdate = $salesinvoice['paymentdate'];

    $total += $salesinvoice_total;
    $paymentamount += $salesinvoice_paymentamount;
    $paymentaccountid = $salesinvoice_paymentaccountid;
    $paymentdate = $salesinvoice_paymentdate;
  }

  $ispaid = $paymentamount >= $total ? 1 : ($paymentamount > 0 ? 2 : 0);

  pm("UPDATE salesinvoicegroup SET total = ?, paymentamount = ?, ispaid = ?, paymentaccountid = ?, paymentdate = ? WHERE `id` = ?",
      array($total, $paymentamount, $ispaid, $paymentaccountid, $paymentdate, $id));

}
function salesinvoicegroup_recreateitems(){

  pm("DELETE FROM salesinvoicegroupitem;");

  $salesinvoicegroups = pmrs("SELECT `id` FROM salesinvoicegroup");
  $salesinvoiceids = array();
  foreach($salesinvoicegroups as $salesinvoicegroup)
    $salesinvoiceids[] = $salesinvoicegroup['id'];

  $salesinvoices = pmrs("SELECT `id`, salesinvoicegroupid FROM salesinvoice WHERE salesinvoicegroupid IN (" . implode(', ', $salesinvoiceids) . ")");
  $salesinvoices = array_index($salesinvoices, array('salesinvoicegroupid'));

  $queries = array();
  foreach($salesinvoicegroups as $salesinvoicegroup){
    $id = $salesinvoicegroup['id'];
    if(isset($salesinvoices[$id])){
      foreach($salesinvoices[$id] as $salesinvoice){
        $salesinvoiceid = $salesinvoice['id'];
        $queries[] = "INSERT INTO salesinvoicegroupitem (salesinvoicegroupid, `type`, typeid) VALUES ($id, 'SI', $salesinvoiceid)";
      }
    }
  }
  mysqli_exec_multiples($queries);
  //pm("INSERT INTO salesinvoicegroupitem (salesinvoicegroupid, `type`, typeid) VALUES " . implode(', ', $queries), $params);

  console_warn('Total salesinvoicegroup: ' . count($salesinvoicegroups));
  console_warn('Total salesinvoice: ' . count($salesinvoices));

}

?>