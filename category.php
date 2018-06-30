<?php

$modulename = 'category';

require_once 'api/category.php';
require_once 'ui/category.php';

function defaultmodule(){

  $columns = category_ui_columns();

  $module = array(
      'title'=>'category',
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
      'frontend_active'=>'t1.frontend_active',
      'name'=>'t1.name',
      'imageurl'=>'t1.imageurl'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $pettycash_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $pettycash_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $pettycash_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $query = "SELECT 'category' as `type`, t1.id $columnquery FROM category t1 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('category', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_categorydetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('category', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_categoryexport', [ ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function categorylist_imageurl($obj){

  $c = "<div class='align-center'>";
  $c .= strlen($obj['imageurl']) > 0 ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>";
  $c .= "</div>";
  return $c;

}

function grid_options($obj){
  $id = $obj['id'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_categorydetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if(privilege_get('category', 'delete')) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus " . $obj['code'] . "')) ui.async('ui_categoryremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_categorydetail', [ this.dataset['id'] ], {})";

}

include 'rcfx/dashboard1.php';
?>