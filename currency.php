<?php

$modulename = 'currency';

require_once 'api/currency.php';
require_once 'ui/currency.php';

function defaultmodule(){

  $columns = currency_ui_columns();

  $module = array(
      'title'=>'currency',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Semua',
              'columns'=>$columns,
              'sorts'=>array(
                  array('name'=>'createdon', 'sorttype'=>'desc')
              ),
              'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'code|name&contains&')
    )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $pettycash_columnaliases = array(
    'id'=>'t1.id',
    'code'=>'t1.code',
    'name'=>'t1.name',
    'isdefault'=>'t1.isdefault',
    'total'=>'t1.total',
    'createdon'=>'t1.createdon',
    'createdby'=>'t1.createdby'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $pettycash_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $pettycash_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $pettycash_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $query = "SELECT 'currency' as `type`, t1.id $columnquery FROM currency t1 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  if(privilege_get('currency', 'new'))
    return "<td><button class='blue' onclick=\"ui.async('ui_currencydetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span><label>Baru...</label></button></td>";


}

function grid_options($obj){
  $id = $obj['id'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_currencydetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if(privilege_get('currency', 'delete')) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus " . $obj['code'] . "')) ui.async('ui_currencyremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_currencydetail', [ this.dataset['id'] ], {})";

}

include 'rcfx/dashboard1.php';
?>