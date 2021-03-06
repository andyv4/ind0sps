<?php
require_once dirname(__FILE__) . '/chartofaccount.php';
require_once dirname(__FILE__) . '/job.php';

function journalvoucher_ui_columns(){

  $columns = array(
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>0, 'name'=>'journaltype', 'text'=>'M/A', 'width'=>30),
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'description', 'text'=>'Nama', 'width'=>200),
    array('active'=>0, 'name'=>'ref', 'text'=>'Ref', 'width'=>30),
    array('active'=>0, 'name'=>'refid', 'text'=>'ID Ref', 'width'=>30),
    array('active'=>1, 'name'=>'amount', 'text'=>'Total', 'width'=>100, 'datatype'=>'money'),
    array('active'=>0, 'name'=>'coaid', 'text'=>'ID Ref', 'width'=>30),
    array('active'=>1, 'name'=>'coaname', 'text'=>'Nama Akun', 'width'=>150),
    array('active'=>1, 'name'=>'debit', 'text'=>'Debit', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'credit', 'text'=>'Kredit', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'type', 'text'=>'D/K', 'width'=>30),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date'),
  );
  return $columns;

}
function journalvoucherdetail($columns, $filters){

  $journalvoucher = mysql_get_row('journalvoucher', $filters, $columns);

  if($journalvoucher){
    $id = $journalvoucher['id'];
    $details = array();
    $query = "SELECT * FROM journalvoucherdetail WHERE jvid = ?";
    $rows = pmrs($query, array($id));
    for($i = 0 ; $i < count($rows) ; $i++){
      $row = $rows[$i];
      $coaid = $row['coaid'];
      $coa = chartofaccountdetail(null, array('id'=>$coaid));
      $coaname = $coa['name'];
      $debitamount = $row['debit'];
      $creditamount = $row['credit'];

      $details[] = array(
        'coaid'=>$coaid,
        'coaname'=>$coaname,
        'debitamount'=>$debitamount,
        'creditamount'=>$creditamount
      );
    }
    $journalvoucher['details'] = $details;
  }

  return $journalvoucher;

}
function journalvoucherexists($filters){

  $obj = mysql_get_row('journalvoucher', $filters, array('id'));
  if($obj != null) return $obj['id'];
  return 0;

}
function journalvoucherlist($columns = null, $sorts = null, $filters = null, $limits = null){

  $journalvoucher_columnaliases = array(
    'id'=>'t1.id',
    'journaltype'=>'t1.type',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'ref'=>'t1.ref',
    'refid'=>'t1.refid',
    'amount'=>'t1.amount',
    'coaid'=>'t2.coaid',
    'coaname'=>'t3.name',
    'debit'=>'t2.debit',
    'credit'=>'t2.credit',
    'type'=>'t2.type',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $journalvoucher_columnaliases);
  $wherequery = 'WHERE t1.id = t2.jvid AND t2.coaid = t3.id ' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $journalvoucher_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $journalvoucher_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'journalvoucher' as `type`, t1.id $columnquery
    FROM journalvoucher t1, journalvoucherdetail t2, chartofaccount t3 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);

  return $data;

}

function journalvoucherentryormodify($obj){

  if(($id = journalvoucherexists(array('ref'=>$obj['ref'], 'refid'=>$obj['refid'])))){
    $obj['id'] = $id;
    journalvouchermodify($obj);
  }
  else{
    journalvoucherentry($obj);
  }

}
function journalvoucherentries($journalvouchers){

  /*
   * Validation
   */
  global $chartofaccounts_indexbyid;
  for($i = 0 ; $i < count($journalvouchers) ; $i++){
    $journalvoucher = $journalvouchers[$i];
    $date = ov('date', $journalvoucher);
    ov('description', $journalvoucher, 1, array('notempty'=>1));
    $details = ov('details', $journalvoucher, 1);

    if(!isdate($date)) continue;

    if(!isdate($date)) exc('Tanggal harus diisi.');
    if(!is_array($details) && count($details) > 0) throw new Exception("Invalid details parameter.");
    if(!is_array($details) || count($details) <= 0) throw new Exception('Detil akun belum diisi.');

    $totaldebitamount = $totalcreditamount = 0;
    for($j = 0 ; $j < count($details) ; $j++){
      $detail = $details[$j];
      if(!isset($detail['coaid'])) throw new Exception('Parameter coaid of detail is required.');
      if(!isset($detail['debitamount']) && !isset($detail['creditamount'])) throw new Exception('Parameter creditamount/debitamount of detail is required.');
      if(isset($detail['debitamount']) && $detail['debitamount'] < 0) throw new Exception('Invalid debit amount parameter, number required. ' . $detail['debitamount']);
      if(isset($detail['creditamount']) && $detail['creditamount'] < 0) throw new Exception('Invalid credit amount parameter, number required. ' . $detail['creditamount']);
      if(!isset($chartofaccounts_indexbyid[$detail['coaid']])) throw new Exception('Invalid coaid parameter.');
      $totaldebitamount += $detail['debitamount'];
      $totalcreditamount += $detail['creditamount'];
    }
    if(abs($totaldebitamount - $totalcreditamount) > 0.001){
      throw new Exception("Jurnal tidak balance $totaldebitamount-$totalcreditamount " . json_encode($details));
    }
  }

  $related_coaids = [];

  try{

    pdo_begin_transaction();

    foreach($journalvouchers as $journalvoucher){
      $rows = pmrs("select t2.coaid from journalvoucher t1, journalvoucherdetail t2 where t1.id = t2.jvid and 
      t1.ref = ? and t1.refid = ?", [ $journalvoucher['ref'], $journalvoucher['refid'] ]);
      if(is_array($rows))
        foreach($rows as $row)
          $related_coaids[$row['coaid']] = 1;
      pm("delete from journalvoucher where `ref` = ? and refid = ?", [ $journalvoucher['ref'], $journalvoucher['refid'] ]);
    }

    $params = $queries = [];
    for($i = 0 ; $i < count($journalvouchers) ; $i++){

      $journalvoucher = $journalvouchers[$i];
      $date = ov('date', $journalvoucher);
      $description = ov('description', $journalvoucher, 1, array('notempty'=>1));
      $ref = ov('ref', $journalvoucher, 0, 'JV');
      $refid = ov('refid', $journalvoucher, 0, 0);
      $details = ov('details', $journalvoucher, 1);
      $type = ov('type', $journalvoucher, 0, 'M');
      $createdon = date('YmdHis');
      $createdby = isset($journalvoucher['createdby']) ? $journalvoucher['createdby'] : (isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0);

      $totaldebitamount = $totalcreditamount = 0;
      for($j = 0 ; $j < count($details) ; $j++){
        $detail = $details[$j];
        $totaldebitamount += $detail['debitamount'];
        $totalcreditamount += $detail['creditamount'];
      }

      $query = "INSERT INTO journalvoucher(`date`, `type`, description, amount, ref, refid, createdon, createdby) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
      $id = pmi($query, array($date, $type, $description, abs($totaldebitamount), $ref, $refid, $createdon, $createdby));

      for($j = 0 ; $j < count($details) ; $j++){

        $detail = $details[$j];
        $coaid = $detail['coaid'];
        $section = ov('section', $detail, 0, 0);
        $debitamount = floatval($detail['debitamount']);
        $creditamount = floatval($detail['creditamount']);
        $type = isset($detail['type']) && in_array($detail['type'], array('D', 'C')) ? $detail['type'] : ($debitamount == 0 ? 'C' : 'D');
        $amount = $type == 'C' ? $creditamount : $debitamount;
        $totaldebitamount += $debitamount;
        $totalcreditamount += $creditamount;
        $debit = $type == 'D' ? $amount : 0;
        $credit = $type == 'C' ? $amount : 0;

        $section = !$section ? 0 : $section;

        $queries[] = "(?, ?, ?, ?, ?, ?)";
        array_push($params, $id, $section, $coaid, $type, $debit, $credit);
        $related_coaids[$coaid] = 1;
      }

    }
    $query = "INSERT INTO journalvoucherdetail(jvid, section, coaid, `type`, debit, credit) VALUES " . implode(',', $queries);
    pm($query, $params);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

  chartofaccountrecalculate(array_keys($related_coaids));

}
function journalvoucherentry($journalvoucher, $log = false){

  // --------------------
  // Extract parameters
  // --------------------
  global $chartofaccounts_indexbyid;
  $date = ov('date', $journalvoucher, 1, array('type'=>'date'));
  $description = ov('description', $journalvoucher, 1, array('notempty'=>1));
  $ref = ov('ref', $journalvoucher, 0, 'JV');
  $refid = ov('refid', $journalvoucher, 0, 0);
  $details = ov('details', $journalvoucher, 1);
  $type = ov('type', $journalvoucher, 0, 'M');
  $createdon = date('YmdHis');
  $createdby = $_SESSION['user']['id'];

  // --------------------
  // Validation
  // --------------------
  if(!isdate($date)) exc('Tanggal harus diisi.');
  if(!is_array($details) && count($details) > 0) throw new Exception("Invalid details parameter.");
  if(!is_array($details) || count($details) <= 0) throw new Exception('Detil akun belum diisi.');
  $totaldebitamount = $totalcreditamount = 0;
  $related_coaids = array();
  $paramstr = $params = array();
  for($i = 0 ; $i < count($details) ; $i++){
    $detail = $details[$i];
    if(count($detail) == 0) continue;
    if(!$detail['coaid'] && !$detail['debitamount'] && !$detail['creditamount']) continue;
    if(!isset($detail['coaid'])) throw new Exception('Parameter coaid of detail is required.');
    if(!isset($detail['debitamount']) && !isset($detail['creditamount'])) throw new Exception('Parameter creditamount/debitamount of detail is required.');
    if(isset($detail['debitamount']) && $detail['debitamount'] < 0) throw new Exception('Invalid debit amount parameter, number required. ' . $detail['debitamount']);
    if(isset($detail['creditamount']) && $detail['creditamount'] < 0) throw new Exception('Invalid credit amount parameter, number required. ' . $detail['creditamount']);
    if(!isset($chartofaccounts_indexbyid[$detail['coaid']])) throw new Exception('Invalid coaid parameter.');
    $totaldebitamount += $detail['debitamount'];
    $totalcreditamount += $detail['creditamount'];
  }
  if(abs($totaldebitamount - $totalcreditamount) > 0.001){
    throw new Exception('Jurnal tidak balance');
  }

  // --------------------
  // Store to database
  // --------------------
  try{

    pdo_begin_transaction();

    $query = "INSERT INTO journalvoucher(`date`, `type`, description, amount, ref, refid, createdon, createdby) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $id = pmi($query, array($date, $type, $description, abs($totaldebitamount), $ref, $refid, $createdon, $createdby));

    for($i = 0 ; $i < count($details) ; $i++){
      $detail = $details[$i];
      if(count($detail) == 0) continue;
      if(!$detail['coaid'] && !$detail['debitamount'] && !$detail['creditamount']) continue;
      $coaid = $detail['coaid'];
      $section = ov('section', $detail, 0, 0);
      $debitamount = floatval($detail['debitamount']);
      $creditamount = floatval($detail['creditamount']);
      $type = isset($detail['type']) && in_array($detail['type'], array('D', 'C')) ? $detail['type'] : ($debitamount == 0 ? 'C' : 'D');
      $amount = $type == 'C' ? $creditamount : $debitamount;
      $totaldebitamount += $debitamount;
      $totalcreditamount += $creditamount;
      $debit = $type == 'D' ? $amount : 0;
      $credit = $type == 'C' ? $amount : 0;

      $paramstr[] = "(?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $section, $coaid, $type, $debit, $credit);
      $related_coaids[$detail['coaid']] = 1;
    }
    $query = "INSERT INTO journalvoucherdetail(jvid, section, coaid, `type`, debit, credit) VALUES " . implode(',', $paramstr);
    pm($query, $params);

    if($log) userlog('journalvoucherentry', $journalvoucher, null, $_SESSION['user']['id'], $id);

    pdo_commit();

  }
  catch(Exception $ex){

    pdo_rollback();
    throw $ex;

  }

  chartofaccountrecalculate(array_keys($related_coaids));

  return array('id'=>$id);
  
}
function journalvouchermodify($journalvoucher, $log = false){

  $id = ov('id', $journalvoucher, 1);
  $current_journalvoucher = journalvoucherdetail(null, array('id'=>$id));
  if(!$current_journalvoucher) throw new Exception('Journal voucher not exists.');
  global $chartofaccounts_indexbyid;

  $updatedrow = array();
  if(isset($journalvoucher['date']) && strtotime($journalvoucher['date']) != strtotime($current_journalvoucher['date'])){
    if(!isdate($journalvoucher['date'])) exc('Format tanggal salah');
    $updatedrow['date'] = date('Ymd', strtotime($journalvoucher['date']));
  }
  if(isset($journalvoucher['description']) && $journalvoucher['description'] != $current_journalvoucher['description'])
    $updatedrow['description'] = $journalvoucher['description'];
  if(isset($journalvoucher['type']) && $journalvoucher['type'] != $current_journalvoucher['type'])
    $updatedrow['type'] = $journalvoucher['type'];

  if(count($updatedrow) > 0){
    $updatedrow['lastupdatedon'] = date('YmdHis');
    $updatedrow['lastupdatedby'] = $_SESSION['user']['id'];
    mysql_update_row('journalvoucher', $updatedrow, array('id'=>$id));
  }

  if(isset($journalvoucher['details'])){

    $updatedrow['details'] = $journalvoucher['details'];

    // Validation
    // - Check if details is array
    if(!is_array($journalvoucher['details'])) throw new Exception('Invalid details parameter, array expected.');
    // - Check if details has
    if(count($journalvoucher['details']) <= 0) throw new Exception('Detil akun belum diisi.');
    // - Check each detail parameter
    $totaldebitamount = $totalcreditamount = 0;
    $paramstr = $params = array();
    for($i = 0 ; $i < count($journalvoucher['details']) ; $i++){
      $detail = $journalvoucher['details'][$i];
      if(!$detail['coaid'] && !$detail['debitamount'] && !$detail['creditamount']) continue;
      if(!isset($detail['coaid'])) throw new Exception('Parameter coaid of detail is required.');
      if(!isset($detail['debitamount']) && !isset($detail['creditamount'])) throw new Exception('Parameter creditamount/debitamount of detail is required.');
      if(isset($detail['debitamount']) && $detail['debitamount'] < 0) throw new Exception('Invalid debit amount parameter, number required. ' . $detail['debitamount']);
      if(isset($detail['creditamount']) && $detail['creditamount'] < 0) throw new Exception('Invalid credit amount parameter, number required. ' . $detail['creditamount']);
      if(!isset($chartofaccounts_indexbyid[$detail['coaid']])) throw new Exception('Invalid coaid parameter.');

      $coaid = $detail['coaid'];
      $section = ov('section', $detail, 0, 0);
      $debitamount = floatval($detail['debitamount']);
      $creditamount = floatval($detail['creditamount']);
      $type = isset($detail['type']) && in_array($detail['type'], array('D', 'C')) ? $detail['type'] : ($debitamount == 0 ? 'C' : 'D');
      $amount = $type == 'C' ? $creditamount : $debitamount;
      $totaldebitamount += $debitamount;
      $totalcreditamount += $creditamount;
      $debit = $type == 'D' ? $amount : 0;
      $credit = $type == 'C' ? $amount : 0;

      $paramstr[] = "(?, ?, ?, ?, ?, ?)";
      array_push($params, $id, $section, $coaid, $type, $debit, $credit);
      $related_coaids[$detail['coaid']] = 1;
    }
    if(abs($totaldebitamount - $totalcreditamount) > 0.001)
      throw new Exception('Jurnal tidak balance');

    $related_coaids = array();
    foreach($current_journalvoucher['details'] as $currentdetail)
      $related_coaids[$currentdetail['coaid']] = 1;

    try{

      pdo_begin_transaction();

      // Store to database
      $query = "DELETE FROM journalvoucherdetail WHERE jvid = ?";
      pm($query, array($id));
      $query = "INSERT INTO journalvoucherdetail(jvid, section, coaid, `type`, debit, credit) VALUES " . implode(',', $paramstr);
      pm($query, $params);
      $query = "UPDATE journalvoucher SET amount = ? WHERE `id` = ?";
      pm($query, array(abs($totaldebitamount), $id));

      if($log) userlog('journalvouchermodify', $current_journalvoucher, $updatedrow, $_SESSION['user']['id'], $id);

      pdo_commit();

    }
    catch(Exception $ex){

      pdo_rollback();
      throw $ex;

    }

  }

  chartofaccountrecalculate(array_keys($related_coaids));

  return array('id'=>$id);

}
function journalvoucherremove($filters, $log = false){

  $ids = array();
  if(isset($filters['description'])){
    $description = $filters['description'];
    $rows = pmrs("SELECT `id` FROM journalvoucher WHERE description = ?", array($description));
    for($i = 0 ; $i < count($rows) ; $i++)
      $ids[] = $rows[$i]['id'];
  }
  else if(isset($filters['ref']) && isset($filters['refid'])){
    $ref = $filters['ref'];
    $refid = $filters['refid'];
    $rows = pmrs("SELECT `id` FROM journalvoucher WHERE ref = ? AND refid = ?", array($ref, $refid));
    for($i = 0 ; $i < count($rows) ; $i++)
      $ids[] = $rows[$i]['id'];
  }
  else{
    $id = ov('id', $filters, 1);
    array_push($ids, $id);
  }

  $related_coaids = [];
  for($i = 0 ; $i < count($ids) ; $i++){

    $id = $ids[$i];
    $current = journalvoucherdetail('*', [ 'id'=>$id ]);
    if(!$current) continue;

    $query = "SELECT coaid FROM journalvoucherdetail WHERE jvid = ?";
    $rows = pmrs($query, array($id));
    for($j = 0 ; $j < count($rows) ; $j++)
      $related_coaids[$rows[$j]['coaid']] = 1;

    $query = "DELETE FROM journalvoucher WHERE `id` = ?";
    pm($query, array($id));

    if($log) userlog('journalvoucherremove', $current, null, $_SESSION['user']['id'], $id);

  }

  chartofaccountrecalculate(array_keys($related_coaids));

}

function journalvoucheritemmodify($condition, $update){

  $ref = $condition['ref'];
  $refid = $condition['refid'];
  $coaid = $condition['coaid'];

  $id = pmc("select t2.id from journalvoucher t1, journalvoucherdetail t2
    where t1.id = t2.jvid and t1.ref = ? and t1.refid = ? and t2.coaid = ?",
    [ $ref, $refid, $coaid ]);

  mysql_update_row("journalvoucherdetail", $update, [ 'id'=>$id ], [ 'skip_table_checking'=>true ]);

}

?>