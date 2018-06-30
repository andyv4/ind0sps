<?php
if(privilege_get('inventoryadjustment', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'inventoryadjustment';

require_once 'api/inventoryadjustment.php';
require_once 'ui/inventoryadjustment.php';

function defaultmodule(){

  $columns = inventoryadjustment_ui_columns();

  $module = array(
    'title'=>'inventoryadjustment',
    'columns'=>$columns,
    'presets'=>array(
        array(
          'text'=>'Detil',
          'columns'=>$columns,
          'sorts'=>array(
            array('name'=>'createdon', 'sorttype'=>'desc')
          ),
          'viewtype'=>'list'
        ),
    ),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'warehousename|code|description|inventorycode|inventorydescription&contains&'),
      array('text'=>'Gudang:', 'value'=>'warehousename&contains&'),
      array('text'=>'Nama Barang:', 'value'=>'inventorydescription&contains&'),
      array('text'=>'Catatan:', 'value'=>'remark&contains&')
    )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $columnaliases = array(
    'id'=>'t1.id',
    'warehouseid'=>'t1.warehouseid',
    'warehousename'=>'t3.name',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'inventoryid'=>'t2.inventoryid',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'amount'=>'t2.amount',
    'remark'=>'t2.remark',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases);
  $wherequery = 'WHERE t1.id = t2.inventoryadjustmentid AND t1.warehouseid = t3.id ' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'inventoryadjustment' as `type`, t1.id, $columnquery
    FROM inventoryadjustment t1, inventoryadjustmentdetail t2, warehouse t3 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = array();
  if(privilege_get('inventoryadjustment', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_inventoryadjustmentdetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('inventoryadjustment', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_inventoryadjustmentexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);
}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_inventoryadjustmentdetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_inventoryadjustmentremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_inventoryadjustmentdetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('inventoryadjustment', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/inventoryadjustment.js"></script>