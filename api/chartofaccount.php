<?php
require_once dirname(__FILE__) . '/currency.php';

$chartofaccounts = pmrs("SELECT `id`, `code`, `name` FROM chartofaccount;");
$chartofaccounts_indexbyid = array_index($chartofaccounts, array('id'), 1);

function chartofaccount_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>0, 'name'=>'moved', 'text'=>'Pindah', 'width'=>40, 'type'=>'html', 'html'=>'chartofaccountlist_moved'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>1, 'name'=>'name', 'text'=>'Nama Akun', 'width'=>300),
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>1, 'name'=>'amount', 'text'=>'Saldo', 'width'=>150, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'accounttype', 'text'=>'Tipe Akun', 'width'=>80),
    array('active'=>0, 'name'=>'currencyid', 'text'=>'ID Mata Uang', 'width'=>30),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function chartofaccountdetail($columns, $filters){
 
  if($columns == null) $columns = array('*');
  $chartofaccount = mysql_get_row('chartofaccount', $filters, array('*'));

  if($chartofaccount){
    $currency = currencydetail(null, array('id'=>$chartofaccount['currencyid']));
    $chartofaccount['currencycode'] = $currency['code'];
    $chartofaccount['currencyname'] = $currency['name'];
  }

  return $chartofaccount;

}
function chartofaccountlist($columns = null, $sorts = null, $filters = null, $limitoffset = null){

  // DB column aliases
  $columnaliases = array(
    'id'=>'t1.id!',
    'code'=>'t1.code',
    'name'=>'t1.name',
    'type'=>'t1.type',
    'currencyid'=>'t1.currencyid',
    'amount'=>'t1.amount',
    'createdon'=>'t1.createdon',
    'createdby'=>'t1.createdby',
    'accounttype'=>'t1.accounttype'
  );

  // Retrieve inventories from db
  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases, array('t1.id', 't1.name', 't1.type'));
  $wherequery = wherequery_from_filters($params, $filters, $columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limitoffset);
  $query = "SELECT $columnquery FROM chartofaccount t1 $wherequery $sortquery $limitquery";
  $chartofaccounts = pmrs($query, $params);

  if(is_array($chartofaccounts)){
    $currencies = currencylist(null, null);
    $currencies = array_index($currencies, array('id'), 1);

    for($i = 0 ; $i < count($chartofaccounts) ; $i++){
      $currency = $currencies[$chartofaccounts[$i]['currencyid']][0];
      $chartofaccounts[$i]['currencycode'] = $currency['code'];
      $chartofaccounts[$i]['currencyname'] = $currency['name'];
      $chartofaccounts[$i]['amount'] = $chartofaccounts[$i]['type'] == 'C' && $chartofaccounts[$i]['amount'] < 0 ? abs($chartofaccounts[$i]['amount']) : $chartofaccounts[$i]['amount'];
    }
  }

  return $chartofaccounts;

}
function chartofaccountlist2($columns, $filters){

  $chartofaccounts = mysql_get_rows('chartofaccount', $columns, $filters);

  if(is_array($chartofaccounts)){
    $currencies = currencylist(null, null);
    $currencies = array_index($currencies, array('id'), 1);

    for($i = 0 ; $i < count($chartofaccounts) ; $i++){
      $currency = $currencies[$chartofaccounts[$i]['currencyid']][0];
      $chartofaccounts[$i]['currencycode'] = $currency['code'];
      $chartofaccounts[$i]['currencyname'] = $currency['name'];
      $chartofaccounts[$i]['amount'] = $chartofaccounts[$i]['type'] == 'C' && $chartofaccounts[$i]['amount'] < 0 ? abs($chartofaccounts[$i]['amount']) : $chartofaccounts[$i]['amount'];
    }
  }

  return $chartofaccounts;

}
function chartofaccount_accounttype(){

  $arr = array(
      array('text'=>lang('coa03'), 'value'=>'asset'),
      array('text'=>lang('coa04'), 'value'=>'expense'),
      array('text'=>lang('coa05'), 'value'=>'other')
  );
  return $arr;

}
function chartofaccount_type(){

  return array(
      array('value'=>'D', 'text'=>lang('coa01')),
      array('value'=>'C', 'text'=>lang('coa02'))
  );

}
function chartofaccountmutation($filters, $sorts = null){

  $columns = [
    'id'=>'p2.id',
    'date'=>'p1.date',
    'description'=>'p1.description',
    'credit'=>'SUM(p2.credit)',
    'debit'=>'SUM(p2.debit)',
    'type'=>'p2.type',
    'ref'=>'p1.ref',
    'refid'=>'p1.refid',
    'createdon'=>'p1.createdon',
    'createdby'=>'p1.createdby',
    'p2id'=>'p2.id',
    'coaid'=>'p2.coaid',
  ];

  $filter_names = [];
  foreach($filters as $filter)
    $filter_names[$filter['name']] = 1;
  $filter_names = array_keys($filter_names);

  $params = [];
  $columnquery = columnquery_from_columnaliases(true, $columns);
  $wherequery = wherequery_from_filters($params, $filters, $columns);
  $wherequery = str_replace('WHERE', 'AND', $wherequery);
  $sortquery = sortquery_from_sorts($sorts, $columns);

  // Mutation from date
  $query = "SELECT $columnquery
    FROM journalvoucher p1, journalvoucherdetail p2
    WHERE p1.id = p2.jvid $wherequery
    GROUP BY p1.id $sortquery";
  $rows = pmrs($query, $params);

  if(!$rows) $rows = array();

  // Resolve description into more readable text
  if(count($rows) > 0){

    $purchaseinvoiceids = [];
    $purchaseinvoices = [];
    foreach($rows as $row)
      if($row['ref'] == 'PI') $purchaseinvoiceids[] = $row['refid'];
    if(count($purchaseinvoiceids) > 0){
      $purchaseinvoices = pmrs("select `id`, concat(`code`, ' - ', supplierdescription) as description from purchaseinvoice where `id` in (" . implode(', ', $purchaseinvoiceids) . ")");
      $purchaseinvoices = array_index($purchaseinvoices, [ 'id' ], 1);
    }

    foreach($rows as $index=>$row){
      if($row['ref'] == 'PI' && isset($purchaseinvoices[$row['refid']]))
        $rows[$index]['description'] = $purchaseinvoices[$row['refid']]['description'];
    }

  }

  // Opening balance and balance only calculated on below criteria
  if(count($rows) > 0 &&
    count($filter_names) == 2 && in_array('coaid', $filter_names) && in_array('date', $filter_names) &&
    is_array($sorts) && count($sorts) == 1 && $sorts[0]['name'] == 'id'){

    $sorttype = $sorts[0]['sorttype'];

    switch($sorttype){
      case 'asc':

        $startdate = $rows[0]['date'];

        // Opening balance before date
        $query = "SELECT SUM(p2.debit - p2.credit) as openingvalue FROM journalvoucher p1, journalvoucherdetail p2
          WHERE p1.id = p2.jvid AND p2.coaid = ? AND p1.date < ? ORDER BY p1.date";
        $openingvalue = floatval(pmc($query, array($rows[0]['coaid'], $startdate)));

        // Add opening balance to first row
        array_splice($rows, 0, 0, [
          [
          'id'=>0,
          'date'=>date('Y-m-d', strtotime($startdate)),
          'description'=>'Saldo Awal',
          'debit'=>0,
          'credit'=>'0',
          'type'=>'',
          'ref'=>'',
          'refid'=>0,
          'balance'=>$openingvalue
          ]
        ]);

        // Add end balance after each row
        $balance = $openingvalue;
        for($i = 1 ; $i < count($rows) ; $i++){
          $row = $rows[$i];
          $debit = $row['debit'];
          $credit = $row['credit'];
          $balance += $debit > 0 ? $debit : $credit * -1;
          $rows[$i]['balance'] = $balance;
        }

        break;
      case 'desc':

        $startdate = $rows[count($rows) - 1]['date'];

        // Opening balance before date
        $query = "SELECT SUM(p2.debit - p2.credit) as openingvalue FROM journalvoucher p1, journalvoucherdetail p2
          WHERE p1.id = p2.jvid AND p2.coaid = ? AND p1.date < ?";
        $openingvalue = floatval(pmc($query, array($rows[0]['coaid'], $startdate)));

        // Add opening balance to first row
        array_push($rows, array(
          'id'=>0,
          'date'=>date('Y-m-d', strtotime($startdate)),
          'description'=>'Saldo Awal',
          'debit'=>0,
          'credit'=>'0',
          'type'=>'',
          'ref'=>'',
          'refid'=>0,
          'balance'=>$openingvalue
        ));

        // Add end balance after each row
        $balance = $openingvalue;
        for($i = count($rows) - 1 ; $i >= 0 ; $i--){
          $row = $rows[$i];
          $debit = $row['debit'];
          $credit = $row['credit'];
          $balance += $debit > 0 ? $debit : $credit * -1;
          $rows[$i]['balance'] = $balance;
        }

        break;
    }

  }





  return $rows;

}
function chartofaccount_id_exists($id){
  $count = pmc("select count(*) from chartofaccount where `id` = ?", [ $id ]);
  return $count > 0 ? true : false;
}

function chartofaccountentry($chartofaccount){

  $code = ov('code', $chartofaccount);
  $name = ov('name', $chartofaccount);
  $accounttype = ov('accounttype', $chartofaccount, 0);
  $type = ov('type', $chartofaccount, 0, 'D');
  $currencyid = ov('currencyid', $chartofaccount);

  if(empty($code)) throw new Exception(excmsg('coa01'));
  if(chartofaccountdetail(null, array('code'=>$code))) throw new Exception(excmsg('coa03'));
  if(empty($name)) throw new Exception(excmsg('coa02'));
  if(chartofaccountdetail(null, array('name'=>$name))) throw new Exception(excmsg('coa04'));
  if(empty($type)) throw new Exception(excmsg('coa07'));
  if(empty($accounttype)) throw new Exception(excmsg('coa08'));
  if(!$currencyid) throw new Exception(excmsg('coa09'));

  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  $lock_file = __DIR__ . "/../usr/system/chartofaccount_entry.lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $query = "INSERT INTO chartofaccount(`code`, `name`, `type`, accounttype, currencyid, amount, createdon, createdby) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
  $id = pmi($query, array($code, $name,  $type, $accounttype, $currencyid, 0, $createdon, $createdby));

  userlog('chartofaccountentry', $chartofaccount, '', $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;
  
}
function chartofaccountmodify($chartofaccount){

  $id = ov('id', $chartofaccount, 1);
  $current_chartofaccount = chartofaccountdetail(null, array('id'=>$id));

  if(!$current_chartofaccount) throw new Exception('Terdapat kesalahan, tidak dapat mengubah akun ini.');

  $lock_file = __DIR__ . "/../usr/system/chartofaccount_modify_" . $id . ".lock";
  $fp = fopen($lock_file, 'w+');
  if(!flock($fp, LOCK_EX)) exc('Tidak dapat menyimpan, silakan ulangi beberapa saat lagi.');

  $updatedrow = array();
  if(isset($chartofaccount['code']) && $current_chartofaccount['code'] != $chartofaccount['code']){
    if(empty($chartofaccount['code'])) throw new Exception(excmsg('coa01'));
    if(chartofaccountdetail(null, array('code'=>$chartofaccount['code']))) throw new Exception(excmsg('coa03'));
    $updatedrow['code'] = $chartofaccount['code'];
  }

  if(isset($chartofaccount['name']) && $current_chartofaccount['name'] != $chartofaccount['name']){
    if(empty($chartofaccount['name'])) throw new Exception(excmsg('coa02'));
    if(chartofaccountdetail(null, array('name'=>$chartofaccount['name']))) throw new Exception(excmsg('coa04'));
    $updatedrow['name'] = $chartofaccount['name'];
  }

  if(isset($chartofaccount['accounttype']) && $current_chartofaccount['accounttype'] != $chartofaccount['accounttype'])
    $updatedrow['accounttype'] = $chartofaccount['accounttype'];

  if(count($updatedrow) > 0)
    mysql_update_row('chartofaccount', $updatedrow, array('id'=>$id));

  userlog('chartofaccountmodify', $current_chartofaccount, $updatedrow, $_SESSION['user']['id'], $id);

  fclose($fp);
  unlink($lock_file);

  $result = array('id'=>$id);
  return $result;
  
}
function chartofaccountremove($filters){

  if(isset($filters['id'])){
    $id = ov('id', $filters);

    $current_chartofaccount = chartofaccountdetail(null, array('id'=>$id));
    if(!$current_chartofaccount) exc('Akun tidak terdaftar.');

    $lock_file = __DIR__ . "/../usr/system/chartofaccount_remove_" . $id . ".lock";
    $fp = fopen($lock_file, 'w+');
    if(!flock($fp, LOCK_EX)) exc('Tidak dapat menghapus, silakan ulangi beberapa saat lagi.');

    $query = "DELETE FROM chartofaccount WHERE `id` = ?";
    pm($query, array($id));

    userlog('chartofaccountremove', $current_chartofaccount, '', $_SESSION['user']['id'], $id);

    fclose($fp);
    unlink($lock_file);

  }

}

function chartofaccountrecalculate($id){

  $totalamount = pmc("select SUM(t2.debit) - SUM(t2.credit) from journalvoucher t1, journalvoucherdetail t2 
    where t1.id = t2.jvid and t1.date <= ? and t2.coaid = ? group by t2.coaid", [ date('Ymd'), $id ]);
  $query = "UPDATE chartofaccount SET amount = ? WHERE `id` = ?";
  pm($query, array($totalamount, $id));

}
function chartofaccountrecalculateall(){

	$chartofaccounts = pmrs("
    select 
      `id` as coaid, 
      (select SUM(t2.debit) - SUM(t2.credit) from journalvoucher t1, journalvoucherdetail t2 where t1.id = t2.jvid and t1.date <= ? and t2.coaid = x1.id group by t2.coaid) as balance
    from 
      chartofaccount x1
    ",
    [ date('Ymd') ]
  );
	if(is_array($chartofaccounts)){
		$queries = $params = [];
		foreach($chartofaccounts as $chartofaccount){
			$coaid = $chartofaccount['coaid'];
			$balance = intval($chartofaccount['balance']);
			$queries[] = "UPDATE chartofaccount SET amount = $balance WHERE id = $coaid";
			array_push($params, $balance, $coaid);
		}
		if(count($queries) > 0) pm(implode(';', $queries), $params);
	}

}

?>