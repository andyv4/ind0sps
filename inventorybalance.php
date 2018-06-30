<?php
$modulename = 'inventorybalance';

function defaultmodule(){

  $columns = array(
    array('active'=>1, 'name'=>'id', 'text'=>'ID', 'width'=>50),
    array('active'=>1, 'name'=>'inventoryid', 'text'=>'Inventory ID', 'width'=>50),
    array('active'=>1, 'name'=>'inventorycode', 'text'=>'Inventory Code', 'width'=>80),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Inventory Description', 'width'=>200),
    array('active'=>1, 'name'=>'date', 'text'=>'Date', 'width'=>90, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'in', 'text'=>'In', 'width'=>50, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'out', 'text'=>'Out', 'width'=>50, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitamount', 'text'=>'Unit Amount', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'amount', 'text'=>'Amount', 'width'=>100, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'ref', 'text'=>'Ref', 'width'=>30, 'align'=>'center'),
    array('active'=>1, 'name'=>'refid', 'text'=>'Ref ID', 'width'=>30),
  );
  $module = array(
      'title'=>'Inventory Balance',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Default',
              'columns'=>$columns,
              'viewtype'=>'list'
          )
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'inventorycode|inventorydescription&contains&'),
        array('text'=>'Inventory ID:', 'value'=>'inventoryid&equals&'),
        array('text'=>'Ref ID:', 'value'=>'refid&equals&')
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $aliases = array(
    'id'=>'t1.id',
    'inventoryid'=>'t1.inventoryid',
    'date'=>'t1.date',
    'inventorycode'=>'t2.code as inventorycode',
    'inventorydescription'=>'t2.description as inventorydescription',
    'in'=>'t1.in',
    'out'=>'t1.out',
    'unitamount'=>'t1.unitamount',
    'amount'=>'t1.amount',
    'ref'=>'t1.ref',
    'refid'=>'t1.refid'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $aliases);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $sortquery = sortquery_from_sorts($sorts, $aliases);
  $wherequery = ' WHERE t1.inventoryid = t2.id AND t1.section != 9 ' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $aliases));
  $limitquery = limitquery_from_limitoffset($limits);
  $query = "SELECT t1.id $columnquery FROM inventorybalance t1, inventory t2 $wherequery $sortquery $limitquery";
  console_warn($query);
  $data = pmrs($query, $params);
  return $data;

}

include 'rcfx/dashboard1.php';
?>