<?php

$modulename = 'log-u1';

require_once 'api/log.php';

function defaultmodule(){

  $columns = array(
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>1, 'name'=>'timestamp', 'text'=>'Waktu', 'width'=>150, 'datatype'=>'datetime'),
    array('active'=>0, 'name'=>'userid', 'text'=>'User ID', 'width'=>30),
    array('active'=>1, 'name'=>'user_name', 'text'=>'User Name', 'width'=>100),
    array('active'=>1, 'name'=>'action', 'text'=>'Action', 'width'=>200),
    array('active'=>0, 'name'=>'refid', 'text'=>'Ref ID', 'width'=>40),
    array('active'=>1, 'name'=>'remark', 'text'=>'Remark', 'width'=>400),
  );

  $module = array(
    'title'=>'log',
    'columns'=>$columns,
    'presets'=>array(
      array(
        'text'=>'Semua',
        'columns'=>$columns,
        'sorts'=>array(
          array('name'=>'timestamp', 'sorttype'=>'desc')
        ),
        'viewtype'=>'list'
      ),
    ),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'action|user_name&contains&')
    )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $log_columnaliases = array(
    'id'=>'t1.id',
    'action'=>'t1.action',
    'timestamp'=>'t1.timestamp',
    'userid'=>'t1.userid',
    'user_name'=>'t2.name'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $log_columnaliases);
  $wherequery = 'WHERE t1.userid = t2.id ' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $log_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $log_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  $columnquery = 't1.refid' . (strlen($columnquery) > 0 ? ', ' . $columnquery : '');
  $query = "SELECT $columnquery FROM userlog t1, `user` t2 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);

  if(is_array($data)){
    for($i = 0 ; $i < count($data) ; $i++){
      $action = $data[$i]['action'];
      $refid = $data[$i]['refid'];
      $remark = '';

      if($refid){
        if(strpos($action, 'salesinvoice') !== false){
          $remark = pmc("SELECT CONCAT(code, ' - ', customerdescription) FROM salesinvoice WHERE `id` = ?", array($refid));
        }
        else if(strpos($action, 'customer') !== false){
          $remark = pmc("SELECT CONCAT(code, ' - ', description) FROM customer WHERE `id` = ?", array($refid));
        }
        else if(strpos($action, 'inventoryadjustment') !== false){
          $remark = pmc("SELECT code FROM inventoryadjustment WHERE `id` = ?", array($refid));
        }
        else if(strpos($action, 'purchaseinvoice') !== false){
          $remark = pmc("SELECT CONCAT(code, ' - ', supplierdescription) FROM purchaseinvoice WHERE `id` = ?", array($refid));
        }
        else if(strpos($action, 'purchaseorder') !== false){
          $remark = pmc("SELECT CONCAT(code, ' - ', supplierdescription) FROM purchaseorder WHERE `id` = ?", array($refid));
        }
        else if(strpos($action, 'pettycash') !== false){
          $remark = pmc("SELECT code FROM pettycash WHERE `id` = ?", array($refid));
        }
        else if(strpos($action, 'warehousetransfer') !== false){
          $remark = pmc("SELECT code FROM warehousetransfer WHERE `id` = ?", array($refid));
        }
      }

      $data[$i]['remark'] = $remark;
    }

  }


  return $data;

}

function customheadcolumns(){

  return '';

}

include 'rcfx/dashboard1.php';
?>