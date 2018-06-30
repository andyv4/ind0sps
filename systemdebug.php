<?php
$modulename = 'systemdebug';

function defaultmodule(){

  $columns = array(
    array('active'=>0, 'name'=>'id', 'text'=>'ID', 'width'=>40),
    array('active'=>1, 'name'=>'requestid', 'text'=>'Request ID', 'width'=>120),
    array('active'=>1, 'name'=>'description', 'text'=>'Description', 'width'=>200),
    array('active'=>1, 'name'=>'params', 'text'=>'Params', 'width'=>200, 'datatype'=>'serializedobj'),
    array('active'=>1, 'name'=>'result', 'text'=>'Result', 'width'=>200, 'datatype'=>'serializedobj'),
    array('active'=>1, 'name'=>'timestamp', 'text'=>'Timestamp', 'width'=>90)
  );

  $module = array(
    'title'=>'System Debug',
    'columns'=>$columns,
    'presets'=>array(
      array(
        'text'=>'Default',
        'columns'=>$columns,
        'viewtype'=>'list',
        'sorts'=>array(
          array('name'=>'timestamp', 'sorttype'=>'desc')
        )
      )
    ),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'Last Request:', 'value'=>'lastrequest&equals&')
    )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $filters = array_index($filters, array('name'), 1);

  $systemdebug_columnaliases = array(
    'requestid'=>'t1.requestid',
    'description'=>'t1.description',
    'timestamp'=>'t1.timestamp',
    'params'=>'t1.params',
    'result'=>'t1.result'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $systemdebug_columnaliases);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $sortquery = sortquery_from_sorts($sorts, $systemdebug_columnaliases);

  $wherequery = wherequery_from_filters($params, $filters, $systemdebug_columnaliases);
  if(isset($filters['lastrequest'])){
    if(strlen($wherequery) > 0) $wherequery .= ', ';
    else $wherequery .= 'WHERE ';
    $wherequery .= "requestid = (select requestid from systemdebug order by timestamp desc limit 1)";
  }

  $limitquery = limitquery_from_limitoffset($limits);
  $query = "SELECT t1.id $columnquery FROM systemdebug t1 $wherequery $sortquery $limitquery";

  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  return "<td><button class='red' onclick=\"ui.async('systemdebug_clear', [], { waitel:this, callback:'ui(\'#reloadbtn\').click()' })\"><span class='mdi mdi-close'></span></button></td>";

}

include 'rcfx/dashboard1.php';
?>