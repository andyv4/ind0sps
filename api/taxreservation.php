<?php

$taxreservation_columns = [
  'id'=>'t1.id!',
  'createdon'=>'t1.createdon',
  'type'=>'t1.type',
  'prefix'=>'t1.prefix!',
  'midfix'=>'t1.midfix!',
  'start_index'=>'t1.start_index!',
  'end_index'=>'t1.end_index!',
  'index_length'=>'t1.index_length!',
];

function taxreservation_list($columns = null, $sorts = null, $filters = null, $limits = null){

  global $taxreservation_columns;

  $params = [];
  $columnquery = columnquery_from_columnaliases($columns, $taxreservation_columns);
  $wherequery = wherequery_from_filters($params, $filters, $taxreservation_columns, $columns);
  $sortquery = sortquery_from_sorts($sorts, $taxreservation_columns);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT * FROM taxcodereservation t1 $wherequery $sortquery $limitquery";

  $data = pmrs($query, $params);

  if(is_array($data)){
    for($i = 0 ; $i < count($data) ; $i++){
      $obj = $data[$i];

      if(isset($obj['index_length']) && isset($obj['start_index']) && isset($obj['end_index'])){

        $index_length = $obj['index_length'];
        $start_index = $obj['start_index'];
        $end_index = $obj['end_index'];
        $prefix = $obj['prefix'];
        $midfix = $obj['midfix'];

        $start_range = $prefix . '.' . $midfix . '.' . str_pad($start_index, $index_length, '0', STR_PAD_LEFT);
        $end_range = $prefix . '.' . $midfix . '.' . str_pad($end_index, $index_length, '0', STR_PAD_LEFT);

        $data[$i]['start_range'] = $start_range;
        $data[$i]['end_range'] = $end_range;

        $count = pmc("select count(*) from taxcodereservationpool where tid = ?", [ $data[$i]['id'] ]);
        $count_used = pmc("select count(*) from taxcodereservationpool where tid = ? and `status` != 0", [ $data[$i]['id'] ]);
        $data[$i]['summary'] = $count_used . '/' . $count;

      }

      unset($data[$i]['prefix']);
      unset($data[$i]['midfix']);
      unset($data[$i]['index_length']);
      unset($data[$i]['start_index']);
      unset($data[$i]['end_index']);

    }

  }

  return $data;

}

function taxreservation_detail($id){

  $type = pmc("select `type` from taxcodereservationpool where tid = ? limit 1", [ $id ]);

  switch($type){

    case 'SI':
      $rows = pmrs("select 
        t1.code, 
        (select `code` from salesinvoice where `id` = t1.typeid) as typecode,
        (select `status` from salesinvoice where `id` = t1.typeid) as typestatus
        from taxcodereservationpool t1 
        where t1.tid = ?", [ $id ]);
      break;

    case 'PI':
      $rows = pmrs("select 
        t1.code, 
        (select `code` from purchaseinvoice where `id` = t1.typeid) as typecode,
        (select `status` from purchaseinvoice where `id` = t1.typeid) as typestatus
        from taxcodereservationpool t1 
        where t1.tid = ?", [ $id ]);
      break;

  }

  return $rows;

}

function taxreservation_new($obj){

  // Extract parameters
  $type = $obj['type'];
  $prefix = $obj['prefix'];
  $midfix = $obj['midfix'];
  $start_index = $obj['start_index'];
  $end_index = $obj['end_index'];

  if(strlen($start_index) != strlen($end_index)) exc("Panjang karakter index tidak sama.");

  // Auto calculated variables
  $index_length = strlen($start_index);
  $createdon = date('YmdHis');

  // Swap start_index and end_index if start_index is greater than end_index
  if($start_index > $end_index){
    $temp = $end_index;
    $end_index = $start_index;
    $start_index = $temp;
  }

  // Generate codes
  $codes = [];
  for($i = $start_index ; $i <= $end_index ; $i++)
    $codes[] = $prefix . '.' . $midfix . '.' . str_pad($i, $index_length, '0', STR_PAD_LEFT);

  // Check if code exists
  $exists = false;
  $checkers = [];
  foreach($codes as $code){
    $checkers[] = "'$code'";
    if(count($checkers) > 100){
      $count = pmc("select count(*) from taxcodereservationpool where code in (" . implode(', ', $checkers) . ")");
      if($count > 0){
        $exists = true;
        break;
      }
      $checkers = [];
    }
  }
  if(count($checkers) > 0){
    $count = pmc("select count(*) from taxcodereservationpool where code in (" . implode(', ', $checkers) . ")");
    if($count > 0) $exists = true;
  }

  // Break on existing code
  if($exists) exc('Tidak dapat reservasi kode ini, Kode sudah pernah ditambahkan sebelumnya.');

  // Insert to taxcodereservation
  $id = pmi("insert into taxcodereservation (createdon, `type`, prefix, midfix, start_index, end_index, index_length) values (?, ?, ?, ?, ?, ?, ?)",
    [ $createdon, $type, $prefix, $midfix, $start_index, $end_index, $index_length ]);

  // Insert to taxcodereservationpool
  try{
    $queries = $params = [];
    $counter = 1;
    foreach($codes as $code){
      $queries[] = "(?, ?, ?, ?, ?, ?)";
      array_push($params, $code, $id, $type, 0, 0, $counter);
      if(count($queries) > 1000){
        pm("insert into taxcodereservationpool (code, tid, `type`, typeid, `status`, `order`) values " . implode(', ', $queries), $params);
        $queries = $params = [];
      }
      $counter++;
    }
    if(count($queries) > 0)
      pm("insert into taxcodereservationpool (code, tid, `type`, typeid, `status`, `order`) values " . implode(', ', $queries), $params);
  }
  catch(Exception $ex){
    pm("delete from taxcodereservation where `id` = ?", [ $id ]);
    pm("delete from taxcodereservationpool where `tid` = ?", [ $id ]);
    throw $ex;
  }

}

function taxreservation_remove($id){

  // Check if id exists
  $exists = pmc("select count(*) from taxcodereservation where `id` = ?", [ $id ]);
  if(!$exists) exc('Tidak dapat menghapus ini.');

  // Check if code already used
  $used_count = pmc("select count(*) from taxcodereservationpool where tid = ? and `status` > 0", [ $id ]);
  if($used_count > 0) exc('Tidak dapat menghapus, kode sudah ada yang terpakai.');

  pm("delete from taxcodereservation where `id` = ?", [ $id ]);

}

function taxreservationpool_get($type, $typeid){

  $existing_code = pmc("select `code` from taxcodereservationpool where `type` = ? AND `typeid` = ?", [ $type, $typeid ]);
  if($existing_code) pm("update taxcodereservationpool set `typeid` = 0, `status` = 0 where `code` = ?", [ $existing_code ]);
  $code = pmc("select code from taxcodereservationpool where `type` = ? and `status` = 0 order by `order` asc limit 1", [ $type ]);
  pm("update taxcodereservationpool set `status` = 1, `type` = ?, typeid = ? where code = ?", [ $type, $typeid, $code ]);
  return $code;

}

function taxreservationpool_reserve_batch($type, $typeids){

  if(!is_array($typeids) || count($typeids) == 0) exc('Invalid typeids parameter, array required.');

  // Release if already exists
  $rows = pmrs("select `code` from taxcodereservationpool where `type` = ? and `typeid` IN (" . implode(',', $typeids) . ")", [ $type ]);
  if(count($rows) > 0){
    $codes = [];
    foreach($rows as $row){
      $code = $row['code'];
      $code[] = "'$code'";
    }
    pm("update taxcodereservationpool set `status` = 0, `typeid` = 0, `type` = '' where code in (" . implode(', ', $codes) . ")");
  }

  // Retrieve code
  $codes = [];
  $count = count($typeids);
  $rows = pmrs("select code from taxcodereservationpool where `type` = ? and `status` = 0 order by `order` asc limit $count", [ $type ]);
  if(count($rows) != $count) exc('Nomor faktur pajak tidak cukup. dibutuhkan ' . count($typeids) . ' kode.');
  $queries = $params = [];
  foreach($rows as $index=>$row){
    $queries[] = "update taxcodereservationpool set `status` = 1, `type` = ?, typeid = ? where code = ?";
    array_push($params, $type, $typeids[$index], $row['code']);
    $codes[] = $row['code'];
  }
  pm(implode(';', $queries), $params);

  return $codes;

}

function taxreservationpool_release_batch($type, $typeids){

  if(is_array($typeids) && count($typeids) > 0){
    pm("update taxcodereservationpool set `status` = 0, typeid = 0 where `type` = ? and typeid in (" . implode(',', $typeids) . ")",
      [ $type ]);
  }

}

function taxreservationpool_set($type, $typeid, $code){

  $existing_code = pmc("select `code` from taxcodereservationpool where `type` = ? AND `typeid` = ?", [ $type, $typeid ]);
  if($existing_code) pm("update taxcodereservationpool set `typeid` = 0, `status` = 0 where `code` = ?", [ $existing_code ]);
  pm("update taxcodereservationpool set `typeid` = ?, `status` = 1 where `type` = ? and `code` = ?", [ $typeid, $type, $code ]);

}

function taxreservationpool_remove($code, $type, $typeid){

  pm("update taxcodereservationpool set `status` = 0, typeid = 0 where code = ? and `type` = ? and typeid = ?",
    [ $code, $type, $typeid ]);

}

function taxreservationpool_release_unused(){

  pm("UPDATE taxcodereservationpool SET typeid = 0, `status` = 0 WHERE typeid > 0 AND `type` = 'SI' AND typeid NOT IN (SELECT `id` FROM salesinvoice)");

}

?>