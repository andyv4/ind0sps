<?php
if(!systemvarget('salesable') || privilege_get('salesreturn', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'salesreturn';

require_once 'api/salesreturn.php';
require_once 'ui/salesreturn.php';

function defaultmodule(){

  $columns = salesreturn_ui_columns();

  $module = array(
      'title'=>'Sales Return',
      'columns'=>$columns,
      'presets'=>array(
          array(
              'text'=>'Semua Faktur',
              'columns'=>$columns,
              'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
          array('text'=>'', 'value'=>'code|customerdescription|inventorydescription&contains&')
      ),
      'griddoubleclick'=>"ui.async('ui_salesreturndetail', [ id ], { waitel:this })"
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $salesreturn_columnaliases = array(
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerid'=>'t1.customerid',
    'customerdescription'=>'t1.customerdescription',
    'total'=>'t1.total',
    'inventoryid'=>'t2.inventoryid',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'unittotal'=>'t2.unittotal',
    'createdon'=>'t1.createdon',
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesreturn_columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesreturnid' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesreturn_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesreturn_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'salesreturn' as `type`, t1.id, $columnquery FROM salesreturn t1, salesreturninventory t2 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);
  return $data;

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('salesreturn', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreturndetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span><label>Buat...</label></button></td>";
  if(privilege_get('salesreturn', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_salesreturnexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}

function grid_options($obj){

  $id = $obj['id'];
  $code = ov('code', $obj);

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_salesreturndetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?'))ui.async('ui_salesreturnremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function m_griddoubleclick(){

  return "ui.async('ui_salesreturndetail', [ this.dataset['id'] ], {})";

}

$deletable = privilege_get('salesreturn', 'delete');
include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/salesreturn.js"></script>