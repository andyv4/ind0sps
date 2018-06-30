<?php
if(privilege_get('warehouse', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'warehouse';

require_once 'api/warehouse.php';
require_once 'ui/warehouse.php';

function defaultmodule(){

  $columns = warehouse_ui_columns();

  $module = array(
      'title'=>'warehouse',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Semua Gudang',
              'columns'=>$columns,
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

  $warehouse_columnaliases = array(
    'code'=>'t1.code',
    'name'=>'t1.name',
    'createdon'=>'t1.createdon',
    'moved'=>'t1.moved'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $warehouse_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $warehouse_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $warehouse_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'warehouse' as `type`, t1.id, $columnquery FROM warehouse t1 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('warehouse', 'new'))
    $html[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousedetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('warehouse', 'download'))
    $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_warehouseexport', [ ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $name = $obj['name'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_warehousedetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $name?'))ui.async('ui_warehouseremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function warehouselist_moved($obj){

  $c = "<div class='align-center warehousemove'>";
  $c .= $obj['moved'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-plus color-blue' onclick=\"if(confirm('Pindah gudang ini?')) ui.async('ui_warehousemove', [ $obj[id] ])\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_warehousedetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('warehouse', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/warehouse.js"></script>